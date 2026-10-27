"""Prepare a reviewed, inert content snapshot from the public Orenburg website.

Requires Python 3 and lxml. Does not write to WordPress or execute source scripts.
"""
from __future__ import annotations

import argparse
import concurrent.futures
import copy
import hashlib
import json
import re
import urllib.request
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urljoin, urlparse, urlunparse

from lxml import etree, html

ROOT = Path(__file__).resolve().parents[1]
BASE = "https://oren.vniipo.ru/"
CACHE = ROOT / ".local/oren-source"
DEST = ROOT / "wp-content/plugins/vniipo-core/content"
STRUCTURE = DEST / "structure.json"
BASE_PAGES = {
    "/o-nas/ob-organizacii": "branch",
    "/o-nas/nashi-sotrudniki": "leadership",
    "/o-nas/vakansii": "vacancies",
    "/nauchnaya-deyatel-nost/": "research",
    "/svedeniya-ob-obrazovatel-noy-organizacii_38/": "education-info",
    "/dopolnitel-noe-professional-noe-obuchenie/": "education",
    "/uslugi-v-oblasti-pozharnoy-bezopasnosti/": "activity-types",
    "/poligon/": "testing-ground",
    "/kontakty": "contact-info",
    "/rekvizity": "requisites",
}
EDUCATION = {
    "osnovnye-svedeniya": "basic", "struktura-i-organy-upravleniya-obrazovatel-noy-organizaciey": "governance",
    "dokumenty": "documents", "obrazovanie": "programs", "rukovodstvo": "management",
    "pedagogicheskiy-sostav": "teachers", "material-no-tehnicheskoe-obespechenie-i-osnaschennost-obrazovatel-nogo-processa": "facilities",
    "platnye-obrazovatel-nye-uslugi": "paid-services", "finansovo-hozyaystvennaya-deyatel-nost": "finance",
    "vakantnye-mesta-dlya-priema-perevoda": "vacancies", "stipendii": "support",
    "mezhdunarodnoe-sotrudnichestvo": "international", "pitanie": "meals",
}
MONTHS = {m: i for i, m in enumerate(["янв", "фев", "мар", "апр", "май", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"], 1)}
DROP = "script style form input textarea select button iframe object embed noscript video source".split()
TAGS = set("p div section article span strong b em i u s a img figure figcaption ul ol li h2 h3 h4 h5 h6 table thead tbody tfoot tr th td blockquote br hr dl dt dd sub sup".split())


def canonical(url: str) -> str:
    parsed = urlparse(urljoin(BASE, url))
    if parsed.hostname == "oren.vniipo.ru":
        return urlunparse(("https", "oren.vniipo.ru", parsed.path or "/", "", parsed.query, ""))
    return url


def cache_path(url: str) -> Path:
    return CACHE / (hashlib.sha256(url.encode()).hexdigest()[:16] + ".html")


def fetch(url: str, refresh: bool = False) -> bytes:
    target = cache_path(url)
    if target.exists() and not refresh:
        return target.read_bytes()
    failure = None
    for _ in range(3):
        try:
            request = urllib.request.Request(url, headers={"User-Agent": "VNIIPO-content-migration/0.3 (public content snapshot)"})
            with urllib.request.urlopen(request, timeout=15) as response:
                if urlparse(response.url).hostname != "oren.vniipo.ru":
                    raise ValueError("Unexpected redirect host")
                data = response.read(12 * 1024 * 1024 + 1)
            if len(data) > 12 * 1024 * 1024:
                raise ValueError("Resource exceeds 12 MiB")
            target.write_bytes(data)
            return data
        except Exception as error:
            failure = error
    raise RuntimeError(str(failure))


def content_node(data: bytes, url: str):
    tree = html.fromstring(data)
    matches = tree.xpath('//*[contains(concat(" ",normalize-space(@class)," ")," main-content ")]')
    if not matches:
        raise ValueError("No main-content container")
    node = copy.deepcopy(matches[0])
    title = " ".join(node.xpath("string(.//h1)").split()) or " ".join(tree.xpath("string(//title)").split())
    for element in node.xpath('./div[contains(@class,"breadcrumb")]|./h1'):
        element.drop_tree()
    columns = node.xpath('./div[contains(@class,"row")]/div[contains(concat(" ",normalize-space(@class)," ")," col-lg-8 ")]')
    if columns:
        node = copy.deepcopy(columns[0])
    for element in list(node.iter()):
        if not isinstance(element.tag, str):
            parent = element.getparent()
            if parent is not None:
                parent.remove(element)
            continue
        if element.tag.lower() in DROP and element.getparent() is not None:
            element.drop_tree()
    return title, node


def clean_fragment(node, url: str, media: dict) -> str:
    node = copy.deepcopy(node)
    for element in list(node.iter()):
        if not isinstance(element.tag, str):
            continue
        tag = element.tag.lower()
        if tag not in TAGS:
            if element.getparent() is not None:
                element.drop_tag()
            continue
        attributes = dict(element.attrib)
        element.attrib.clear()
        for name in ("alt", "title", "colspan", "rowspan", "id"):
            if name in attributes:
                element.set(name, attributes[name])
        if tag == "a" and attributes.get("href"):
            href = attributes["href"].strip()
            parsed = urlparse(urljoin(url, href))
            if parsed.scheme in ("http", "https", "mailto", "tel"):
                element.set("href", canonical(urljoin(url, href)))
            visible = " ".join(element.text_content().split())
            if parsed.scheme == "mailto" and "@" in visible and visible.lower() != parsed.path.lower():
                element.attrib.pop("href", None)
            if parsed.scheme == "tel" and not re.search(r"\d", visible):
                element.attrib.pop("href", None)
        if tag == "img" and attributes.get("src"):
            src = canonical(urljoin(url, attributes["src"]))
            if urlparse(src).scheme not in ("http", "https"):
                element.drop_tree()
                continue
            # News galleries remain at the source; article images and page media are preserved.
            element.set("src", src)
            element.set("loading", "lazy")
            if urlparse(src).hostname == "oren.vniipo.ru":
                media.setdefault(src, {})
        if tag == "div" and "fotorama" in attributes.get("class", ""):
            element.set("class", "vn-source-gallery")
    text = etree.tostring(node, encoding="unicode", method="html")
    return re.sub(r"<p>\s*(?:&nbsp;|\xa0|<br\s*/?>)*\s*</p>", "", text)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--refresh", action="store_true")
    parser.add_argument("--news-limit", type=int, default=0, help="0 imports the entire discovered news archive")
    args = parser.parse_args()
    CACHE.mkdir(parents=True, exist_ok=True)
    DEST.mkdir(parents=True, exist_ok=True)
    if (CACHE / "home.html").exists() and not cache_path(BASE).exists():
        cache_path(BASE).write_bytes((CACHE / "home.html").read_bytes())
    archive_url = BASE + "novosti/"
    archive = html.fromstring(fetch(archive_url, args.refresh))
    cards = archive.xpath('//a[contains(concat(" ",normalize-space(@class)," ")," news-item ")]')
    if args.news_limit:
        cards = cards[:args.news_limit]
    news = {}
    for card in cards:
        url = canonical(card.get("href"))
        date_text = " ".join(card.xpath('.//*[contains(@class,"news-date")]/text()')).strip()
        match = re.search(r"(\d{1,2})\s+(\w{3})\w*\s+(\d{4})", date_text.lower())
        date = f"{int(match[3]):04}-{MONTHS[match[2]]:02}-{int(match[1]):02}" if match else None
        image = re.search(r"url\(['\"]?([^)'\"]+)", card.get("style", ""))
        news[url] = {"date": date, "thumbnail": canonical(image[1]) if image else None, "title": " ".join(card.xpath('string(.//*[contains(@class,"news-title")])').split())}
    queue = {canonical(path) for path in BASE_PAGES} | set(news) | {BASE}
    # Links from the source education index are part of the required disclosure structure.
    for path in EDUCATION:
        queue.add(BASE + path + "/")
    queue |= {BASE + "dopolnitel-noe-professional-noe-obrazovanie/", BASE + "obuchenie-rabotodateley-i-rabotnikov-voprosam-ohrany-truda/", BASE + "nauchnaya-deyatel-nost/sekciya-nauchno-tehnicheskogo-soveta"}
    documents = {}
    failures = []
    for depth in range(3):
        tasks = sorted(queue - documents.keys())
        if not tasks:
            break
        queue = set()
        with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
            futures = {executor.submit(fetch, url, args.refresh): url for url in tasks}
            for future in concurrent.futures.as_completed(futures):
                url = futures[future]
                try:
                    data = future.result()
                    title, node = content_node(data, url)
                    documents[url] = (title, node, data)
                    print(f"Read {len(documents)}: {url}", flush=True)
                    if url not in news:
                        for anchor in node.xpath('.//a[@href]'):
                            target = canonical(urljoin(url, anchor.get("href")))
                            parsed = urlparse(target)
                            if parsed.hostname == "oren.vniipo.ru" and not parsed.query and not Path(parsed.path).suffix and target not in documents and "/novosti" not in parsed.path and not any(x in parsed.path for x in ("tologin", "ajax", "login", "search")):
                                queue.add(target)
                except Exception as error:
                    failures.append({"url": url, "error": str(error)})
        if len(documents) > 220:
            raise RuntimeError("Unexpected crawl expansion; review source links")

    structure = json.loads(STRUCTURE.read_text(encoding="utf-8-sig"))
    existing = {page["key"]: page for page in structure["pages"]}
    mapping = {}
    extra = []
    for url, (title, _, _) in documents.items():
        path = urlparse(url).path
        if url in news or url == BASE or path in ("/novosti/", "/osnovnye-vidy-deyatel-nosti/"):
            continue
        key = BASE_PAGES.get(path)
        if key:
            target = existing[key]["path"]
        else:
            slug = path.strip("/").split("/")[-1]
            if slug in EDUCATION:
                key = "education-" + EDUCATION[slug]
                target = "/education/information/" + EDUCATION[slug] + "/"
            elif "uslugi-v-oblasti-pozharnoy-bezopasnosti/" in path:
                key = "service-" + hashlib.sha256(path.encode()).hexdigest()[:8]
                target = "/branch/activities/types/" + slug + "/"
            elif "sekciya-nauchno-tehnicheskogo-soveta" in path:
                key, target = "scientific-council", "/research/council/"
            elif slug == "dopolnitel-noe-professional-noe-obrazovanie":
                key, target = "professional-education", "/education/professional/"
            elif slug == "obuchenie-rabotodateley-i-rabotnikov-voprosam-ohrany-truda":
                key, target = "occupational-training", "/education/occupational-safety/"
            else:
                key = "source-" + hashlib.sha256(path.encode()).hexdigest()[:8]
                target = "/branch/useful/source-materials/" + slug + "/"
            if key not in existing:
                extra.append({"key": key, "path": target, "title": title, "source_url": url})
                existing[key] = extra[-1]
        mapping[url] = {"key": key, "path": target}
    if any(p["path"].startswith("/branch/useful/source-materials/") for p in extra):
        if "source-materials" not in existing:
            structure["pages"].append({"key": "source-materials", "path": "/branch/useful/source-materials/", "title": "Дополнительные материалы филиала", "source_url": BASE})
    structure["pages"].extend(sorted(extra, key=lambda item: (item["path"].count("/"), item["path"])))
    media = {}
    records = []
    for url, (title, node, data) in sorted(documents.items()):
        if url == BASE or url not in news and url not in mapping:
            continue
        record = {"uid": ("news:" if url in news else "page:") + url, "source_url": url, "source_sha256": hashlib.sha256(data).hexdigest(), "title": title}
        if url in news:
            record.update({"kind": "post", "slug": "oren-" + urlparse(url).path.rstrip("/").split("/")[-1], **news[url]})
            if record["thumbnail"]:
                media.setdefault(record["thumbnail"], {})
            # Keep the full source gallery available via an explicit source link.
        else:
            record.update({"kind": "page", **mapping[url]})
        record["html"] = clean_fragment(node, url, media)
        excerpt = " ".join(node.text_content().split())
        record["excerpt"] = excerpt[:260] + ("…" if len(excerpt) > 260 else "")
        records.append(record)

    for url, metadata in news.items():
        if url in documents:
            continue
        record = {'uid': 'news:' + url, 'kind': 'post', 'source_url': url, 'source_sha256': hashlib.sha256(json.dumps(metadata, ensure_ascii=False, sort_keys=True).encode()).hexdigest(), 'source_hash_scope': 'archive-card', 'source_unavailable': True, 'title': metadata['title'], 'date': metadata['date'], 'thumbnail': metadata['thumbnail'], 'slug': 'oren-' + urlparse(url).path.rstrip('/').split('/')[-1], 'excerpt': '', 'html': '<p>Заголовок и дата перенесены из архива oren.vniipo.ru. Полный текст временно не удалось получить; он доступен по ссылке на источник ниже.</p>'}
        records.append(record)
        if metadata['thumbnail']:
            media.setdefault(metadata['thumbnail'], {})

    titles = {record['source_url']: record['title'] for record in records}
    for record in records:
        fragment = html.fromstring(record['html'])
        for anchor in fragment.xpath('.//a[@href]'):
            if not anchor.text_content().strip() and not anchor.xpath('.//img'):
                anchor.text = titles.get(anchor.get('href'), 'Открыть материал на сайте источника')
        if not fragment.text_content().strip() and not fragment.xpath('.//img'):
            paragraph = etree.SubElement(fragment, 'p')
            paragraph.text = 'В источнике не опубликовано описание этого раздела.'
            record['source_content_empty'] = True
        record['html'] = etree.tostring(fragment, encoding='unicode', method='html')

    if BASE in documents:
        home_tree = html.fromstring(documents[BASE][2])
        about = home_tree.xpath('//*[@id="main-screen"]//div[contains(concat(" ",normalize-space(@class)," ")," col-lg-6 ")][div[contains(@class,"block-header")][contains(.,"ФГБУ")]]')
        if about:
            block = etree.Element('div')
            for paragraph in about[0].xpath('./p'):
                block.append(copy.deepcopy(paragraph))
            records.append({'uid': 'block:branch-summary', 'kind': 'block', 'title': 'О филиале', 'area': 'home-bottom', 'source_url': BASE, 'source_sha256': hashlib.sha256(documents[BASE][2]).hexdigest(), 'html': clean_fragment(block, BASE, media), 'excerpt': ''})

    contacts = {}
    contact_record = next((record for record in records if record.get('key') == 'contact-info'), None)
    if contact_record:
        contact_tree = html.fromstring(contact_record['html'])
        paragraphs = [' '.join(p.text_content().replace('\xa0', ' ').split()) for p in contact_tree.xpath('.//p')]
        for paragraph in paragraphs:
            for label, key in [('Научно-исследовательский отдел:', 'department_address'), ('График работы:', 'hours'), ('E-mail:', 'email_display')]:
                if label in paragraph:
                    contacts[key] = paragraph.split(label, 1)[1].strip()
        contacts['phones'] = list(dict.fromkeys(re.findall(r'\+7\s*\(3532\)\s*\d{2}-\d{2}-\d{2}', ' '.join(paragraphs))))
        contacts['source_url'] = contact_record['source_url']

    media_dir = DEST / "source-media"
    media_dir.mkdir(exist_ok=True)
    def download_media(url):
        suffix = Path(urlparse(url).path).suffix.lower()
        if suffix not in (".jpg", ".jpeg", ".png", ".gif", ".webp"):
            return url, {"status": "external"}
        name = hashlib.sha256(url.encode()).hexdigest()[:20] + suffix
        target = media_dir / name
        try:
            if target.exists() and not args.refresh:
                data = target.read_bytes()
            else:
                data = fetch(url, args.refresh)
                if not (data.startswith(b'\xff\xd8') or data.startswith(b'\x89PNG') or data.startswith(b'GIF8') or data.startswith(b'RIFF')):
                    raise ValueError("Not a raster image")
                target.write_bytes(data)
            return url, {"status": "local", "file": name, "sha256": hashlib.sha256(data).hexdigest(), "bytes": len(data)}
        except Exception as error:
            return url, {"status": "external", "error": str(error)}
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
        for url, result in executor.map(download_media, sorted(media)):
            media[url] = result
    snapshot = {"version": 1, "fetched_at": datetime.now(timezone.utc).isoformat(), "base_url": BASE, "archive_discovered": len(cards), "records": records, "media": media, "failures": failures, "links": mapping, "contacts": contacts,
        "notes": ["В шапке источника указан адрес на Советской 97, в блоке карты главной — Селивановский переулок 30/32. Новые контакты берутся со страницы Контакты с подписью Научно-исследовательский отдел.", "На странице Контакты подпись email of@vniipo.ru отличается от mailto of.vniipo@mail.ru. Требуется подтверждение владельцем; автоматическая подмена адреса не выполняется.", "Формы, исполняемые скрипты, карты и сторонние виджеты не перенесены. Галереи новостей доступны по ссылкам на оригинальные публикации."]}
    (DEST / "oren-source.json").write_text(json.dumps(snapshot, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    STRUCTURE.write_text(json.dumps(structure, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({"records": len(records), "news": sum(r["kind"] == "post" for r in records), "pages": sum(r["kind"] == "page" for r in records), "structure": len(structure["pages"]), "local_media": sum(v["status"] == "local" for v in media.values()), "failures": failures}, ensure_ascii=False), flush=True)


if __name__ == "__main__":
    main()

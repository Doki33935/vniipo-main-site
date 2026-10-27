"""Reuse published source excerpts and file references in the sections required by the specification."""
import copy
from lxml import html, etree


def add_derived_records(snapshot, structure):
    records = snapshot['records']
    pages = {r.get('key'): r for r in records if r['kind'] == 'page'}
    definitions = {p['key']: p for p in structure['pages']}

    def add(key, origin, fragment):
        if key in pages or key not in definitions:
            return
        record = {
            'uid': 'page:source-reference:' + key, 'kind': 'page', 'key': key,
            'path': definitions[key]['path'], 'title': definitions[key]['title'],
            'source_url': origin['source_url'], 'source_sha256': origin['source_sha256'],
            'derived_from': origin['uid'], 'excerpt': '',
            'html': etree.tostring(fragment, encoding='unicode', method='html'),
        }
        records.append(record)
        pages[key] = record

    for key, source_key, match in (
        ('charter', 'education-documents', 'устав'),
        ('licenses', 'education-documents', 'лицензия'),
        ('structure', 'education-governance', 'структура и органы управления'),
    ):
        origin = pages.get(source_key)
        if not origin:
            continue
        fragment = etree.Element('div')
        for anchor in html.fromstring(origin['html']).xpath('//a[@href]'):
            if match in anchor.text_content().lower():
                paragraph = etree.SubElement(fragment, 'p')
                paragraph.append(copy.deepcopy(anchor))
        if len(fragment):
            if key == 'licenses':
                etree.SubElement(fragment, 'p').text = 'Опубликована ссылка на образовательную лицензию из действующего сайта. Сведения о других лицензиях требуют отдельного подтверждения.'
            add(key, origin, fragment)

    block = next((r for r in records if r['kind'] == 'block' and r['uid'] == 'block:branch-summary'), None)
    if block:
        fragment = etree.Element('div')
        for paragraph in html.fromstring(block['html']).xpath('.//p'):
            if 'задач' in paragraph.text_content().lower():
                fragment.append(copy.deepcopy(paragraph))
        if len(fragment):
            add('goals', block, fragment)

    history = next((r for r in records if r['kind'] == 'post' and r.get('slug') == 'oren-preobrazovanie-v-filial-formirovanie-poligonnoy-bazy'), None)
    if history:
        fragment = etree.Element('div')
        etree.SubElement(fragment, 'h2').text = history['title']
        etree.SubElement(fragment, 'p').text = 'Публикация источника от ' + history['date'] + '.'
        fragment.append(html.fromstring(history['html']))
        add('history', history, fragment)

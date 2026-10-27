import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';

const base = process.env.SITE_URL || 'http://localhost:8088';
const snapshot = JSON.parse(await readFile(new URL('../wp-content/plugins/vniipo-core/content/oren-source.json', import.meta.url), 'utf8'));
let passed = 0;
async function page(route) {
  const response = await fetch(new URL(route, base), { signal: AbortSignal.timeout(15000) });
  assert.equal(response.status, 200, route);
  const html = await response.text();
  assert(!/Fatal error|Uncaught (?:Error|TypeError)|<b>Warning<\/b>/.test(html), route);
  passed++;
  return html;
}
const home = await page('/');
const brand = home.match(/class="vn-brand"[\s\S]*?<\/a>/)?.[0] || '';
assert(brand.indexOf('ФГБУ ВНИИПО МЧС России') >= 0 && brand.indexOf('ФГБУ ВНИИПО МЧС России') < brand.indexOf('Оренбургский филиал'));
assert(home.includes('О филиале') && home.includes('vn-information-block'));
assert(home.includes('Советская 97') && home.includes('57-22-95'));
for (const record of snapshot.records.filter(item => item.kind === 'page')) {
  const html = await page(record.path);
  assert(html.includes('oren.vniipo.ru') && html.includes('Снимок от'), record.key);
  if (record.key === 'contact-info') assert(html.includes('of@vniipo.ru') && html.includes('of.vniipo@mail.ru'));
}
const news = snapshot.records.filter(item => item.kind === 'post').sort((a, b) => b.date.localeCompare(a.date))[0];
const article = await page('/' + news.slug + '/');
assert(article.includes(news.title) && article.includes('Снимок от'));
const image = Object.values(snapshot.media).find(item => item.status === 'local');
const response = await fetch(new URL('/wp-content/plugins/vniipo-core/content/source-media/' + image.file, base));
assert.equal(response.status, 200);
assert(response.headers.get('content-type')?.startsWith('image/'));
passed++;
console.log(`PASS: ${passed} source HTTP checks: branding order, ${snapshot.records.filter(record => record.kind === 'page').length} populated pages, contacts, news, source block and bundled image.`);

import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
const state = JSON.parse(await readFile(process.argv[2] || '.local/language-test-state.json', 'utf8'));
const base = process.env.SITE_URL || 'http://localhost:8088';
let passed = 0;
for (const [language, url] of Object.entries(state.homes)) {
  const response = await fetch(url, { redirect: 'manual', signal: AbortSignal.timeout(15000) });
  assert.equal(response.status, 200, `${language}: ${url}`);
  const html = await response.text();
  assert(!/Fatal error|Uncaught (?:Error|TypeError)|<b>Warning<\/b>|<b>Notice<\/b>/.test(html), language);
  const marker = language === 'en' ? 'Branch news' : language === 'zh-hant' ? '分部新聞' : 'Новости филиала';
  assert(html.includes(marker), `${language}: missing translated interface`);
  assert(html.includes(language === 'zh-hant' ? 'lang="zh-TW"' : `lang="${language === 'en' ? 'en-US' : 'ru-RU'}"`), language);
  assert(html.includes('hreflang="en"') && html.includes('hreflang="zh-TW"'), `${language}: hreflang`);
  assert(html.includes('lang-item') && html.includes('繁體中文'), `${language}: switcher`);
  assert(html.includes('data-vn-gallery'), `${language}: gallery`);
  passed++;
}
const page = await fetch(new URL('/research/', base));
assert.equal(page.status, 200);
assert((await page.text()).includes('vn-presentation-hero'));
passed++;
console.log(`PASS: ${passed} multilingual/presentation HTTP checks: ru, en, traditional Chinese, lang, hreflang, switcher and gallery.`);

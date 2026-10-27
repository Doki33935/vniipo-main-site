import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';

const base = process.env.SITE_URL || 'http://localhost:8088';
const structure = JSON.parse(await readFile(new URL('../wp-content/plugins/vniipo-core/content/structure.json', import.meta.url), 'utf8'));
const failures = [];
let passed = 0;
async function check(route, status = 200, marker = 'id="primary"') {
  try {
    const response = await fetch(new URL(route, base), { redirect: 'manual', signal: AbortSignal.timeout(15000) });
    assert.equal(response.status, status, `${route} HTTP status`);
    const html = await response.text();
    assert(!/Fatal error|Uncaught (?:Error|TypeError)|<b>Warning<\/b>|<b>Deprecated<\/b>/.test(html), `${route} PHP error`);
    if (marker) assert(html.includes(marker), `${route} missing ${marker}`);
    passed++;
  } catch (error) { failures.push(error.message); }
}
for (const page of structure.pages) await check(page.path);
await check('/?s=' + encodeURIComponent('Филиал'), 200, 'Поиск:');
await check('/vniipo-missing-page-for-test/', 404, 'Страница не найдена');
await check('/conferences/', 200, 'Конференции');
await check('/registration/', 200, 'Регистрация');
await check('/editor/login/', 200, 'Вход');
await check('/conferences/vniipo-integration-check/', 200, 'Интеграционная проверка конференции');
await check('/documents/', 200, 'Проверка публикации документа');
await check('/faq/', 200, 'Проверка вопроса и ответа');
await check('/branch/useful/news/page/2/', 200, 'Проверка новости');
for (const redirect of structure.redirects) {
  const response = await fetch(new URL(redirect.from, base), { redirect: 'manual' });
  try {
    assert.equal(response.status, 301);
    assert.equal(response.headers.get('location'), new URL(redirect.to, base).href);
    passed++;
  } catch (error) { failures.push(`${redirect.from}: ${error.message}`); }
}
if (failures.length) { console.error(failures.join('\n')); process.exitCode = 1; }
else console.log(`PASS: ${passed} HTTP checks: all pages, search, 404, conference archive/card, registration, login and redirects.`);

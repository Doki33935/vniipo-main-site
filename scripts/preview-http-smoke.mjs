import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';

const base = process.env.SITE_URL || 'http://localhost:8088';
const state = JSON.parse(await readFile(process.argv[2] || '.local/preview-test-state.json', 'utf8'));
const structure = JSON.parse(await readFile(new URL('../wp-content/plugins/vniipo-core/content/structure.json', import.meta.url), 'utf8'));
let passed = 0;
async function request(route, { cookie, status = 200, marker, method = 'GET' } = {}) {
  const response = await fetch(new URL(route, base), { redirect: 'manual', method, headers: cookie ? { Cookie: cookie } : {}, signal: AbortSignal.timeout(15000) });
  assert.equal(response.status, status, `${method} ${route}`);
  const text = await response.text();
  if (marker) assert(text.includes(marker), `${route}: ${marker}`);
  if (route.startsWith('/vniipo-preview/')) {
    assert(response.headers.get('x-robots-tag')?.includes('noindex'));
    assert(response.headers.get('cache-control')?.includes('no-cache'));
  }
  passed++;
  return { response, text };
}
const denied = await request('/vniipo-preview/', { status: 302 });
assert(denied.response.headers.get('location').includes('wp-login.php'));
await request('/vniipo-preview/', { cookie: state.editor.header, status: 403, marker: 'только администраторам' });
await request('/vniipo-preview/', { cookie: state.admin.header, method: 'POST', status: 405 });
await request('/vniipo-preview/', { cookie: state.admin.header, method: 'PUT', status: 405 });
await request('/vniipo-preview/', { cookie: state.admin.header, method: 'DELETE', status: 405 });
await request('/vniipo-preview/', { cookie: state.admin.header, method: 'HEAD' });
await request('/', { marker: 'class="site-header"' });
await request('/', { cookie: state.admin.header, marker: 'class="site-header"' });
for (const page of structure.pages) {
  const result = await request('/vniipo-preview' + page.path, { cookie: state.admin.header, marker: 'Закрытый предпросмотр нового сайта' });
  assert(result.text.includes('class="vn-header"'));
}
for (const route of ['/conferences/', '/conferences/vniipo-integration-check/', '/branch/useful/news/page/2/']) {
  await request('/vniipo-preview' + route, { cookie: state.admin.header, marker: 'id="primary"' });
}
await request('/vniipo-preview/registration/', { cookie: state.admin.header, marker: 'Формы в предпросмотре отключены' });
await request('/vniipo-preview/?s=' + encodeURIComponent('Проверка'), { cookie: state.admin.header, marker: 'Поиск:' });
await request('/vniipo-preview/page/2/?s=' + encodeURIComponent('Проверка'), { cookie: state.admin.header, marker: 'Поиск:' });
await request('/vniipo-preview/missing-preview-page/', { cookie: state.admin.header, status: 404 });
await request('/', { marker: 'class="site-header"' });
console.log(`PASS: ${passed} private preview checks: admin access, anonymous/editor denial, POST blocked, pages, events, pagination and unchanged public theme.`);

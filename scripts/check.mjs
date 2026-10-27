import { readFile, readdir } from 'node:fs/promises';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const read = (name) => readFile(path.join(root, name), 'utf8');
const structure = JSON.parse(await read('wp-content/plugins/vniipo-core/content/structure.json'));
const keys = new Set();
const paths = new Set();
for (const page of structure.pages) {
  assert(!keys.has(page.key), `Duplicate key: ${page.key}`);
  assert(!paths.has(page.path), `Duplicate path: ${page.path}`);
  assert(/^\/(?:[a-z0-9-]+\/)*$/.test(page.path), `Invalid path: ${page.path}`);
  assert(!/^\/(conferences|registration|editor|partnership)(\/|$)/.test(page.path), `Conference route collision: ${page.path}`);
  const parent = page.path === '/' ? '/' : path.posix.dirname(page.path.slice(0, -1)) + '/';
  assert(parent === '/' || parent === '//' || paths.has(parent), `Missing/out-of-order parent: ${page.path}`);
  assert(page.title && ((Array.isArray(page.source) && page.source[0] <= page.source[1]) || (page.source_url && new URL(page.source_url).hostname === 'oren.vniipo.ru')), `Missing source: ${page.key}`);
  for (const field of ['external', 'reference']) {
    if (page[field]) assert(new URL(page[field]).protocol === 'https:', `Invalid external resource: ${page.key}`);
  }
  keys.add(page.key); paths.add(page.path);
}
assert.equal(structure.pages.filter((page) => page.menu).length, 6);
for (const link of structure.links) {
  assert(keys.has(link.parent));
  assert(link.target ? keys.has(link.target) : link.url === '/conferences/');
}
const redirects = new Map(structure.redirects.map(({ from, to }) => [from, to]));
assert.equal(redirects.size, structure.redirects.length);
for (const [from, to] of redirects) {
  assert(!paths.has(from), `Redirect shadows page: ${from}`);
  assert(paths.has(to) || to === '/conferences/', `Redirect destination missing: ${to}`);
  assert(!redirects.has(to), `Redirect chain or cycle: ${from}`);
}
const lock = JSON.parse(await read('docs/upstream-lock.json'));
assert(/^[a-f0-9]{40}$/.test(lock.commit));
for (const [name, expected] of Object.entries(lock.sha256)) {
  const actual = createHash('sha256').update(await readFile(path.join(root, name))).digest('hex');
  assert.equal(actual, expected, `Pinned conference source modified: ${name}`);
}
async function walk(directory) {
  const files = [];
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    if (['.git', '.local', 'artifacts', 'node_modules'].includes(entry.name) || entry.name === '.env') continue;
    const name = path.join(directory, entry.name);
    files.push(...(entry.isDirectory() ? await walk(name) : [name]));
  }
  return files;
}
const files = await walk(root);
for (const name of files) {
  if (name.endsWith('.js') || name.endsWith('.mjs')) execFileSync(process.execPath, ['--check', name], { stdio: 'pipe' });
  assert(!/\.(dump|sql)(\.gz)?$/.test(name), `Database data in source: ${name}`);
  if (/\.(php|js|mjs|json|md|yaml|yml|sh|ps1|txt)$/.test(name)) {
    const content = await readFile(name, 'utf8');
    assert(!/gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{30,}|-----BEGIN (?:RSA |OPENSSH |EC )?PRIVATE KEY-----/.test(content), `Potential secret: ${name}`);
  }
}
const theme = await read('wp-content/themes/vniipo-main/style.css');
assert(theme.includes('Template: fyremezzonine-wp-theme'));
const snapshot = JSON.parse(await read('wp-content/plugins/vniipo-core/content/oren-source.json'));
const translations = JSON.parse(await read('wp-content/plugins/vniipo-core/content/interface-translations.json'));
for (const [message, locales] of Object.entries(translations)) {
  for (const locale of ['en_US', 'zh_TW']) {
    assert(typeof locales[locale] === 'string' && locales[locale].length, `Missing ${locale}: ${message}`);
    assert.deepEqual(locales[locale].match(/%\d?\$?[sd]/g) || [], message.match(/%\d?\$?[sd]/g) || [], `Format placeholders: ${message}`);
  }
}
const languageLock = JSON.parse(await read('docs/language-dependency.json'));
assert.equal(new URL(languageLock.url).hostname, 'downloads.wordpress.org');
assert(/^[a-f0-9]{64}$/.test(languageLock.sha256));
const sourceIds = new Set();
for (const record of snapshot.records) {
  assert(!sourceIds.has(record.uid), `Duplicate source UID: ${record.uid}`);
  sourceIds.add(record.uid);
  assert.equal(new URL(record.source_url).hostname, 'oren.vniipo.ru');
  assert(/^[a-f0-9]{64}$/.test(record.source_sha256));
  assert(!/<(?:script|iframe|form|input|object|embed)\b|\son[a-z]+\s*=|(?:href|src)=["']\s*(?:javascript|data):/i.test(record.html), `Unsafe source HTML: ${record.uid}`);
  if (record.kind === 'page') assert(keys.has(record.key), `Unknown source page: ${record.key}`);
  if (record.kind === 'post') assert(/^\d{4}-\d{2}-\d{2}$/.test(record.date), `Missing source date: ${record.uid}`);
}
assert.equal(snapshot.records.filter(record => record.kind === 'post').length, snapshot.archive_discovered);
let sourceImages = 0;
for (const media of Object.values(snapshot.media)) {
  if (media.status !== 'local') continue;
  assert(/^[a-f0-9]{20}\.(jpg|jpeg|png|gif|webp)$/.test(media.file));
  const bytes = await readFile(path.join(root, 'wp-content/plugins/vniipo-core/content/source-media', media.file));
  assert.equal(bytes.length, media.bytes);
  assert.equal(createHash('sha256').update(bytes).digest('hex'), media.sha256, `Source image changed: ${media.file}`);
  sourceImages++;
}
assert(!(await read('.env.example')).match(/PASSWORD=(?!CHANGE_ME)/));
console.log(`PASS: ${keys.size} pages, 6 menu entries, ${redirects.size} redirects, ${Object.keys(lock.sha256).length} pinned upstream files, ${sourceIds.size} source records, ${sourceImages} verified images; JavaScript syntax and source hygiene.`);

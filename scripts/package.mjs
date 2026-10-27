import { mkdir, readdir, copyFile, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const output = path.join(root, 'dist', 'wordpress');
const directories = ['themes/vniipo-main', 'themes/fyremezzonine-wp-theme', 'plugins/vniipo-core', 'plugins/fyremezzonine-conference-manager', 'plugins/vniipo-preview'];
const files = {};
async function copy(relative) {
  const source = path.join(root, 'wp-content', relative);
  const target = path.join(output, 'wp-content', relative);
  await mkdir(target, { recursive: true });
  for (const entry of await readdir(source, { withFileTypes: true })) {
    const name = path.posix.join(relative, entry.name);
    if (entry.isDirectory()) await copy(name);
    else {
      const dest = path.join(output, 'wp-content', name);
      await copyFile(path.join(root, 'wp-content', name), dest);
      files[`wp-content/${name}`] = createHash('sha256').update(await readFile(dest)).digest('hex');
    }
  }
}
for (const directory of directories) await copy(directory);
const version = JSON.parse(await readFile(path.join(root, 'package.json'), 'utf8')).version;
await writeFile(path.join(output, 'manifest.json'), JSON.stringify({ version, files }, null, 2) + '\n');
console.log(`Packaged ${Object.keys(files).length} files into dist/wordpress. Installation: docs/development.md`);

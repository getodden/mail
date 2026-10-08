import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

import { bundle, strip } from '../../scripts/build-editor.mjs';

test('the bundle is valid JavaScript with no leftover imports and one default export', () => {
    const { 'mail-editor.js': js } = bundle();
    const file = join(mkdtempSync(join(tmpdir(), 'mail-editor-')), 'bundle.mjs');

    writeFileSync(file, js);
    // A duplicate top-level name between two modules is a syntax error here.
    assert.doesNotThrow(() => execFileSync(process.execPath, ['--check', file], { stdio: 'pipe' }));

    assert.equal((js.match(/^import\s/gm) ?? []).length, 0);
    assert.equal((js.match(/^export\s/gm) ?? []).length, 1);
    assert.match(js, /^export default function oddenMailEditorField/m);
    assert.match(js, /const STYLESHEET = null;/);
});

test('the committed bundle is up to date', () => {
    const files = bundle();

    for (const [name, content] of Object.entries(files)) {
        assert.equal(readFileSync(new URL(`../../resources/dist/${name}`, import.meta.url), 'utf8'), content, `${name} is stale: run "node scripts/build-editor.mjs"`);
    }
});

test('the bundler strips imports and exports', () => {
    const out = strip("import { a } from './a.js';\nimport {\n  b,\n  c,\n} from './b.js';\nexport function f() {}\nexport class C {}\nexport const x = 1;\n", 'x.js');

    assert.doesNotMatch(out, /^import/m);
    assert.match(out, /^function f\(\)/m);
    assert.match(out, /^class C/m);
    assert.match(out, /^const x = 1;/m);
});

test('the bundler refuses export lists, which it cannot remove safely', () => {
    assert.throws(() => strip('const a = 1;\nexport { a };\n', 'x.js'), /export \{ \.\.\. \}/);
});

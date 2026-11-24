import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

import { DEFAULT_STRINGS, translate } from '../../resources/js/editor/i18n.js';

test('a provided translation is shown, with its placeholders filled', () => {
    const strings = { undo: 'تراجع', block_added: '{label} أضيف في الموضع {position} من {total}.' };

    assert.equal(translate(strings, 'undo'), 'تراجع');
    assert.equal(translate(strings, 'block_added', { label: 'Hero', position: 2, total: 5 }), 'Hero أضيف في الموضع 2 من 5.');
});

test('a missing key, or no dictionary at all, falls back to English', () => {
    assert.equal(translate({ undo: 'تراجع' }, 'redo'), 'Redo');
    assert.equal(translate(null, 'block_deleted', { label: 'Hero' }), 'Hero deleted. Press undo to bring it back.');
    assert.equal(translate(undefined, 'add_item'), 'Add item');
    // A value that is not a string is ignored rather than shown.
    assert.equal(translate({ undo: 5 }, 'undo'), 'Undo');
});

test('a placeholder without a parameter is left as it is', () => {
    assert.equal(translate({}, 'move_item_up'), 'Move item {position} up');
});

test('every string the editor asks for has an English default', () => {
    for (const file of ['odden-mail-editor.js', 'fields.js']) {
        const code = readFileSync(new URL(`../../resources/js/editor/${file}`, import.meta.url), 'utf8');
        const keys = [...code.matchAll(/\bt\('([a-z_]+)'/g)].map((match) => match[1]);

        assert.ok(keys.length > 0, file);

        for (const key of keys) {
            assert.ok(key in DEFAULT_STRINGS, `${file} uses "${key}", which has no default`);
        }
    }
});

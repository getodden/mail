import test from 'node:test';
import assert from 'node:assert/strict';

import { parseLines } from '../../resources/js/editor/fields.js';
import { setField } from '../../resources/js/editor/model.js';

test('a textarea becomes a list of its non-empty lines, trimmed', () => {
    assert.deepEqual(parseLines('Up to 5,000 contacts\n  A/B testing  \r\n\n\nDedicated IP\n'), ['Up to 5,000 contacts', 'A/B testing', 'Dedicated IP']);
});

test('an empty textarea is no list, so the slot falls back to its own default', () => {
    assert.equal(parseLines(''), '');
    assert.equal(parseLines(' \n \n'), '');

    const slots = [{ id: 'a', type: 'data_table', data: { headers: ['A', 'B'] } }];

    assert.equal('headers' in setField(slots, 'a', ['headers'], parseLines(''))[0].data, false);
});

test('a list of strings inside a list item is set by its path', () => {
    const slots = [{ id: 'a', type: 'data_table', data: { rows: [{ cells: ['x'] }, { cells: ['y'] }] } }];
    const next = setField(slots, 'a', ['rows', 1, 'cells'], parseLines('Pro\nEnterprise'));

    assert.deepEqual(next[0].data.rows, [{ cells: ['x'] }, { cells: ['Pro', 'Enterprise'] }]);
    assert.deepEqual(slots[0].data.rows[1].cells, ['y']);
});

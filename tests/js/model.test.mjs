import test from 'node:test';
import assert from 'node:assert/strict';

import {
    addItem,
    createSlot,
    defaultsFor,
    dropLineY,
    duplicateSlot,
    fromDocument,
    insertAt,
    insertionIndex,
    moveBy,
    moveItem,
    moveTo,
    removeItem,
    removeSlot,
    setField,
    toDocument,
} from '../../resources/js/editor/model.js';

const slot = (id, type = 'hero', data = {}) => ({ id, type, data });
const ids = (slots) => slots.map((s) => s.id);

test('defaults come from the schema, with the default number of list items', () => {
    const schema = {
        type: 'features',
        fields: [
            { key: 'heading', type: 'text', default: null },
            { key: 'bg_color', type: 'color', default: '#f8fafc' },
            { key: 'items', type: 'items', default_items: 2, items: [{ key: 'icon', type: 'text', default: '⚡' }, { key: 'title', type: 'text', default: null }] },
        ],
    };

    assert.deepEqual(defaultsFor(schema), { bg_color: '#f8fafc', items: [{ icon: '⚡' }, { icon: '⚡' }] });
    assert.equal(createSlot(schema).type, 'features');
});

test('a document round-trips without ids and keeps visibility rules and other keys', () => {
    const document = { subject: 'Hi', slots: [{ type: 'hero', data: { title: 'A' }, visibility: { field: 'x' } }, { type: 'divider', data: {} }] };
    const slots = fromDocument(document);

    assert.equal(slots.length, 2);
    assert.ok(slots.every((s) => typeof s.id === 'string'));
    assert.notEqual(slots[0].id, slots[1].id);
    assert.deepEqual(toDocument(slots, { subject: 'Hi' }), document);
});

test('inserting clamps the position', () => {
    const list = [slot('a'), slot('b')];

    assert.deepEqual(ids(insertAt(list, 0, slot('x'))), ['x', 'a', 'b']);
    assert.deepEqual(ids(insertAt(list, 1, slot('x'))), ['a', 'x', 'b']);
    assert.deepEqual(ids(insertAt(list, 99, slot('x'))), ['a', 'b', 'x']);
    assert.deepEqual(ids(insertAt(list, -5, slot('x'))), ['x', 'a', 'b']);
});

test('moving to a drop gap handles gaps above and below the slot', () => {
    const list = [slot('a'), slot('b'), slot('c'), slot('d')];

    assert.deepEqual(ids(moveTo(list, 'a', 3)), ['b', 'c', 'a', 'd']);
    assert.deepEqual(ids(moveTo(list, 'a', 4)), ['b', 'c', 'd', 'a']);
    assert.deepEqual(ids(moveTo(list, 'd', 0)), ['d', 'a', 'b', 'c']);
    assert.deepEqual(ids(moveTo(list, 'c', 1)), ['a', 'c', 'b', 'd']);
    // The gap right above or right below the slot itself is a no-op.
    assert.deepEqual(ids(moveTo(list, 'b', 1)), ['a', 'b', 'c', 'd']);
    assert.deepEqual(ids(moveTo(list, 'b', 2)), ['a', 'b', 'c', 'd']);
    assert.equal(moveTo(list, 'missing', 0), list);
});

test('moving one step stops at the ends', () => {
    const list = [slot('a'), slot('b'), slot('c')];

    assert.deepEqual(ids(moveBy(list, 'b', -1)), ['b', 'a', 'c']);
    assert.deepEqual(ids(moveBy(list, 'b', 1)), ['a', 'c', 'b']);
    assert.equal(moveBy(list, 'a', -1), list);
    assert.equal(moveBy(list, 'c', 1), list);
});

test('removing and duplicating', () => {
    const list = [slot('a', 'hero', { title: 'T' }), slot('b')];
    const { slots, copy } = duplicateSlot(list, 'a');

    assert.deepEqual(ids(removeSlot(list, 'a')), ['b']);
    assert.equal(slots.length, 3);
    assert.equal(slots[1].id, copy.id);
    assert.notEqual(copy.id, 'a');
    assert.deepEqual(copy.data, { title: 'T' });

    copy.data.title = 'changed';
    assert.equal(slots[0].data.title, 'T', 'a copy must not share data with the original');
    assert.equal(duplicateSlot(list, 'missing').copy, null);
});

test('setting a field never changes the old list, and an empty value removes the key', () => {
    const list = [slot('a', 'hero', { title: 'Old', badge: 'NEW' })];
    const next = setField(list, 'a', ['title'], 'New');
    const cleared = setField(next, 'a', ['badge'], '');

    assert.equal(list[0].data.title, 'Old');
    assert.equal(next[0].data.title, 'New');
    assert.deepEqual(cleared[0].data, { title: 'New' });
    assert.deepEqual(setField(list, 'a', ['enabled'], false)[0].data.enabled, false);
});

test('list items can be set, added, removed and moved', () => {
    let list = [slot('a', 'features', { items: [{ title: 'one' }, { title: 'two' }] })];

    list = setField(list, 'a', ['items', 1, 'title'], 'TWO');
    assert.equal(list[0].data.items[1].title, 'TWO');

    list = addItem(list, 'a', 'items', { title: 'three' });
    assert.equal(list[0].data.items.length, 3);

    list = moveItem(list, 'a', 'items', 2, -1);
    assert.deepEqual(list[0].data.items.map((i) => i.title), ['one', 'three', 'TWO']);
    assert.equal(moveItem(list, 'a', 'items', 0, -1)[0], list[0]);

    list = removeItem(list, 'a', 'items', 0);
    assert.deepEqual(list[0].data.items.map((i) => i.title), ['three', 'TWO']);
});

test('the drop index counts the slots whose middle is above the pointer', () => {
    const rects = [{ top: 0, bottom: 100 }, { top: 100, bottom: 150 }, { top: 150, bottom: 300 }];

    assert.equal(insertionIndex(rects, -20), 0);
    assert.equal(insertionIndex(rects, 40), 0);
    assert.equal(insertionIndex(rects, 60), 1);
    assert.equal(insertionIndex(rects, 130), 2);
    assert.equal(insertionIndex(rects, 160), 2);
    assert.equal(insertionIndex(rects, 500), 3);
    assert.equal(insertionIndex([], 10), 0);
});

test('the drop line sits on the slot boundary', () => {
    const rects = [{ top: 0, bottom: 100 }, { top: 100, bottom: 150 }];

    assert.equal(dropLineY(rects, 0), 0);
    assert.equal(dropLineY(rects, 1), 100);
    assert.equal(dropLineY(rects, 2), 150);
    assert.equal(dropLineY([], 0), 0);
});

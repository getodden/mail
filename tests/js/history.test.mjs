import test from 'node:test';
import assert from 'node:assert/strict';

import { History } from '../../resources/js/editor/history.js';

test('undo and redo walk through the states', () => {
    const history = new History('a');

    history.push('b');
    history.push('c');

    assert.equal(history.present, 'c');
    assert.equal(history.undo(), 'b');
    assert.equal(history.undo(), 'a');
    assert.equal(history.canUndo, false);
    assert.equal(history.undo(), 'a');
    assert.equal(history.redo(), 'b');
    assert.equal(history.redo(), 'c');
    assert.equal(history.canRedo, false);
});

test('a new edit after an undo drops the redo states', () => {
    const history = new History('a');

    history.push('b');
    history.undo();
    history.push('c');

    assert.equal(history.canRedo, false);
    assert.equal(history.undo(), 'a');
});

test('typing in one field is one undo step, a pause or another field is a new one', () => {
    const history = new History('a');

    history.push('ab', 'title', 1000);
    history.push('abc', 'title', 1200);
    history.push('abcd', 'title', 1500);
    assert.equal(history.undo(), 'a');

    history.redo();
    history.push('abcde', 'title', 5000);
    assert.equal(history.undo(), 'abcd');

    history.push('x', 'subtitle', 5100);
    assert.equal(history.undo(), 'abcd');
});

test('pushing the same state again does nothing', () => {
    const history = new History('a');

    history.push('a');
    assert.equal(history.canUndo, false);
});

test('the history is limited', () => {
    const history = new History(0, 3);

    for (let i = 1; i <= 10; i += 1) {
        history.push(i);
    }

    history.undo();
    history.undo();
    history.undo();
    assert.equal(history.present, 7);
    assert.equal(history.canUndo, false);
});

test('reset forgets everything', () => {
    const history = new History('a');

    history.push('b');
    history.reset('z');

    assert.equal(history.present, 'z');
    assert.equal(history.canUndo, false);
    assert.equal(history.canRedo, false);
});

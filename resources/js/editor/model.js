// The editor's document model: an ordered list of slots, `{id, type, data, visibility?}`. Every function returns a new
// list and never changes its argument, so the history can keep snapshots cheaply.

let counter = 0;

/** A short id, unique within the page. Ids live only in the editor and are removed when the document is exported. */
export function uid() {
    counter += 1;

    return `s${Date.now().toString(36)}${counter.toString(36)}`;
}

/** The `data` of a new slot of a schema type: every field default, and the default number of list items. */
export function defaultsFor(slotSchema) {
    const data = {};

    for (const field of slotSchema.fields) {
        if (field.type === 'items') {
            data[field.key] = Array.from({ length: field.default_items ?? 0 }, () => defaultsForFields(field.items));
        } else if (field.default !== null && field.default !== undefined) {
            data[field.key] = field.default;
        }
    }

    return data;
}

function defaultsForFields(fields) {
    const data = {};

    for (const field of fields) {
        if (field.default !== null && field.default !== undefined) {
            data[field.key] = field.default;
        }
    }

    return data;
}

export function createSlot(slotSchema) {
    return { id: uid(), type: slotSchema.type, data: defaultsFor(slotSchema) };
}

/** Turn an exported document (`{slots: [{type, data, visibility?}]}`) into editor slots with ids. */
export function fromDocument(document) {
    const slots = Array.isArray(document?.slots) ? document.slots : [];

    return slots.map((slot) => {
        const next = { id: uid(), type: slot.type, data: structuredClone(slot.data ?? {}) };

        if (slot.visibility) {
            next.visibility = structuredClone(slot.visibility);
        }

        return next;
    });
}

/** The inverse of fromDocument: drop the ids, keep everything else of the document untouched. */
export function toDocument(slots, base = {}) {
    return {
        ...base,
        slots: slots.map((slot) => {
            const out = { type: slot.type, data: structuredClone(slot.data) };

            if (slot.visibility) {
                out.visibility = structuredClone(slot.visibility);
            }

            return out;
        }),
    };
}

export function indexOfSlot(slots, id) {
    return slots.findIndex((slot) => slot.id === id);
}

/** Insert at `index` (0 is the top, `slots.length` the bottom). */
export function insertAt(slots, index, slot) {
    const at = Math.max(0, Math.min(index, slots.length));

    return [...slots.slice(0, at), slot, ...slots.slice(at)];
}

/**
 * Move a slot so that it sits where `targetIndex` pointed in the list *before* the move, which is what a drop
 * position is: "the gap before slot N". Moving to the gap right above or right below itself changes nothing.
 */
export function moveTo(slots, id, targetIndex) {
    const from = indexOfSlot(slots, id);

    if (from === -1) {
        return slots;
    }

    const without = slots.filter((slot) => slot.id !== id);
    const at = Math.max(0, Math.min(targetIndex > from ? targetIndex - 1 : targetIndex, without.length));

    return insertAt(without, at, slots[from]);
}

/** Move one step: -1 up, +1 down. */
export function moveBy(slots, id, step) {
    const from = indexOfSlot(slots, id);
    const to = from + step;

    if (from === -1 || to < 0 || to >= slots.length) {
        return slots;
    }

    const next = [...slots];
    [next[from], next[to]] = [next[to], next[from]];

    return next;
}

export function removeSlot(slots, id) {
    return slots.filter((slot) => slot.id !== id);
}

/** A copy placed right after the original, with a new id. */
export function duplicateSlot(slots, id) {
    const from = indexOfSlot(slots, id);

    if (from === -1) {
        return { slots, copy: null };
    }

    const copy = { ...structuredClone(slots[from]), id: uid() };

    return { slots: insertAt(slots, from + 1, copy), copy };
}

/**
 * Set a value at a path in a slot's data: `['title']`, or `['items', 1, 'title']` for a list item. An empty string,
 * null or undefined removes the key, so the slot's view falls back to its own default.
 */
export function setField(slots, id, path, value) {
    return slots.map((slot) => {
        if (slot.id !== id) {
            return slot;
        }

        const data = structuredClone(slot.data);
        let target = data;

        for (const step of path.slice(0, -1)) {
            target = target[step] ??= {};
        }

        const last = path[path.length - 1];

        if (value === '' || value === null || value === undefined) {
            delete target[last];
        } else {
            target[last] = value;
        }

        return { ...slot, data };
    });
}

export function addItem(slots, id, key, item) {
    return slots.map((slot) => (slot.id === id ? { ...slot, data: { ...slot.data, [key]: [...(slot.data[key] ?? []), item] } } : slot));
}

export function removeItem(slots, id, key, index) {
    return slots.map((slot) => (slot.id === id ? { ...slot, data: { ...slot.data, [key]: (slot.data[key] ?? []).filter((_, i) => i !== index) } } : slot));
}

export function moveItem(slots, id, key, index, step) {
    return slots.map((slot) => {
        if (slot.id !== id) {
            return slot;
        }

        const items = [...(slot.data[key] ?? [])];
        const to = index + step;

        if (to < 0 || to >= items.length) {
            return slot;
        }

        [items[index], items[to]] = [items[to], items[index]];

        return { ...slot, data: { ...slot.data, [key]: items } };
    });
}

/**
 * Where a pointer at `y` would drop: the number of slots whose vertical middle is above `y`. `rects` are the slots'
 * `{top, bottom}` in the same coordinates as `y`, in document order.
 */
export function insertionIndex(rects, y) {
    let index = 0;

    for (const rect of rects) {
        if (y > (rect.top + rect.bottom) / 2) {
            index += 1;
        }
    }

    return index;
}

/** The y of the line that shows a drop at `index`: the top of that slot, or the bottom of the last one. */
export function dropLineY(rects, index) {
    if (rects.length === 0) {
        return 0;
    }

    return index >= rects.length ? rects[rects.length - 1].bottom : rects[index].top;
}

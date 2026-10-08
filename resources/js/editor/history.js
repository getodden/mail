// Undo and redo for the editor. States are immutable slot lists, so each history entry is just a reference.

export class History {
    #past = [];
    #future = [];
    #present;
    #limit;
    #lastKey = null;
    #lastAt = 0;

    constructor(initial, limit = 200) {
        this.#present = initial;
        this.#limit = limit;
    }

    get present() {
        return this.#present;
    }

    get canUndo() {
        return this.#past.length > 0;
    }

    get canRedo() {
        return this.#future.length > 0;
    }

    /**
     * Record a new state. Edits that share a `coalesceKey` and come within `windowMs` of each other (typing in one
     * field) replace each other instead of piling up one undo step per keystroke.
     */
    push(next, coalesceKey = null, now = Date.now(), windowMs = 800) {
        if (next === this.#present) {
            return;
        }

        const coalesce = coalesceKey !== null && coalesceKey === this.#lastKey && now - this.#lastAt <= windowMs;

        if (!coalesce) {
            this.#past.push(this.#present);

            if (this.#past.length > this.#limit) {
                this.#past.shift();
            }
        }

        this.#present = next;
        this.#future = [];
        this.#lastKey = coalesceKey;
        this.#lastAt = now;
    }

    undo() {
        if (!this.canUndo) {
            return this.#present;
        }

        this.#future.push(this.#present);
        this.#present = this.#past.pop();
        this.#lastKey = null;

        return this.#present;
    }

    redo() {
        if (!this.canRedo) {
            return this.#present;
        }

        this.#past.push(this.#present);
        this.#present = this.#future.pop();
        this.#lastKey = null;

        return this.#present;
    }

    /** Start over from a new state (the host replaced the whole document). */
    reset(initial) {
        this.#past = [];
        this.#future = [];
        this.#present = initial;
        this.#lastKey = null;
    }
}

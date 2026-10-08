// The property panel: one control for each field of a slot's schema.

import { h } from './dom.js';

const HEX = /^#[0-9a-fA-F]{6}$/;

/**
 * @param {object} options
 * @param {object} options.field  A field of the slot schema.
 * @param {*} options.value  The current value (undefined when unset).
 * @param {Array<string|number>} options.path  Where the value lives in the slot's data.
 * @param {string} options.idPrefix  Makes control ids unique.
 * @param {(path: Array<string|number>, value: *) => void} options.onChange
 * @param {(action: string, path: Array<string|number>, index: number) => void} options.onItem  List actions: add, remove, up, down.
 */
export function renderField(options) {
    const { field, path } = options;
    const id = `${options.idPrefix}-${path.join('-')}`;

    if (field.type === 'items') {
        return renderItems(options, id);
    }

    const helpId = field.help ? `${id}-help` : null;
    const control = renderControl(options, id, helpId);
    const label = h('label', { for: id, class: 'ome-label' }, field.label, field.required ? h('span', { class: 'ome-required', 'aria-hidden': 'true' }, ' *') : null);

    if (field.type === 'toggle') {
        return h('div', { class: 'ome-field ome-field-toggle' }, control, label, field.help ? h('p', { class: 'ome-help', id: helpId }, field.help) : null);
    }

    return h('div', { class: 'ome-field' }, label, control, field.help ? h('p', { class: 'ome-help', id: helpId }, field.help) : null);
}

function describedBy(helpId) {
    return helpId ? { 'aria-describedby': helpId } : {};
}

function renderControl({ field, value, path, onChange }, id, helpId) {
    const shared = { id, ...describedBy(helpId), 'aria-required': field.required ? 'true' : false };

    switch (field.type) {
        case 'textarea':
        case 'rich_text':
            return h('textarea', {
                ...shared,
                class: 'ome-input ome-textarea',
                rows: field.rows > 0 ? field.rows : field.type === 'rich_text' ? 8 : 3,
                placeholder: field.placeholder ?? false,
                value: value ?? '',
                oninput: (event) => onChange(path, event.target.value),
            });

        case 'select':
            return renderSelect({ field, value, path, onChange }, shared);

        case 'toggle':
            return h('input', { ...shared, type: 'checkbox', role: 'switch', class: 'ome-switch', checked: value === true, onchange: (event) => onChange(path, event.target.checked) });

        case 'number':
            return h('input', {
                ...shared,
                type: 'number',
                class: 'ome-input',
                placeholder: field.placeholder ?? false,
                value: value ?? '',
                oninput: (event) => onChange(path, event.target.value === '' ? '' : Number(event.target.value)),
            });

        case 'color':
            return renderColor({ field, value, path, onChange }, shared);

        default:
            return h('input', {
                ...shared,
                type: 'text',
                class: 'ome-input',
                placeholder: field.placeholder ?? false,
                autocomplete: 'off',
                spellcheck: field.type === 'text' ? 'true' : 'false',
                value: value ?? '',
                oninput: (event) => onChange(path, event.target.value),
            });
    }
}

function renderSelect({ field, value, path, onChange }, shared) {
    const options = [...field.options];
    const known = options.some((option) => String(option.value) === String(value));

    // A stored value the schema does not list stays selectable, so opening a slot never silently changes it.
    if (value !== undefined && value !== null && value !== '' && !known) {
        options.push({ value, label: `${value} (custom)` });
    }

    const select = h(
        'select',
        {
            ...shared,
            class: 'ome-input',
            onchange: (event) => {
                const chosen = options.find((option) => String(option.value) === event.target.value);

                onChange(path, chosen ? chosen.value : event.target.value);
            },
        },
        options.map((option) => h('option', { value: String(option.value), selected: String(option.value) === String(value ?? field.default ?? '') }, option.label)),
    );

    return select;
}

function renderColor({ field, value, path, onChange }, shared) {
    const text = h('input', {
        ...shared,
        type: 'text',
        class: 'ome-input ome-color-text',
        placeholder: field.default ?? 'Not set',
        autocomplete: 'off',
        spellcheck: 'false',
        value: value ?? '',
    });
    const picker = h('input', {
        type: 'color',
        class: 'ome-color',
        'aria-label': `${field.label}: pick a color`,
        value: HEX.test(value ?? '') ? value : HEX.test(field.default ?? '') ? field.default : '#000000',
        oninput: (event) => {
            text.value = event.target.value;
            onChange(path, event.target.value);
        },
    });

    text.addEventListener('input', () => {
        if (HEX.test(text.value)) {
            picker.value = text.value;
        }

        onChange(path, text.value.trim());
    });

    return h('div', { class: 'ome-color-row' }, picker, text);
}

function renderItems(options, id) {
    const { field, value, path, onItem } = options;
    const items = Array.isArray(value) ? value : [];

    return h(
        'fieldset',
        { class: 'ome-items' },
        h('legend', { class: 'ome-label' }, field.label),
        items.map((item, index) =>
            h(
                'div',
                { class: 'ome-item', role: 'group', 'aria-label': `${field.label} ${index + 1} of ${items.length}` },
                h(
                    'div',
                    { class: 'ome-item-bar' },
                    h('span', { class: 'ome-item-title' }, `${index + 1}`),
                    h('button', { type: 'button', class: 'ome-icon-button', 'aria-label': `Move item ${index + 1} up`, disabled: index === 0, onclick: () => onItem('up', path, index) }, '↑'),
                    h('button', { type: 'button', class: 'ome-icon-button', 'aria-label': `Move item ${index + 1} down`, disabled: index === items.length - 1, onclick: () => onItem('down', path, index) }, '↓'),
                    h('button', { type: 'button', class: 'ome-icon-button', 'aria-label': `Remove item ${index + 1}`, onclick: () => onItem('remove', path, index) }, '✕'),
                ),
                field.items.map((subField) =>
                    renderField({
                        ...options,
                        field: subField,
                        value: item?.[subField.key],
                        path: [...path, index, subField.key],
                        idPrefix: `${id}-${index}`,
                    }),
                ),
            ),
        ),
        h('button', { type: 'button', class: 'ome-button ome-button-quiet', onclick: () => onItem('add', path, items.length) }, `Add item`),
    );
}

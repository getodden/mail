// A tiny element builder, so the editor needs no framework.

/**
 * h('button', {class: 'x', onclick: fn, 'aria-label': 'Go'}, 'Text', childElement)
 * Keys starting with "on" become event listeners; `false`, null and undefined attributes are skipped.
 */
export function h(tag, attrs = {}, ...children) {
    const element = document.createElement(tag);

    for (const [name, value] of Object.entries(attrs ?? {})) {
        if (value === false || value === null || value === undefined) {
            continue;
        }

        if (name.startsWith('on') && typeof value === 'function') {
            element.addEventListener(name.slice(2), value);
        } else if (name === 'class') {
            element.className = value;
        } else if (name === 'value' || name === 'checked' || name === 'selected' || name === 'disabled') {
            element[name] = value;
        } else {
            element.setAttribute(name, value === true ? '' : String(value));
        }
    }

    for (const child of children.flat()) {
        if (child === null || child === undefined || child === false) {
            continue;
        }

        element.append(child instanceof Node ? child : document.createTextNode(String(child)));
    }

    return element;
}

export function clear(element) {
    while (element.firstChild) {
        element.removeChild(element.firstChild);
    }
}

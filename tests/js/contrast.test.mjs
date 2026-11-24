import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync(new URL('../../resources/js/editor/editor.css', import.meta.url), 'utf8');

function variable(name) {
    const match = css.match(new RegExp(`${name}:\\s*(#[0-9a-f]{6})\\s*;`));
    assert.ok(match, `${name} is missing from editor.css`);
    return match[1];
}

// WCAG 2.2 relative luminance (sRGB channels linearized) and contrast ratio.
function luminance(hex) {
    const [r, g, b] = [1, 3, 5]
        .map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
        .map((c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrast(a, b) {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (light + 0.05) / (dark + 0.05);
}

test('muted text meets WCAG 2.2 SC 1.4.3 (4.5:1) on every editor background', () => {
    const muted = variable('--ome-muted');

    for (const name of ['--ome-canvas', '--ome-line', '--ome-surface']) {
        const ratio = contrast(muted, variable(name));
        assert.ok(ratio >= 4.5, `${muted} on ${name} is ${ratio.toFixed(2)}:1, below 4.5:1`);
    }
});

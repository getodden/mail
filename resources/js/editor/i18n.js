// The editor's interface strings. A host passes a dictionary (`strings`); a missing key falls back to the English text
// here. Keep this list in step with `MailEditor::DEFAULT_STRINGS` (a test compares them).

export const DEFAULT_STRINGS = {
    'undo': 'Undo',
    'redo': 'Redo',
    'undone': 'Undone.',
    'redone': 'Redone.',
    'desktop': 'Desktop',
    'mobile': 'Mobile',
    'search_blocks': 'Search blocks',
    'no_match': 'No block matches your search.',
    'tab_blocks': 'Blocks',
    'tab_structure': 'Structure',
    'left_panel': 'Left panel',
    'blocks_and_structure': 'Blocks and structure',
    'email_preview': 'Email preview',
    'selected_block': 'Selected block',
    'empty': 'Drag a block here, or choose one from the list on the left.',
    'block_settings': 'Block settings',
    'select_a_block': 'Select a block in the email or in the structure list to change it.',
    'not_editable': 'This kind of block cannot be edited here yet. You can still move, duplicate and delete it, and its content is kept as it is.',
    'style_options': 'Style options',
    'loading': 'Loading…',
    'updating_preview': 'Updating preview…',
    'schema_failed': 'The schema could not be loaded ({status}).',
    'preview_failed': 'The preview could not be rendered ({status}).',
    'drag_to_move': 'Drag to move',
    'drag_label': 'Drag {label} to move it. Or use the move buttons.',
    'move_up': 'Move up',
    'move_down': 'Move down',
    'duplicate': 'Duplicate',
    'delete': 'Delete',
    'move_up_label': 'Move {label} up',
    'move_down_label': 'Move {label} down',
    'duplicate_label': 'Duplicate {label}',
    'delete_label': 'Delete {label}',
    'block_added': '{label} added at position {position} of {total}.',
    'block_moved': '{label} moved to position {position} of {total}.',
    'block_duplicated': 'Duplicated. The copy is at position {position} of {total}.',
    'block_deleted': '{label} deleted. Press undo to bring it back.',
    'move_cancelled': 'Move cancelled.',
    'item_added': 'List item added.',
    'item_removed': 'List item removed.',
    'item_moved': 'List item moved.',
    'palette_label': '{label}. Add to the email.',
    'palette_hint': 'Click to add, or drag onto the email.',
    'structure_label': '{label}. Block {position} of {total}.',
    'item_label': '{label} {position} of {total}',
    'move_item_up': 'Move item {position} up',
    'move_item_down': 'Move item {position} down',
    'remove_item': 'Remove item {position}',
    'add_item': 'Add item',
    'custom_option': '{value} (custom)',
    'not_set': 'Not set',
    'pick_color': '{label}: pick a color',
};

/** The text for `key` with `{name}` placeholders filled from `params`: the host's string, else the English one. */
export function translate(strings, key, params = {}) {
    const text = typeof strings?.[key] === 'string' ? strings[key] : (DEFAULT_STRINGS[key] ?? key);

    return text.replace(/\{(\w+)\}/g, (placeholder, name) => (name in params ? String(params[name]) : placeholder));
}

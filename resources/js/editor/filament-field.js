// The Alpine component behind the Filament field (`MailEditor`): it binds the form state to <odden-mail-editor> and
// asks the field's PHP class for the preview, so the editor needs no routes and Filament's own authorization applies.
//
// Only the bundled build uses this file (see scripts/build-editor.mjs); it relies on the editor being in the same bundle.

/** Filament keeps builder items keyed by id; the editor wants a list of `{type, data}`. */
function toList(state) {
    if (Array.isArray(state)) {
        return state;
    }

    return state && typeof state === 'object' ? Object.values(state) : [];
}

export default function oddenMailEditorField({ state, key, schema, theme, disabled }) {
    return {
        state,
        lastEmitted: null,

        init() {
            const editor = this.$refs.editor;

            editor.adapter = {
                loadSchema: async () => schema,
                preview: async (document) => this.$wire.callSchemaComponentMethod(key, 'renderPreview', { document }),
            };

            editor.value = { ...(theme && Object.keys(theme).length > 0 ? { theme } : {}), slots: toList(this.state) };

            editor.addEventListener('change', (event) => {
                const slots = event.detail.slots;

                this.lastEmitted = JSON.stringify(slots);
                this.state = slots;
            });

            // The form changed the state itself (a preset was applied, the record was reloaded): show it.
            this.$watch('state', (value) => {
                const slots = toList(value);

                if (JSON.stringify(slots) === this.lastEmitted) {
                    return;
                }

                editor.value = { ...(theme && Object.keys(theme).length > 0 ? { theme } : {}), slots };
            });

            if (disabled) {
                editor.setAttribute('inert', '');
            }
        },
    };
}

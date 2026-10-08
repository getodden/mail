<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Odden Mail editor demo</title>
    <style>body { margin: 0; padding: 16px; font-family: system-ui, sans-serif; background: #fff; }</style>
    <script type="module" src="/editor/odden-mail-editor.js"></script>
</head>
<body>
    <odden-mail-editor id="editor" schema-url="/mail-builder/editor/schema" preview-url="/mail-builder/editor/preview"></odden-mail-editor>

    <script type="module">
        const editor = document.getElementById('editor');
        editor.value = {
            subject: 'Demo',
            slots: [
                { type: 'header', data: { brand_name: 'Acme Studio', tagline: 'Product updates' } },
                { type: 'hero', data: { title: 'Meet the new editor', subtitle: 'Drag blocks, edit them, send.', button_text: 'Read more', button_url: 'https://example.com' } },
                { type: 'body_text', data: { content: '<p>Hi @{{contact.first_name}}, here is what is new this month.</p>' } },
                { type: 'features', data: { heading: 'Why you will like it', items: [{ icon: '⚡', title: 'Fast', text: 'Edits show up as you type.' }, { icon: '🔒', title: 'Safe', text: 'The preview is the real email.' }] } },
                { type: 'footer', data: { company_name: 'Acme Studio', address: '123 Main Street, Springfield', unsubscribe_url: '@{{unsubscribe_url}}' } },
            ],
        };
        editor.addEventListener('change', (event) => { window.lastChange = event.detail; });
    </script>
</body>
</html>

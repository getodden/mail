<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// A demo of the drag-and-drop editor for development: `vendor/bin/testbench serve`, then open the home page.
// The editor's own routes are switched on in testbench.yaml.

Route::get('/', fn () => view('editor-demo'));

// Serve the editor's files straight from resources, so there is nothing to publish while developing.
Route::get('/editor/{file}', function (string $file) {
    abort_unless(in_array($file, ['odden-mail-editor.js', 'model.js', 'history.js', 'fields.js', 'dom.js', 'editor.css'], true), 404);

    return response(File::get(dirname(__DIR__, 2).'/resources/js/editor/'.$file), 200, [
        'Content-Type' => str_ends_with($file, '.css') ? 'text/css' : 'text/javascript',
        'Cache-Control' => 'no-store',
    ]);
});

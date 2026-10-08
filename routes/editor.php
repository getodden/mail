<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Odden\MailBuilder\Http\Controllers\EditorController;

Route::get('schema', [EditorController::class, 'schema'])->name('mail-builder.editor.schema');
Route::post('preview', [EditorController::class, 'preview'])->name('mail-builder.editor.preview');

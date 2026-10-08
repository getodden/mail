<?php

declare(strict_types=1);

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;
use Odden\MailBuilder\Filament\Components\MailEditor;

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

// The Filament field, inside a real Livewire form: `/filament`. Run `vendor/bin/testbench filament:assets` once first.
Route::get('/filament', function () {
    return view('filament-demo');
});

if (! class_exists('WorkbenchFilamentDemo')) {
    class WorkbenchFilamentDemo extends Component implements HasForms
    {
        use InteractsWithForms;

        /** @var array<string, mixed>|null */
        public ?array $data = [];

        public function mount(): void
        {
            $this->form->fill(['slots' => [
                ['type' => 'header', 'data' => ['brand_name' => 'Acme Studio', 'tagline' => 'Product updates']],
                ['type' => 'hero', 'data' => ['title' => 'Edit me inside Filament', 'subtitle' => 'The form state is the same list of slots.']],
                ['type' => 'body_text', 'data' => ['content' => '<p>Hello there.</p>']],
                ['type' => 'testimonial', 'data' => ['quote' => 'A block the editor cannot edit yet is kept.', 'author' => 'Someone']],
            ]]);
        }

        public function form(Schema $schema): Schema
        {
            return $schema->components([MailEditor::make('slots')->label('Email content')])->statePath('data');
        }

        public function save(): void
        {
            $this->dispatch('saved', slots: $this->form->getState()['slots']);
        }

        public function render(): string
        {
            return <<<'HTML'
                <div>
                    <form wire:submit="save">
                        {{ $this->form }}
                        <button type="submit" id="save" style="margin-top:12px;padding:6px 14px">Save</button>
                    </form>
                    <pre id="saved" style="background:#f1f5f9;padding:8px;overflow:auto;max-height:200px" x-data="{ text: '' }" x-on:saved.window="text = JSON.stringify($event.detail?.slots ?? $event.detail?.[0]?.slots ?? [])" x-text="text"></pre>
                </div>
                HTML;
        }
    }

    Livewire::component('workbench-filament-demo', WorkbenchFilamentDemo::class);
}

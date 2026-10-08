<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Filament\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Livewire\Attributes\Renderless;
use Odden\MailBuilder\Editor\PreviewRenderer;

/**
 * A Filament form field with the drag-and-drop email editor.
 *
 * It is a drop-in for `EmailSlotBuilder::make('slots')`: the state is the same list of slots (`{type, data}`), so it
 * saves to the same column. The preview is rendered by this class through Livewire, so it needs no routes and runs
 * under the panel's own authentication.
 *
 *     MailEditor::make('slots')->theme(['container_width' => 640])
 */
class MailEditor extends Field
{
    protected string $view = 'mail-builder::filament.mail-editor';

    /** @var array<string, mixed>|Closure */
    protected array|Closure $theme = [];

    /**
     * Theme settings for the preview (see `mail-builder.defaults`): an array, or a closure that is evaluated for every
     * preview, so it can follow the form. A closure may ask for `$get`:
     *
     *     ->theme(fn (Get $get): array => $get('theme') ?? [])
     *
     * @param  array<string, mixed>|Closure  $theme  A closure may take any of Filament's injected parameters (`$get`, `$record`, ...) and returns the theme array (or null for none).
     */
    public function theme(array|Closure $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTheme(): array
    {
        $theme = $this->evaluate($this->theme);

        return is_array($theme) ? $theme : [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (MailEditor $component, mixed $state): void {
            $component->state(self::normalizeState($state));
        });

        $this->dehydrateStateUsing(fn (mixed $state): array => self::normalizeState($state));
    }

    /**
     * Filament's Builder keeps its items keyed by id, and a column may hold either shape: make it a plain list of
     * `{type, data}` arrays, which is what the editor and the compiler read. Anything that is not a slot is dropped.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeState(mixed $state): array
    {
        if (! is_array($state)) {
            return [];
        }

        $slots = [];

        foreach (array_values($state) as $slot) {
            if (is_array($slot) && is_string($slot['type'] ?? null)) {
                $slot['data'] = is_array($slot['data'] ?? null) ? $slot['data'] : [];
                $slots[] = $slot;
            }
        }

        return $slots;
    }

    /**
     * Called from the editor in the browser to render the preview.
     *
     * @param  array<string, mixed>  $document
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function renderPreview(array $document): string
    {
        // The field's theme is applied on the server for every preview, so it wins over any theme the browser sent.
        $document['theme'] = array_merge(is_array($document['theme'] ?? null) ? $document['theme'] : [], $this->getTheme());

        return app(PreviewRenderer::class)->render($document);
    }
}

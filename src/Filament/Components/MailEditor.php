<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Filament\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Livewire\Attributes\Renderless;
use Odden\MailBuilder\Editor\PreviewRenderer;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;

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
    /**
     * Every interface string of the editor with its English text: the keys `translations()` accepts. `{name}` marks a
     * value the editor fills in. Keep in step with `DEFAULT_STRINGS` in resources/js/editor/i18n.js.
     *
     * @var array<string, string>
     */
    public const DEFAULT_STRINGS = [
        'undo' => 'Undo',
        'redo' => 'Redo',
        'undone' => 'Undone.',
        'redone' => 'Redone.',
        'desktop' => 'Desktop',
        'mobile' => 'Mobile',
        'search_blocks' => 'Search blocks',
        'no_match' => 'No block matches your search.',
        'tab_blocks' => 'Blocks',
        'tab_structure' => 'Structure',
        'left_panel' => 'Left panel',
        'blocks_and_structure' => 'Blocks and structure',
        'email_preview' => 'Email preview',
        'selected_block' => 'Selected block',
        'empty' => 'Drag a block here, or choose one from the list on the left.',
        'block_settings' => 'Block settings',
        'select_a_block' => 'Select a block in the email or in the structure list to change it.',
        'not_editable' => 'This kind of block cannot be edited here yet. You can still move, duplicate and delete it, and its content is kept as it is.',
        'style_options' => 'Style options',
        'loading' => 'Loading…',
        'updating_preview' => 'Updating preview…',
        'schema_failed' => 'The schema could not be loaded ({status}).',
        'preview_failed' => 'The preview could not be rendered ({status}).',
        'drag_to_move' => 'Drag to move',
        'drag_label' => 'Drag {label} to move it. Or use the move buttons.',
        'move_up' => 'Move up',
        'move_down' => 'Move down',
        'duplicate' => 'Duplicate',
        'delete' => 'Delete',
        'move_up_label' => 'Move {label} up',
        'move_down_label' => 'Move {label} down',
        'duplicate_label' => 'Duplicate {label}',
        'delete_label' => 'Delete {label}',
        'block_added' => '{label} added at position {position} of {total}.',
        'block_moved' => '{label} moved to position {position} of {total}.',
        'block_duplicated' => 'Duplicated. The copy is at position {position} of {total}.',
        'block_deleted' => '{label} deleted. Press undo to bring it back.',
        'move_cancelled' => 'Move cancelled.',
        'item_added' => 'List item added.',
        'item_removed' => 'List item removed.',
        'item_moved' => 'List item moved.',
        'palette_label' => '{label}. Add to the email.',
        'palette_hint' => 'Click to add, or drag onto the email.',
        'structure_label' => '{label}. Block {position} of {total}.',
        'item_label' => '{label} {position} of {total}',
        'move_item_up' => 'Move item {position} up',
        'move_item_down' => 'Move item {position} down',
        'remove_item' => 'Remove item {position}',
        'add_item' => 'Add item',
        'custom_option' => '{value} (custom)',
        'not_set' => 'Not set',
        'pick_color' => '{label}: pick a color',
    ];

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

    /** @var array<string, string>|Closure|null */
    protected array|Closure|null $translations = null;

    protected string|Closure|null $direction = null;

    protected string|Closure|null $locale = null;

    /**
     * Translate the editor's interface: a map of `DEFAULT_STRINGS` keys to text. A key left out stays English.
     *
     * @param  array<string, string>|Closure|null  $translations
     */
    public function translations(array|Closure|null $translations): static
    {
        $this->translations = $translations;

        return $this;
    }

    /**
     * @return array<string, string>|null
     */
    public function getTranslations(): ?array
    {
        $translations = $this->evaluate($this->translations);

        return is_array($translations)
            ? array_filter(array_intersect_key($translations, self::DEFAULT_STRINGS), is_string(...))
            : null;
    }

    /** `ltr` or `rtl`: `rtl` mirrors the editor's interface. */
    public function direction(string|Closure|null $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function getDirection(): ?string
    {
        $direction = $this->evaluate($this->direction);

        return in_array($direction, ['ltr', 'rtl'], true) ? $direction : null;
    }

    /** The language of the editor's interface (a BCP 47 tag such as `ar`), set as `lang` on the editor. */
    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        $locale = $this->evaluate($this->locale);

        return is_string($locale) && $locale !== '' ? $locale : null;
    }

    /**
     * The blocks the editor offers. Override it in a subclass to change them (add, hide or relabel a block) without
     * copying the view.
     *
     * @return array<string, mixed>
     */
    public function getEditorSchema(): array
    {
        return SlotSchemaRegistry::toArray();
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

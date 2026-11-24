<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Filament\Actions\ActionsServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\AssetManager;
use Filament\Support\Assets\Css;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\SupportServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\LivewireServiceProvider;
use Odden\MailBuilder\Filament\Components\MailEditor;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;
use Odden\MailBuilder\Tests\TestCase;

class MailEditorFieldTest extends TestCase
{
    // The field view needs Filament's own views, which the base test case does not load.
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
        ];
    }

    public function test_state_becomes_a_plain_list_of_slots(): void
    {
        // Filament's Builder keeps items keyed by id.
        $keyed = ['a1' => ['type' => 'hero', 'data' => ['title' => 'A']], 'b2' => ['type' => 'divider']];

        $this->assertSame(
            [['type' => 'hero', 'data' => ['title' => 'A']], ['type' => 'divider', 'data' => []]],
            MailEditor::normalizeState($keyed),
        );
        $this->assertSame([['type' => 'hero', 'data' => []]], MailEditor::normalizeState([['type' => 'hero', 'data' => 'not an array']]));
    }

    public function test_things_that_are_not_slots_are_dropped(): void
    {
        $this->assertSame([], MailEditor::normalizeState(null));
        $this->assertSame([], MailEditor::normalizeState('text'));
        $this->assertSame([], MailEditor::normalizeState([['data' => []], 'x', ['type' => 5]]));
    }

    public function test_the_preview_is_rendered_by_the_field_with_a_marker_on_each_slot(): void
    {
        $html = MailEditor::make('slots')->renderPreview(['slots' => [
            ['type' => 'hero', 'data' => ['title' => 'From the field']],
            ['type' => 'divider', 'data' => []],
        ]]);

        $this->assertStringContainsString('From the field', $html);
        $this->assertStringContainsString('data-odden-slot="0"', $html);
        $this->assertStringContainsString('data-odden-slot="1"', $html);
    }

    public function test_the_fields_theme_applies_to_the_preview(): void
    {
        $html = MailEditor::make('slots')->theme(['container_width' => 480])->renderPreview(['slots' => [['type' => 'divider', 'data' => []]]]);

        $this->assertStringContainsString('max-width: 480px', $html);
    }

    public function test_the_theme_can_be_a_closure_evaluated_for_each_preview(): void
    {
        $width = 480;
        $field = MailEditor::make('slots')->theme(function () use (&$width): array {
            return ['container_width' => $width];
        });
        $document = ['slots' => [['type' => 'divider', 'data' => []]]];

        $this->assertStringContainsString('max-width: 480px', $field->renderPreview($document));

        $width = 560;

        $this->assertStringContainsString('max-width: 560px', $field->renderPreview($document));
    }

    public function test_the_fields_theme_wins_over_a_theme_the_browser_sent(): void
    {
        $html = MailEditor::make('slots')->theme(['container_width' => 480])->renderPreview([
            'slots' => [['type' => 'divider', 'data' => []]],
            'theme' => ['container_width' => 700, 'primary_color' => '#ff0000'],
        ]);

        $this->assertStringContainsString('max-width: 480px', $html);
        $this->assertStringNotContainsString('max-width: 700px', $html);
    }

    public function test_a_closure_that_returns_nothing_means_no_theme(): void
    {
        $this->assertSame([], MailEditor::make('slots')->theme(fn (): ?array => null)->getTheme());
    }

    public function test_the_preview_rejects_what_the_route_would_reject(): void
    {
        $this->expectException(ValidationException::class);

        MailEditor::make('slots')->renderPreview(['slots' => [['type' => 'not_a_slot', 'data' => []]]]);
    }

    public function test_the_preview_method_is_the_only_exposed_one(): void
    {
        $exposed = [];

        foreach ((new \ReflectionClass(MailEditor::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes(ExposedLivewireMethod::class) !== []) {
                $exposed[] = $method->getName();
            }
        }

        $this->assertSame(['renderPreview'], $exposed);
    }

    public function test_the_editor_files_are_registered_as_filament_assets(): void
    {
        $assets = app(AssetManager::class);

        $components = $assets->getAlpineComponents(['getodden/mail']);
        $styles = $assets->getStyles(['getodden/mail']);

        $this->assertCount(1, $components);
        $this->assertInstanceOf(AlpineComponent::class, $components[0]);
        $this->assertSame('odden-mail-editor', $components[0]->getId());
        $this->assertCount(1, $styles);
        $this->assertInstanceOf(Css::class, $styles[0]);
        $this->assertFileExists($components[0]->getPath());
        $this->assertFileExists($styles[0]->getPath());
    }

    public function test_the_default_view_passes_no_dictionary_direction_or_language(): void
    {
        $html = $this->renderField(MailEditor::make('slots'));

        $this->assertStringContainsString('strings: null', $html);
        $this->assertStringContainsString('dir: null', $html);
        $this->assertStringContainsString('lang: null', $html);
    }

    public function test_translations_direction_and_locale_reach_the_view(): void
    {
        $html = $this->renderField(MailEditor::make('slots')
            ->translations(['undo' => 'Rückgängig', 'not_a_key' => 'x', 'redo' => 5])
            ->direction(fn (): string => 'rtl')
            ->locale('ar'));

        $this->assertStringContainsString('strings: JSON.parse(\'{\\u0022undo\\u0022:\\u0022Rückgängig\\u0022}\')', $html);
        $this->assertStringNotContainsString('not_a_key', $html);
        $this->assertStringContainsString("dir: 'rtl'", $html);
        $this->assertStringContainsString("lang: 'ar'", $html);
    }

    public function test_only_ltr_and_rtl_are_accepted_as_a_direction(): void
    {
        $this->assertNull(MailEditor::make('slots')->direction('sideways')->getDirection());
        $this->assertSame('ltr', MailEditor::make('slots')->direction('ltr')->getDirection());
        $this->assertNull(MailEditor::make('slots')->locale('')->getLocale());
    }

    public function test_the_schema_comes_from_the_field_so_a_subclass_can_change_it(): void
    {
        $this->assertSame(SlotSchemaRegistry::toArray(), MailEditor::make('slots')->getEditorSchema());

        $field = new class('slots') extends MailEditor
        {
            public function getEditorSchema(): array
            {
                return ['slots' => [], 'marker' => 'custom-schema'];
            }
        };

        $this->assertStringContainsString('custom-schema', $this->renderField($field));
        $this->assertStringNotContainsString('custom-schema', $this->renderField(MailEditor::make('slots')));
    }

    public function test_the_php_dictionary_matches_the_editors_english_defaults(): void
    {
        $js = file_get_contents(__DIR__.'/../../resources/js/editor/i18n.js');
        preg_match_all("/^\\s+'(\\w+)': '((?:[^'\\\\]|\\\\.)*)',?$/m", $js, $m);
        $fromJs = array_map(stripslashes(...), array_combine($m[1], $m[2]));

        $this->assertSame($fromJs, MailEditor::DEFAULT_STRINGS);
    }

    private function renderField(MailEditor $field): string
    {
        // The session middleware shares this on a real request.
        View::share('errors', new ViewErrorBag);

        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };

        return Schema::make($livewire)->components([$field])->toHtml();
    }
}

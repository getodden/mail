<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\AssetManager;
use Filament\Support\Assets\Css;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Validation\ValidationException;
use Odden\MailBuilder\Filament\Components\MailEditor;
use Odden\MailBuilder\Tests\TestCase;

class MailEditorFieldTest extends TestCase
{
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
}

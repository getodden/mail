<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Support\Str;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Filament\Components\EmailSlotBuilder;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;
use Odden\MailBuilder\Tests\TestCase;
use ReflectionProperty;

class SchemaBlockFactoryTest extends TestCase
{
    public function test_each_described_type_has_a_filament_block_with_its_basic_fields_in_order(): void
    {
        foreach (SlotSchemaRegistry::all() as $schema) {
            $block = self::block($schema->type);

            $this->assertInstanceOf(Block::class, $block, $schema->type->value);

            $fieldNames = array_map(
                fn (Component $component): string => $component->getName(),
                array_values(array_filter(self::children($block), fn (Component $component): bool => $component instanceof Field)),
            );

            $this->assertSame(
                array_map(fn (SlotField $field): string => $field->key, $schema->basicFields()),
                $fieldNames,
                "The {$schema->type->value} form does not show the schema's basic fields.",
            );
        }
    }

    public function test_the_audience_rule_fieldset_is_only_on_slots_that_support_it(): void
    {
        foreach (SlotSchemaRegistry::all() as $schema) {
            $block = self::block($schema->type);
            $hasFieldset = array_filter(self::children($block), fn (Component $component): bool => $component instanceof Fieldset) !== [];

            $this->assertSame($schema->supportsVisibility, $hasFieldset, $schema->type->value);
        }
    }

    public function test_advanced_fields_stay_out_of_the_form(): void
    {
        $names = array_map(
            fn (Component $component): string => $component instanceof Field ? $component->getName() : '',
            self::children(EmailSlotBuilder::getHeroBlock()),
        );

        $this->assertNotContains('hero_image', $names);
        $this->assertNotContains('subtext_color', $names);
    }

    public function test_types_without_a_schema_still_have_their_block(): void
    {
        $this->assertInstanceOf(Block::class, EmailSlotBuilder::getTestimonialBlock());
    }

    private static function block(SlotType $type): Block
    {
        return EmailSlotBuilder::{'get'.Str::studly($type->value).'Block'}();
    }

    /**
     * @return array<int, Component>
     */
    private static function children(Block $block): array
    {
        $children = (new ReflectionProperty(Component::class, 'childComponents'))->getValue($block);

        return $children['default'] ?? $children;
    }
}

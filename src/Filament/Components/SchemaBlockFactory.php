<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Filament\Components;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchema;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;

/**
 * Builds the Filament block of a slot type from its schema, so the form and the drag-and-drop editor
 * describe a slot the same way.
 */
final class SchemaBlockFactory
{
    public static function make(SlotType $type): Block
    {
        $schema = SlotSchemaRegistry::for($type) ?? throw new \LogicException("No schema for slot type [{$type->value}].");

        return self::fromSchema($schema);
    }

    public static function fromSchema(SlotSchema $schema): Block
    {
        $components = array_map(fn (SlotField $field): Field => self::field($field), $schema->basicFields());

        $block = Block::make($schema->type->value)
            ->label($schema->label())
            ->icon(self::icon($schema->icon));

        if ($schema->supportsVisibility) {
            $block->schema([...$components, EmailSlotBuilder::getVisibilityFieldset()->columnSpanFull()]);
        } else {
            $block->schema($components);
        }

        return $schema->columns > 0 ? $block->columns($schema->columns) : $block;
    }

    private static function icon(string $key): Heroicon
    {
        /** @var Heroicon $icon */
        $icon = constant(Heroicon::class.'::'.Str::studly($key));

        return $icon;
    }

    private static function field(SlotField $field): Field
    {
        $component = match ($field->type) {
            FieldType::Textarea => Textarea::make($field->key)->rows($field->rows > 0 ? $field->rows : null),
            FieldType::RichText => RichEditor::make($field->key),
            FieldType::Color => ColorPicker::make($field->key),
            FieldType::Toggle => Toggle::make($field->key),
            FieldType::Select => Select::make($field->key)->options(self::options($field)),
            FieldType::Number => TextInput::make($field->key)->numeric(),
            FieldType::Items => Repeater::make($field->key)
                ->schema(array_map(fn (SlotField $item): Field => self::field($item), $field->items))
                ->columns($field->itemColumns > 0 ? $field->itemColumns : null)
                ->collapsible()
                ->defaultItems($field->defaultItems),
            FieldType::Text, FieldType::Url, FieldType::Image => TextInput::make($field->key),
        };

        $component->label($field->label);

        if ($field->required) {
            $component->required();
        }

        if ($field->default !== null) {
            $component->default($field->default);
        }

        if ($field->placeholder !== null && method_exists($component, 'placeholder')) {
            $component->placeholder($field->placeholder);
        }

        if ($field->help !== null) {
            $component->helperText($field->help);
        }

        if ($field->fullWidth) {
            $component->columnSpanFull();
        }

        return $component;
    }

    /**
     * @return array<string|int, string>
     */
    private static function options(SlotField $field): array
    {
        $options = [];

        foreach ($field->options as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }
}

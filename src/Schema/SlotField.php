<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Schema;

use Closure;

/**
 * One field of a slot: its key in the slot's `data`, how to label and edit it, and its default.
 *
 * Fields marked `advanced` are read by the slot's view but are not part of the basic form: an editor shows them in a
 * separate style group, and the Filament form leaves them out.
 */
final class SlotField
{
    /**
     * @param  array<int, array{value: string|int, label: string}>  $options  Choices of a select field, in display order.
     * @param  array<int, SlotField>  $items  The fields of each item of an items field.
     * @param  scalar|array<mixed>|Closure(): (scalar|array<mixed>|null)|null  $default  A value, or a closure for defaults that depend on the app's config.
     */
    public function __construct(
        public readonly string $key,
        public readonly FieldType $type,
        public readonly string $label,
        public readonly bool $required = false,
        public readonly string|int|float|bool|array|Closure|null $default = null,
        public readonly ?string $placeholder = null,
        public readonly ?string $help = null,
        public readonly array $options = [],
        public readonly array $items = [],
        public readonly int $rows = 0,
        public readonly bool $fullWidth = false,
        public readonly bool $advanced = false,
        public readonly int $defaultItems = 0,
        public readonly int $itemColumns = 0,
    ) {}

    /**
     * The default value, with a closure resolved.
     *
     * @return scalar|array<mixed>|null
     */
    public function resolveDefault(): string|int|float|bool|array|null
    {
        return $this->default instanceof Closure ? ($this->default)() : $this->default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'key' => $this->key,
            'type' => $this->type->value,
            'label' => $this->label,
            'required' => $this->required,
            'default' => $this->resolveDefault(),
            'advanced' => $this->advanced,
            'full_width' => $this->fullWidth,
        ];

        if ($this->placeholder !== null) {
            $array['placeholder'] = $this->placeholder;
        }

        if ($this->help !== null) {
            $array['help'] = $this->help;
        }

        if ($this->options !== []) {
            $array['options'] = $this->options;
        }

        if ($this->rows > 0) {
            $array['rows'] = $this->rows;
        }

        if ($this->items !== []) {
            $array['items'] = array_map(fn (SlotField $field): array => $field->toArray(), $this->items);
            $array['default_items'] = $this->defaultItems;
            $array['item_columns'] = $this->itemColumns;
        }

        return $array;
    }
}

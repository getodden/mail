<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Schema;

use Odden\MailBuilder\Enums\SlotType;

/**
 * A machine-readable description of one slot type: what fields its `data` takes and how to edit them.
 *
 * It is the single source for the Filament form and for the drag-and-drop editor, so the two cannot drift apart.
 */
final class SlotSchema
{
    /**
     * @param  array<int, SlotField>  $fields
     * @param  string  $icon  A neutral icon key (a Heroicons name such as "globe-alt"), not tied to a UI library.
     * @param  int  $columns  Columns of the basic form (0 for a single column).
     * @param  bool  $supportsVisibility  Whether the slot takes an audience visibility rule.
     */
    public function __construct(
        public readonly SlotType $type,
        public readonly string $icon,
        public readonly array $fields,
        public readonly int $columns = 0,
        public readonly bool $supportsVisibility = false,
    ) {}

    public function label(): string
    {
        return $this->type->label();
    }

    /**
     * The fields shown in the basic form.
     *
     * @return array<int, SlotField>
     */
    public function basicFields(): array
    {
        return array_values(array_filter($this->fields, fn (SlotField $field): bool => ! $field->advanced));
    }

    /**
     * The `data` of a new slot of this type: every field's default.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->fields as $field) {
            $default = $field->resolveDefault();

            if ($default !== null) {
                $defaults[$field->key] = $default;
            }
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'label' => $this->label(),
            'icon' => $this->icon,
            'columns' => $this->columns,
            'supports_visibility' => $this->supportsVisibility,
            'fields' => array_map(fn (SlotField $field): array => $field->toArray(), $this->fields),
        ];
    }
}

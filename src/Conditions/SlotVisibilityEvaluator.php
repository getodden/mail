<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Conditions;

use Illuminate\Support\Arr;
use Odden\MailBuilder\Data\EmailSlot;

class SlotVisibilityEvaluator
{
    /**
     * Determine if an EmailSlot should be visible based on recipient context.
     *
     * @param  EmailSlot|array<string, mixed>  $slot
     * @param  array<string, mixed>  $context
     */
    public function isVisible(EmailSlot|array $slot, array $context): bool
    {
        $slotData = $slot instanceof EmailSlot ? $slot->data : ($slot['data'] ?? $slot);

        /** @var array{field?: string|null, operator?: string|null, value?: string|null}|null $rule */
        $rule = $slotData['visibility'] ?? null;

        if ($rule === null || empty($rule['field'])) {
            return true;
        }

        $field = (string) $rule['field'];
        $operator = (string) ($rule['operator'] ?? 'equals');
        $expected = (string) ($rule['value'] ?? '');

        $actual = Arr::get($context, $field);
        $actualStr = $actual !== null && is_scalar($actual) ? (string) $actual : '';

        return match ($operator) {
            'equals' => strtolower(trim($actualStr)) === strtolower(trim($expected)),
            'not_equals' => strtolower(trim($actualStr)) !== strtolower(trim($expected)),
            'contains' => str_contains(strtolower($actualStr), strtolower($expected)),
            'is_not_empty' => trim($actualStr) !== '',
            'is_empty' => trim($actualStr) === '',
            default => true,
        };
    }

    /**
     * Filter an array of slots or EmailDocument slots to only those visible for the given context.
     *
     * @param  list<array<string, mixed>>  $slots
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    public function filterSlots(array $slots, array $context): array
    {
        if (empty($context)) {
            return $slots;
        }

        $filtered = [];
        foreach ($slots as $slot) {
            if ($this->isVisible($slot, $context)) {
                $filtered[] = $slot;
            }
        }

        return $filtered;
    }
}

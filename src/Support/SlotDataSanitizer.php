<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;

final class SlotDataSanitizer
{
    public static function slot(EmailSlot $slot): EmailSlot
    {
        $fields = array_values(SlotSchemaRegistry::forKey($slot->key())->fields ?? []);

        return new EmailSlot(type: $slot->type, data: self::data($slot->data, $fields), visibility: $slot->visibility);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, SlotField>  $fields
     * @return array<string, mixed>
     */
    public static function data(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            $value = $data[$field->key] ?? null;

            if (in_array($field->type, [FieldType::Items, FieldType::Tags], true) && $value !== null && ! is_array($value)) {
                unset($data[$field->key]);

                continue;
            }

            if ($field->type === FieldType::Items && is_array($value)) {
                $data[$field->key] = array_map(
                    fn (mixed $item): mixed => is_array($item) ? self::data($item, $field->items) : $item,
                    $value,
                );

                continue;
            }

            if ($field->type === FieldType::Number) {
                self::number($data, $field->key, $value);

                continue;
            }

            if ($field->type === FieldType::Select) {
                self::select($data, $field, $value);

                continue;
            }

            if ($field->type === FieldType::RichText || $field->rawHtml) {
                self::markup($data, $field->key, $value, $field->rawHtml ? RawHtmlSanitizer::PROFILE_HTML : RawHtmlSanitizer::PROFILE_RICH);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            match ($field->type) {
                FieldType::Url => $data[$field->key] = SafeUrl::for($value),
                FieldType::Image => $data[$field->key] = SafeUrl::for($value, true),
                FieldType::Color => self::color($data, $field->key, $value),
                default => null,
            };
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function markup(array &$data, string $key, mixed $value, string $profile): void
    {
        if ($value === null) {
            return;
        }

        if (is_scalar($value)) {
            $data[$key] = RawHtmlSanitizer::sanitize((string) $value, $profile);

            return;
        }

        unset($data[$key]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function number(array &$data, string $key, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        if ((is_int($value) || is_float($value) || is_string($value)) && is_numeric($value)) {
            $number = $value + 0;

            if (! is_float($number) || is_finite($number)) {
                $data[$key] = $number;

                return;
            }
        }

        unset($data[$key]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function select(array &$data, SlotField $field, mixed $value): void
    {
        if ($value === null || $field->options === []) {
            return;
        }

        $allowed = array_map(fn (array $option): string => (string) $option['value'], $field->options);

        if (! is_scalar($value) || ! in_array((string) $value, $allowed, true)) {
            unset($data[$field->key]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function color(array &$data, string $key, string $value): void
    {
        $color = SafeColor::for($value);

        if ($color === null) {
            unset($data[$key]);

            return;
        }

        $data[$key] = $color;
    }
}

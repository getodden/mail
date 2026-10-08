<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Data;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Odden\MailBuilder\Enums\SlotType;

class EmailSlot
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $visibility
     */
    public function __construct(
        public readonly SlotType $type,
        public readonly array $data = [],
        public readonly ?array $visibility = null,
    ) {}

    /**
     * Create an EmailSlot from an untyped array payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $rawType = $payload['type'] ?? null;

        if (! is_string($rawType)) {
            throw new InvalidArgumentException('Missing or invalid "type" in email slot payload.');
        }

        $type = SlotType::tryFrom($rawType);

        if ($type === null) {
            throw new InvalidArgumentException("Unsupported email slot type [{$rawType}].");
        }

        /** @var array<string, mixed> $data */
        $data = isset($payload['data']) && is_array($payload['data'])
            ? $payload['data']
            : array_diff_key($payload, ['type' => true, 'visibility' => true]);

        /** @var array<string, mixed>|null $visibility */
        $visibility = $payload['visibility'] ?? ($data['visibility'] ?? null);
        if (isset($data['visibility'])) {
            unset($data['visibility']);
        }

        return new self($type, $data, $visibility);
    }

    /**
     * Convert slot into an array.
     *
     * @return array{type: string, data: array<string, mixed>, visibility?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $arr = [
            'type' => $this->type->value,
            'data' => $this->data,
        ];

        if ($this->visibility !== null) {
            $arr['visibility'] = $this->visibility;
        }

        return $arr;
    }

    /**
     * Retrieve a specific attribute with fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Determine if this slot should be visible given a recipient context.
     *
     * @param  array<string, mixed>  $context
     */
    public function matchesContext(array $context): bool
    {
        if (empty($this->visibility)) {
            return true;
        }

        $field = (string) ($this->visibility['field'] ?? '');
        $operator = (string) ($this->visibility['operator'] ?? 'equals');
        $expected = $this->visibility['value'] ?? null;

        if (empty($field)) {
            return true;
        }

        $actual = Arr::get($context, $field);

        return match ($operator) {
            'equals' => (string) $actual === (string) $expected,
            'not_equals' => (string) $actual !== (string) $expected,
            'contains' => is_string($actual) && str_contains(strtolower($actual), strtolower((string) $expected)),
            'is_empty' => empty($actual),
            'is_not_empty' => ! empty($actual),
            default => true,
        };
    }
}

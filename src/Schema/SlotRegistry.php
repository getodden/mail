<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Schema;

use InvalidArgumentException;
use Odden\MailBuilder\Enums\SlotType;

final class SlotRegistry
{
    /** @var array<string, CustomSlotType> */
    private array $types = [];

    public function register(CustomSlotType $type): void
    {
        $key = $type->key();

        if (preg_match('/^[a-z][a-z0-9_]*$/', $key) !== 1) {
            throw new InvalidArgumentException("Invalid custom slot key [{$key}]. Keys match /^[a-z][a-z0-9_]*$/.");
        }

        if (SlotType::tryFrom($key) !== null) {
            throw new InvalidArgumentException("Custom slot key [{$key}] collides with a built-in slot type.");
        }

        if (isset($this->types[$key])) {
            throw new InvalidArgumentException("Custom slot key [{$key}] is already registered.");
        }

        $schemaKey = $type->schema()->key();

        if ($schemaKey !== $key) {
            throw new InvalidArgumentException("Custom slot [{$key}] has a schema built for [{$schemaKey}].");
        }

        $this->types[$key] = $type;
    }

    public function get(string $key): ?CustomSlotType
    {
        return $this->types[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->types);
    }

    /**
     * @return list<CustomSlotType>
     */
    public function all(): array
    {
        return array_values($this->types);
    }

    public function flush(): void
    {
        $this->types = [];
    }
}

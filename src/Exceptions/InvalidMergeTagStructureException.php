<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Exceptions;

use InvalidArgumentException;

final class InvalidMergeTagStructureException extends InvalidArgumentException
{
    private const DESCRIPTIONS = [
        'unbalanced_opener' => 'A block tag such as {% if %} is never closed. Add the matching {% endif %}.',
        'unbalanced_closer' => 'A closing or else tag such as {% endif %} or {% else %} has no block to belong to. Remove it, or add the opening tag.',
        'mismatched_closer' => 'A closing tag does not match the block it closes, for example {% if %} closed by {% endfor %}.',
        'nested_block' => 'Block tags cannot be nested inside each other. Close the first block before you open another one, or use the slot visibility rules.',
        'unknown_block_tag' => 'A block tag is not supported. Only if, for, unless, each and with blocks are recognised.',
    ];

    /**
     * @param  list<string>  $problems
     */
    private function __construct(public readonly string $field, public readonly array $problems)
    {
        parent::__construct("The block merge tags in [{$field}] are not valid: ".implode(', ', $problems).'.');
    }

    /**
     * @param  list<string>  $problems
     */
    public static function in(string $field, array $problems): self
    {
        return new self($field, $problems);
    }

    /**
     * @return list<string>
     */
    public function descriptions(): array
    {
        return array_map(
            static fn (string $problem): string => self::DESCRIPTIONS[$problem] ?? $problem,
            $this->problems,
        );
    }
}

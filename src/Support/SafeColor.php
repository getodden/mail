<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class SafeColor
{
    private const PATTERNS = [
        '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i',
        '/^(?:rgb|rgba|hsl|hsla)\([0-9\s,.\/%]*\)$/i',
        '/^[a-z]{1,20}$/i',
    ];

    public static function for(string $value): ?string
    {
        $value = trim($value);

        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return $value;
            }
        }

        return null;
    }
}

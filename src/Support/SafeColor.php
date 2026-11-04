<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class SafeColor
{
    private const PATTERNS = [
        '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i',
        '/^(?:rgb|rgba|hsl|hsla)\([a-z0-9\s,.\/%]*\)$/i',
        '/^[a-z]{1,20}$/i',
    ];

    /**
     * @param  array<string, mixed>  $theme
     * @return array<string, mixed>
     */
    public static function theme(array $theme): array
    {
        foreach ($theme as $key => $value) {
            if (! str_ends_with((string) $key, '_color')) {
                continue;
            }

            $color = is_string($value) ? self::for($value) : null;

            if ($color === null) {
                unset($theme[$key]);
            } else {
                $theme[$key] = $color;
            }
        }

        return $theme;
    }

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

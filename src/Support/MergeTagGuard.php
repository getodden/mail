<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class MergeTagGuard
{
    /**
     * @return array{string, array<string, string>}
     */
    public static function protect(string $html): array
    {
        do {
            $nonce = bin2hex(random_bytes(8));
        } while (str_contains($html, $nonce));

        $originals = [];
        $protected = '';
        $offset = 0;
        $length = strlen($html);

        while (($open = strpos($html, '{', $offset)) !== false) {
            $end = self::tagEnd($html, $open, $length);

            if ($end === null) {
                $protected .= substr($html, $offset, $open + 1 - $offset);
                $offset = $open + 1;

                continue;
            }

            $token = 'mbtag'.$nonce.'n'.count($originals).'x';
            $originals[$token] = substr($html, $open, $end - $open);
            $protected .= substr($html, $offset, $open - $offset).$token;
            $offset = $end;
        }

        return [$protected.substr($html, $offset), $originals];
    }

    /**
     * @param  array<string, string>  $originals
     */
    public static function restore(string $html, array $originals, bool $quoteSafeInTags = false): string
    {
        if ($originals === []) {
            return $html;
        }

        if (! $quoteSafeInTags) {
            return strtr($html, $originals);
        }

        $quoteSafe = array_map(
            static fn (string $original): string => str_replace(['"', "'"], ['&quot;', '&#039;'], $original),
            $originals,
        );

        $restored = '';
        $offset = 0;

        while (($open = strpos($html, '<', $offset)) !== false) {
            $close = strpos($html, '>', $open);
            $close = $close === false ? strlen($html) - 1 : $close;

            $restored .= strtr(substr($html, $offset, $open - $offset), $originals)
                .strtr(substr($html, $open, $close - $open + 1), $quoteSafe);
            $offset = $close + 1;
        }

        return $restored.strtr(substr($html, $offset), $originals);
    }

    private static function tagEnd(string $html, int $open, int $length): ?int
    {
        $kind = $html[$open + 1] ?? '';

        if ($kind !== '{' && $kind !== '%') {
            return null;
        }

        $bodyEnd = $open + 2 + strcspn($html, '<>{}', $open + 2);

        if ($bodyEnd >= $length || $html[$bodyEnd] !== '}') {
            return null;
        }

        if ($kind === '{') {
            return ($html[$bodyEnd + 1] ?? '') === '}' ? $bodyEnd + 2 : null;
        }

        return $bodyEnd > $open + 2 && $html[$bodyEnd - 1] === '%' ? $bodyEnd + 1 : null;
    }
}

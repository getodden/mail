<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class MergeTagGuard
{
    private const ATTRIBUTE_TOKEN = '/^\{\{ *[A-Za-z0-9_.]+(?: *\| *[A-Za-z_]+(?:\( *(?:\'[^\'"<>&\\\\\r\n]*\'|"[^\'"<>&\\\\\r\n]*"|[A-Za-z0-9_.-]*) *\)| *: *(?:\'[^\'"<>&\\\\\r\n]*\'|"[^\'"<>&\\\\\r\n]*"|[A-Za-z0-9_.-]*))?)* *\}\}$/D';

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
    public static function restore(string $html, array $originals): string
    {
        return $originals === [] ? $html : strtr($html, $originals);
    }

    /**
     * @param  array<string, string>  $originals
     */
    public static function restoreInMarkup(string $html, array $originals): string
    {
        $restored = '';
        $offset = 0;

        while (($open = strpos($html, '<', $offset)) !== false) {
            $close = strpos($html, '>', $open);
            $close = $close === false ? strlen($html) - 1 : $close;

            $restored .= self::restore(substr($html, $offset, $open - $offset), $originals)
                .self::attributeTokens(self::restore(substr($html, $open, $close - $open + 1), $originals));
            $offset = $close + 1;
        }

        return $restored.self::restore(substr($html, $offset), $originals);
    }

    public static function attributeToken(string $token): ?string
    {
        if (preg_match(self::ATTRIBUTE_TOKEN, $token) !== 1) {
            return null;
        }

        return (string) preg_replace('/"([^"\']*)"/', "'\$1'", $token);
    }

    private static function attributeTokens(string $tag): string
    {
        if (! str_contains($tag, '{') && ! str_contains($tag, '}')) {
            return $tag;
        }

        $kept = '';
        $offset = 0;
        $length = strlen($tag);

        while (($open = strpos($tag, '{', $offset)) !== false) {
            $kept .= str_replace('}', '', substr($tag, $offset, $open - $offset));
            $end = self::tagEnd($tag, $open, $length);

            if ($end === null) {
                $offset = $open + 1;

                continue;
            }

            $kept .= self::attributeToken(substr($tag, $open, $end - $open)) ?? '';
            $offset = $end;
        }

        return $kept.str_replace('}', '', substr($tag, $offset));
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

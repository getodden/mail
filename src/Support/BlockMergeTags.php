<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class BlockMergeTags
{
    private const TOKEN = '/\{%[^<>{}]*%\}|\{\{\s*[#\/^][^<>{}]*\}\}|\{\{\s*else\b[^<>{}]*\}\}/i';

    private const OPENERS = ['if', 'for', 'unless', 'each', 'with', 'block', 'macro', 'filter', 'call', 'raw', 'autoescape', 'spaceless'];

    private const MAX_PASSES = 4;

    public static function strip(string $html): string
    {
        if (! str_contains($html, '{')) {
            return $html;
        }

        for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
            $stripped = self::once($html);

            if ($stripped === $html) {
                return $html;
            }

            $html = $stripped;
        }

        return str_replace(['{', '}'], '', $html);
    }

    private static function once(string $html): string
    {
        if (preg_match_all(self::TOKEN, $html, $matches, PREG_OFFSET_CAPTURE) === 0) {
            return $html;
        }

        $removals = [];
        $open = [];

        foreach ($matches[0] as [$token, $start]) {
            $end = $start + strlen($token);
            [$kind, $name] = self::classify($token);

            if ($kind === 'open') {
                $open[] = [$name, $start, $end];

                continue;
            }

            $removals[] = [$start, $end];

            if ($kind !== 'close') {
                continue;
            }

            for ($index = count($open) - 1; $index >= 0; $index--) {
                if ($open[$index][0] !== $name) {
                    continue;
                }

                $removals[] = [$open[$index][1], $end];
                array_splice($open, $index);

                break;
            }
        }

        foreach ($open as [, $start, $end]) {
            $removals[] = [$start, $end];
        }

        usort($removals, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $kept = '';
        $offset = 0;

        foreach ($removals as [$start, $end]) {
            if ($start > $offset) {
                $kept .= substr($html, $offset, $start - $offset);
            }

            $offset = max($offset, $end);
        }

        return $kept.substr($html, $offset);
    }

    /**
     * @return array{string, string}
     */
    private static function classify(string $token): array
    {
        if ($token[1] === '%') {
            preg_match('/^\{%-?\s*([a-z_]+)/i', $token, $word);
            $name = strtolower($word[1] ?? '');

            if (in_array($name, self::OPENERS, true)) {
                return ['open', $name];
            }

            if (str_starts_with($name, 'end') && in_array(substr($name, 3), self::OPENERS, true)) {
                return ['close', substr($name, 3)];
            }

            return ['other', $name];
        }

        preg_match('/^\{\{\s*([#\/^])\s*([a-z_]+)/i', $token, $word);
        $name = strtolower($word[2] ?? '');

        return match ($word[1] ?? '') {
            '#', '^' => in_array($name, self::OPENERS, true) ? ['open', $name] : ['other', $name],
            '/' => in_array($name, self::OPENERS, true) ? ['close', $name] : ['other', $name],
            default => ['other', $name],
        };
    }
}

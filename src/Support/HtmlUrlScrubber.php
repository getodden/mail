<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class HtmlUrlScrubber
{
    private const TAG = '/<([a-z][a-z0-9:_-]*)((?>"[^"]*"|\'[^\']*\'|[^>])*)>/i';

    private const ATTRIBUTE = '/([^\s"\'>\/=]+)(?:(\s*=\s*)(?>"([^"]*)"|\'([^\']*)\'|([^\s>]*)))?/';

    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'background', 'poster'];

    private const MEDIA_ATTRIBUTES = ['src', 'background', 'poster'];

    private const MEDIA_TAGS = ['img', 'td', 'table', 'v:fill'];

    private const DECODE_PASSES = 3;

    public static function scrub(string $html): string
    {
        if (! preg_match('/(?:href|src|action|formaction|background|poster)\s*=/i', $html)) {
            return $html;
        }

        return preg_replace_callback(
            self::TAG,
            fn (array $tag): string => '<'.$tag[1].self::scrubAttributes(strtolower($tag[1]), $tag[2]).'>',
            $html,
        ) ?? $html;
    }

    private static function scrubAttributes(string $tag, string $attributes): string
    {
        return preg_replace_callback(
            self::ATTRIBUTE,
            function (array $match) use ($tag): string {
                $name = strtolower($match[1]);

                if (! isset($match[2]) || ! in_array($name, self::URL_ATTRIBUTES, true)) {
                    return $match[0];
                }

                $raw = ($match[3] ?? '') !== '' ? $match[3] : (($match[4] ?? '') !== '' ? $match[4] : ($match[5] ?? ''));
                $decoded = self::decode($raw);
                $media = in_array($name, self::MEDIA_ATTRIBUTES, true) && in_array($tag, self::MEDIA_TAGS, true);
                $safe = SafeUrl::for($decoded, $media);

                if ($safe === trim($decoded)) {
                    return $match[0];
                }

                return $match[1].$match[2].'"'.htmlspecialchars($safe, ENT_QUOTES).'"';
            },
            $attributes,
        ) ?? $attributes;
    }

    private static function decode(string $value): string
    {
        for ($pass = 0; $pass < self::DECODE_PASSES; $pass++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($decoded === $value) {
                break;
            }

            $value = $decoded;
        }

        return $value;
    }
}

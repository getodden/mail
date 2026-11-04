<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

use Odden\MailBuilder\Exceptions\UnscrubbableHtmlException;

final class HtmlUrlScrubber
{
    private const TAG = '/<([a-z][a-z0-9:_-]*)((?:[^>"\']++|"[^"]*+"|\'[^\']*+\'|["\'])*+)>/i';

    private const ATTRIBUTE = '/([^\s"\'>\/=]+)(?:(\s*=\s*)(?>"([^"]*)"|\'([^\']*)\'|([^\s>]*)))?/';

    private const URL_ATTRIBUTES = ['href', 'xlink:href', 'src', 'action', 'formaction', 'background', 'poster', 'data'];

    private const ALWAYS_DROPPED_ATTRIBUTES = ['srcset'];

    private const URL_LIST_ATTRIBUTES = ['values'];

    private const MEDIA_ATTRIBUTES = ['src', 'background', 'poster'];

    private const MEDIA_TAGS = ['img', 'td', 'table', 'v:fill'];

    private const MENTIONS = '/(?:href|src|action|formaction|background|poster|data|srcset|values)\s*=|\bon[a-z0-9_:.-]+\s*=/i';

    public static function scrub(string $html): string
    {
        $mentionsAttribute = preg_match(self::MENTIONS, $html);

        if ($mentionsAttribute === false) {
            throw UnscrubbableHtmlException::afterPcreError(preg_last_error_msg());
        }

        if ($mentionsAttribute === 0) {
            return $html;
        }

        $scrubbed = preg_replace_callback(
            self::TAG,
            fn (array $tag): string => '<'.$tag[1].self::scrubAttributes(strtolower($tag[1]), $tag[2]).'>',
            $html,
        );

        return $scrubbed ?? throw UnscrubbableHtmlException::afterPcreError(preg_last_error_msg());
    }

    private static function scrubAttributes(string $tag, string $attributes): string
    {
        $scrubbed = preg_replace_callback(
            self::ATTRIBUTE,
            function (array $match) use ($tag): string {
                $name = strtolower($match[1]);

                if (strlen($name) > 2 && str_starts_with($name, 'on')) {
                    return '';
                }

                if (! isset($match[2])) {
                    return $match[0];
                }

                $raw = ($match[3] ?? '') !== '' ? $match[3] : (($match[4] ?? '') !== '' ? $match[4] : ($match[5] ?? ''));

                if (in_array($name, self::ALWAYS_DROPPED_ATTRIBUTES, true)) {
                    return '';
                }

                if (in_array($name, self::URL_LIST_ATTRIBUTES, true)) {
                    return self::isSafeList(SafeUrl::decodeEntities($raw)) ? $match[0] : '';
                }

                if (! in_array($name, self::URL_ATTRIBUTES, true)) {
                    return $match[0];
                }

                $decoded = SafeUrl::decodeEntities($raw);
                $media = in_array($name, self::MEDIA_ATTRIBUTES, true) && in_array($tag, self::MEDIA_TAGS, true);
                $safe = SafeUrl::for($decoded, $media);

                if ($safe === trim($decoded)) {
                    return $match[0];
                }

                return $match[1].$match[2].'"'.htmlspecialchars($safe, ENT_QUOTES).'"';
            },
            $attributes,
        );

        return $scrubbed ?? throw UnscrubbableHtmlException::afterPcreError(preg_last_error_msg());
    }

    private static function isSafeList(string $values): bool
    {
        foreach (explode(';', $values) as $value) {
            if (SafeUrl::for($value) !== trim($value)) {
                return false;
            }
        }

        return true;
    }
}

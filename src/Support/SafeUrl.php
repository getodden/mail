<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class SafeUrl
{
    private const LINK_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    private const IMAGE_SCHEMES = ['http', 'https', 'mailto', 'tel', 'cid'];

    private const IMAGE_DATA_URI = '#^data:image/(?:png|jpe?g|gif|webp);base64,#i';

    public static function for(string $url, bool $forImage = false): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $probe = (string) preg_replace('/[\x00-\x20\x7f]+/', '', $url);

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $probe, $match) !== 1) {
            return $url;
        }

        $scheme = strtolower($match[1]);
        $allowed = $forImage ? self::IMAGE_SCHEMES : self::LINK_SCHEMES;

        if (in_array($scheme, $allowed, true)) {
            return $url;
        }

        if ($forImage && preg_match(self::IMAGE_DATA_URI, $probe) === 1) {
            return $url;
        }

        return $forImage ? '' : '#';
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class SafeUrl
{
    private const DECODE_PASSES = 3;

    private const DEFAULT_LINK_SCHEMES = ['http', 'https', 'mailto', 'tel', 'sms', 'webcal', 'webcals', 'whatsapp', 'skype', 'geo', 'itms', 'itms-apps'];

    private const IMAGE_SCHEMES = ['http', 'https', 'mailto', 'tel', 'cid'];

    private const IMAGE_DATA_URI = '#^data:image/(?:png|jpe?g|gif|webp|avif|bmp);base64,#i';

    private const HOST_WITH_PORT = '#^(?:localhost|[a-z0-9-]+(?:\.[a-z0-9-]+)+):\d+(?:[/?\#]|$)#i';

    /**
     * @return list<string>
     */
    public static function linkSchemes(): array
    {
        $configured = PackageConfig::get('mail-builder.url_schemes', self::DEFAULT_LINK_SCHEMES);

        return array_values(array_unique(array_map(
            static fn (string $scheme): string => strtolower(trim($scheme)),
            array_filter(is_array($configured) ? $configured : self::DEFAULT_LINK_SCHEMES, is_string(...)),
        )));
    }

    public static function decodeEntities(string $value): string
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

        if (preg_match(self::HOST_WITH_PORT, $probe) === 1) {
            return $url;
        }

        $scheme = strtolower($match[1]);
        $allowed = $forImage ? self::IMAGE_SCHEMES : self::linkSchemes();

        if (in_array($scheme, $allowed, true)) {
            return $url;
        }

        if ($forImage && preg_match(self::IMAGE_DATA_URI, $probe) === 1) {
            return $url;
        }

        return $forImage ? '' : '#';
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Transport;

class CidImageEmbedder
{
    private const MAX_LOCAL_IMAGE_BYTES = 5 * 1024 * 1024;

    /**
     * Parse HTML, extract embedded base64 or local images, replace src with cid: references,
     * and produce attachment definitions for mail transport.
     *
     * @param  string|null  $basePath  Optional filesystem root path to resolve relative local images
     * @return array{
     *     html: string,
     *     attachments: list<array{name: string, data: string, mime: string, cid: string}>
     * }
     */
    public static function embed(string $html, ?string $basePath = null): array
    {
        $attachments = [];
        $cidMap = [];
        // Content-ID domain part (RFC 2392), taken from the application URL.
        $cidDomain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        // Match <img ... src="..." ...>
        $transformedHtml = (string) preg_replace_callback('/<img\b([^>]*?)src=([\'"])(.*?)\2([^>]*?)>/i', function (array $matches) use (&$attachments, &$cidMap, $basePath, $cidDomain): string {
            $before = $matches[1];
            $src = $matches[3];
            $after = $matches[4];

            // If already a cid:, skip
            if (str_starts_with($src, 'cid:')) {
                return $matches[0];
            }

            // Case 1: Base64 data URI (e.g. data:image/png;base64,iVBORw0KGgo...)
            if (preg_match('/^data:(image\/[a-z0-9\+\-]+);base64,(.+)$/i', $src, $dataMatches)) {
                $mime = $dataMatches[1];
                $binary = (string) base64_decode($dataMatches[2]);
                $ext = match ($mime) {
                    'image/jpeg' => 'jpg',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    'image/svg+xml' => 'svg',
                    default => 'png',
                };
                $hash = substr(md5($binary), 0, 12);
                $cid = "img_{$hash}@{$cidDomain}";
                $fileName = "embedded_{$hash}.{$ext}";

                if (! isset($cidMap[$cid])) {
                    $cidMap[$cid] = true;
                    $attachments[] = [
                        'name' => $fileName,
                        'data' => $binary,
                        'mime' => $mime,
                        'cid' => $cid,
                    ];
                }

                return "<img{$before}src=\"cid:{$cid}\"{$after}>";
            }

            // Case 2: Local relative file path (if basePath is provided and file exists)
            if ($basePath !== null) {
                $filePath = self::resolveLocalImage($basePath, $src);
                if ($filePath !== null) {
                    $content = (string) file_get_contents($filePath);
                    $mime = (string) mime_content_type($filePath);
                    $fileName = basename($filePath);
                    $hash = substr(md5($content), 0, 12);
                    $cid = "img_{$hash}@{$cidDomain}";

                    if (! isset($cidMap[$cid])) {
                        $cidMap[$cid] = true;
                        $attachments[] = [
                            'name' => $fileName,
                            'data' => $content,
                            'mime' => $mime,
                            'cid' => $cid,
                        ];
                    }

                    return "<img{$before}src=\"cid:{$cid}\"{$after}>";
                }
            }

            return $matches[0];
        }, $html);

        return [
            'html' => $transformedHtml,
            'attachments' => $attachments,
        ];
    }

    /**
     * Resolve a relative image path inside the base path, or null when it must not be embedded.
     *
     * The template is untrusted input, so the file must stay inside the base directory (after symlinks
     * and ".." are resolved), be a regular readable file of a modest size, and really be an image.
     */
    private static function resolveLocalImage(string $basePath, string $src): ?string
    {
        // URLs, protocol-relative addresses, stream wrappers and NUL bytes are never local paths.
        if (str_contains($src, "\0") || str_starts_with($src, '//') || preg_match('/^[a-z][a-z0-9+.\-]*:/i', $src)) {
            return null;
        }

        $root = realpath($basePath);
        $file = $root === false ? false : realpath(rtrim($basePath, '/').'/'.ltrim($src, '/'));

        if ($root === false || $file === false || ! str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (! is_file($file) || ! is_readable($file) || filesize($file) > self::MAX_LOCAL_IMAGE_BYTES) {
            return null;
        }

        return str_starts_with((string) mime_content_type($file), 'image/') ? $file : null;
    }
}

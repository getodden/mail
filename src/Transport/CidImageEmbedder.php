<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Transport;

class CidImageEmbedder
{
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
            if ($basePath !== null && ! str_starts_with($src, 'http://') && ! str_starts_with($src, 'https://')) {
                $filePath = rtrim($basePath, '/').'/'.ltrim($src, '/');
                if (file_exists($filePath) && is_readable($filePath)) {
                    $content = (string) file_get_contents($filePath);
                    $mime = mime_content_type($filePath) ?: 'image/png';
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
}

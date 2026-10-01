<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tracking;

class EmailTrackingPipeline
{
    /**
     * Inject a transparent 1x1 open tracking pixel right before the closing </body> tag.
     */
    public function injectTrackingPixel(string $html, string $trackingPixelUrl): string
    {
        $pixelTag = sprintf(
            '<img src="%s" width="1" height="1" border="0" alt="" style="display:none;width:1px;height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;mso-hide:all;" />',
            htmlspecialchars($trackingPixelUrl, ENT_QUOTES, 'UTF-8')
        );

        if (stripos($html, '</body>') !== false) {
            return (string) preg_replace('/<\/body>/i', $pixelTag.'</body>', $html, 1);
        }

        return $html.$pixelTag;
    }

    /**
     * Rewrite outbound HTML anchor tags to route through a click tracking redirector.
     *
     * @param  callable(string, string): string  $urlSigner
     */
    public function rewriteLinks(string $html, callable $urlSigner): string
    {
        return (string) preg_replace_callback(
            '/<a\s+([^>]*?)href=([\'"])(.*?)\2([^>]*?)>(.*?)<\/a>/is',
            function (array $matches) use ($urlSigner): string {
                $beforeHref = $matches[1];
                $quote = $matches[2];
                $originalUrl = trim($matches[3]);
                $afterHref = $matches[4];
                $anchorText = $matches[5];

                // Skip anchors, mailto, tel, and placeholders
                if ($this->shouldSkipLink($originalUrl)) {
                    return $matches[0];
                }

                $signedUrl = $urlSigner($originalUrl, strip_tags($anchorText));

                return sprintf('<a %shref=%s%s%s%s>%s</a>', $beforeHref, $quote, $signedUrl, $quote, $afterHref, $anchorText);
            },
            $html
        );
    }

    /**
     * Prepare a fully rendered email payload for delivery with tracking instrumentation.
     *
     * @param  callable(string, string): string  $urlSigner
     */
    public function prepareForDelivery(string $html, ?string $trackingPixelUrl = null, ?callable $urlSigner = null): string
    {
        if ($urlSigner !== null) {
            $html = $this->rewriteLinks($html, $urlSigner);
        }

        if ($trackingPixelUrl !== null && $trackingPixelUrl !== '') {
            $html = $this->injectTrackingPixel($html, $trackingPixelUrl);
        }

        return $html;
    }

    /**
     * Determine if a link destination should be excluded from click tracking.
     */
    protected function shouldSkipLink(string $url): bool
    {
        if ($url === '' || $url === '#') {
            return true;
        }

        $lower = strtolower($url);

        return str_starts_with($lower, 'mailto:')
            || str_starts_with($lower, 'tel:')
            || str_starts_with($lower, 'javascript:')
            || str_starts_with($lower, '#')
            || str_contains($lower, 'unsubscribe');
    }
}

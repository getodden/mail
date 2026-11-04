<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\UrlAttributeSanitizer;

final class MarkupUrlAttributeSanitizer implements AttributeSanitizerInterface
{
    private const INLINE_IMAGE = '#^data:image/(?:png|jpe?g|gif|webp);base64,[A-Za-z0-9+/]+={0,2}$#iD';

    private const REMOTE_IMAGE = '#^https?://#i';

    private const BACKGROUND_ELEMENTS = ['td', 'table', 'body'];

    private readonly UrlAttributeSanitizer $urls;

    public function __construct()
    {
        $this->urls = new UrlAttributeSanitizer;
    }

    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): ?array
    {
        return $this->urls->getSupportedAttributes();
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $value = trim($value);

        if ($element === 'img' && $attribute === 'src' && preg_match(self::INLINE_IMAGE, $value) === 1) {
            return SafeUrl::for($value, true) === $value ? $value : null;
        }

        if ($attribute === 'background' && in_array($element, self::BACKGROUND_ELEMENTS, true)) {
            if (preg_match(self::REMOTE_IMAGE, $value) !== 1 || SafeUrl::for($value, true) !== $value) {
                return null;
            }
        }

        return $this->urls->sanitizeAttribute($element, $attribute, $value, $config);
    }
}

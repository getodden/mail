<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

final class StyleAttributeSanitizer implements AttributeSanitizerInterface
{
    private const FORBIDDEN = ['url(', 'expression(', 'javascript:', 'behavior', '-moz-binding', '@import', 'image-set(', 'image(', 'src(', 'cross-fade(', 'element(', 'paint(', '\\', '/*', '*/', '<', '>'];

    /**
     * @var array<string, string>
     */
    private readonly array $probes;

    /**
     * @param  array<string, string>  $mergeTags
     */
    public function __construct(array $mergeTags = [])
    {
        $this->probes = array_map(static fn (string $original): string => MergeTagGuard::attributeToken($original) ?? '', $mergeTags);
    }

    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): array
    {
        return ['style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        return self::filter($value, $this->probes);
    }

    /**
     * @param  array<string, string>  $probes
     */
    public static function filter(string $style, array $probes = []): ?string
    {
        $kept = [];
        $removed = false;

        foreach (explode(';', $style) as $declaration) {
            $declaration = trim($declaration);

            if ($declaration === '') {
                continue;
            }

            if (self::isSafe($probes === [] ? $declaration : strtr($declaration, $probes))) {
                $kept[] = $declaration;
            } else {
                $removed = true;
            }
        }

        if (! $removed) {
            return $style;
        }

        return $kept === [] ? null : implode('; ', $kept);
    }

    private static function isSafe(string $declaration): bool
    {
        if (! str_contains($declaration, ':')) {
            return false;
        }

        $probe = strtolower(str_replace(["\0", "\t", "\n", "\r", "\f", "\v", ' '], '', $declaration));

        foreach (self::FORBIDDEN as $needle) {
            if (str_contains($probe, $needle)) {
                return false;
            }
        }

        return true;
    }
}

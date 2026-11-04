<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

final class StyleAttributeSanitizer implements AttributeSanitizerInterface
{
    private const FORBIDDEN = ['url(', 'expression(', 'javascript:', 'behavior', '-moz-binding', '@import', 'image-set(', 'image(', 'src(', 'cross-fade(', 'element(', 'paint(', '\\', '/*', '*/', '<', '>'];

    private const URL_PROPERTIES = ['background', 'background-image'];

    private const REMOTE_URL = '/(?<![a-z0-9_-])url\((?|\'(https?:\/\/[^\s\'"\\\\()<>*;{}]+)\'|"(https?:\/\/[^\s\'"\\\\()<>*;{}]+)"|(https?:\/\/[^\s\'"\\\\()<>*;{}]+))\)/i';

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
        $rewritten = false;

        foreach (explode(';', $style) as $declaration) {
            $declaration = trim($declaration);

            if ($declaration === '') {
                continue;
            }

            $safe = self::safeDeclaration($declaration, $probes);

            if ($safe === null) {
                $removed = true;

                continue;
            }

            $rewritten = $rewritten || $safe !== $declaration;
            $kept[] = $safe;
        }

        if (! $removed && ! $rewritten) {
            return $style;
        }

        return $kept === [] ? null : implode('; ', $kept);
    }

    /**
     * @param  array<string, string>  $probes
     */
    private static function safeDeclaration(string $declaration, array $probes): ?string
    {
        if (! str_contains($declaration, ':')) {
            return null;
        }

        $probe = $probes === [] ? $declaration : strtr($declaration, $probes);
        $canonical = $declaration;

        if (in_array(strtolower(trim(explode(':', $probe, 2)[0])), self::URL_PROPERTIES, true)) {
            $probe = preg_replace(self::REMOTE_URL, 'x', $probe, 1) ?? $probe;
            $canonical = preg_replace(self::REMOTE_URL, 'url($1)', $declaration, 1) ?? $declaration;
        }

        return self::isSafe($probe) ? $canonical : null;
    }

    private static function isSafe(string $declaration): bool
    {
        $probe = strtolower(str_replace(["\0", "\t", "\n", "\r", "\f", "\v", ' '], '', $declaration));

        foreach (self::FORBIDDEN as $needle) {
            if (str_contains($probe, $needle)) {
                return false;
            }
        }

        return true;
    }
}

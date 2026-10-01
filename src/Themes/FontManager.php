<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Themes;

class FontManager
{
    /**
     * Known web fonts and their Google Fonts query configurations.
     *
     * @var array<string, array{google_name: string, weights: string, mso_fallback: string}>
     */
    public const WEB_FONTS = [
        'inter' => [
            'google_name' => 'Inter',
            'weights' => 'wght@400;600;700;800',
            'mso_fallback' => 'Arial, Helvetica, sans-serif',
        ],
        'roboto' => [
            'google_name' => 'Roboto',
            'weights' => 'wght@400;500;700',
            'mso_fallback' => 'Arial, Helvetica, sans-serif',
        ],
        'open sans' => [
            'google_name' => 'Open+Sans',
            'weights' => 'wght@400;600;700',
            'mso_fallback' => 'Helvetica, Arial, sans-serif',
        ],
        'poppins' => [
            'google_name' => 'Poppins',
            'weights' => 'wght@400;600;700',
            'mso_fallback' => 'Arial, sans-serif',
        ],
        'merriweather' => [
            'google_name' => 'Merriweather',
            'weights' => 'wght@400;700',
            'mso_fallback' => "Georgia, 'Times New Roman', serif",
        ],
        'playfair display' => [
            'google_name' => 'Playfair+Display',
            'weights' => 'wght@400;700;900',
            'mso_fallback' => "Georgia, 'Times New Roman', serif",
        ],
        'fira code' => [
            'google_name' => 'Fira+Code',
            'weights' => 'wght@400;600',
            'mso_fallback' => "'Courier New', Courier, monospace",
        ],
    ];

    /**
     * Resolve Google Fonts import URL if a known web font is detected.
     */
    public static function getGoogleFontImportUrl(string $fontFamily): ?string
    {
        $lower = strtolower($fontFamily);
        foreach (self::WEB_FONTS as $key => $config) {
            if (str_contains($lower, $key)) {
                return "https://fonts.googleapis.com/css2?family={$config['google_name']}:{$config['weights']}&display=swap";
            }
        }

        return null;
    }

    /**
     * Resolve Microsoft Outlook MSO fallback font stack.
     */
    public static function getMsoFallback(string $fontFamily): string
    {
        $lower = strtolower($fontFamily);
        foreach (self::WEB_FONTS as $key => $config) {
            if (str_contains($lower, $key)) {
                return $config['mso_fallback'];
            }
        }

        if (str_contains($lower, 'serif') && ! str_contains($lower, 'sans-serif')) {
            return "Georgia, 'Times New Roman', serif";
        }

        if (str_contains($lower, 'mono') || str_contains($lower, 'code')) {
            return "'Courier New', Courier, monospace";
        }

        return 'Arial, Helvetica, sans-serif';
    }

    /**
     * Generate HTML snippet containing web font import and MSO fallback style tags for <head>.
     */
    public static function generateHeadTypography(string $fontFamily): string
    {
        $importUrl = self::getGoogleFontImportUrl($fontFamily);
        $msoFallback = self::getMsoFallback($fontFamily);

        $snippets = [];

        if ($importUrl !== null) {
            $snippets[] = "    <!-- Web Font Import -->\n    <link rel=\"stylesheet\" href=\"{$importUrl}\">";
        }

        $snippets[] = "    <!--[if mso]>\n    <style type=\"text/css\">\n        body, table, td, h1, h2, h3, h4, h5, h6, p, a, span {\n            font-family: {$msoFallback} !important;\n        }\n    </style>\n    <![endif]-->";

        return implode("\n", $snippets);
    }
}

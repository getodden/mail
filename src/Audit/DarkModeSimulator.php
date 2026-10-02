<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Audit;

class DarkModeSimulator
{
    /**
     * Common light-to-dark color inversion map representing Apple Mail and Outlook mobile rendering.
     *
     * @var array<string, string>
     */
    protected static array $colorMap = [
        '#ffffff' => '#1a1a1a',
        '#fff' => '#1a1a1a',
        'white' => '#1a1a1a',
        '#f8fafc' => '#0f172a',
        '#f1f5f9' => '#1e293b',
        '#e2e8f0' => '#334155',
        '#cbd5e1' => '#475569',
        '#0f172a' => '#f8fafc',
        '#1e293b' => '#f1f5f9',
        '#334155' => '#e2e8f0',
        '#475569' => '#cbd5e1',
        '#000000' => '#ffffff',
        '#000' => '#ffffff',
        'black' => '#ffffff',
    ];

    /**
     * Simulate dark mode inversion on an HTML email string and audit contrast risks.
     *
     * @return array{
     *     dark_html: string,
     *     warnings: list<string>,
     *     is_dark_mode_ready: bool
     * }
     */
    public static function simulate(string $html): array
    {
        $warnings = [];

        // 1. Check for dark mode meta tags
        $hasColorSchemeMeta = str_contains($html, 'color-scheme') && str_contains($html, 'light dark');
        if (! $hasColorSchemeMeta) {
            $warnings[] = "Missing '<meta name=\"color-scheme\" content=\"light dark\">'. Email clients may apply unpredictable OS color inversions.";
        }

        // 2. Scan images for potential transparent logo visibility risks
        if (preg_match_all('/<img\b([^>]*)>/i', $html, $matches)) {
            foreach ($matches[1] as $attrs) {
                $isLogo = (bool) preg_match('/(logo|brand|header)/i', $attrs);
                $isPngOrSvg = (bool) preg_match('/\.(png|svg)(\?.*)?["\']/i', $attrs);

                if ($isLogo && $isPngOrSvg) {
                    $warnings[] = 'Detected transparent logo graphic in email. Verify that dark text or black outlines inside the logo remain legible when inverted against a dark background.';
                    break;
                }
            }
        }

        // 3. Perform synthetic color inversion
        $darkHtml = $html;

        foreach (self::$colorMap as $light => $dark) {
            $darkHtml = str_ireplace("background-color: {$light}", "background-color: {$dark}", $darkHtml);
            $darkHtml = str_ireplace("background-color:{$light}", "background-color:{$dark}", $darkHtml);
            $darkHtml = str_ireplace("background: {$light}", "background: {$dark}", $darkHtml);
            $darkHtml = str_ireplace("color: {$light}", "color: {$dark}", $darkHtml);
            $darkHtml = str_ireplace("color:{$light}", "color:{$dark}", $darkHtml);
            $darkHtml = str_ireplace("border-color: {$light}", "border-color: {$dark}", $darkHtml);
        }

        // Inject simulated dark background class into body
        if (str_contains($darkHtml, '<body')) {
            $darkHtml = preg_replace('/<body\b([^>]*)style=["\']([^"\']*)["\']/i', '<body$1style="background-color: #121826; color: #f8fafc; $2"', $darkHtml) ?? $darkHtml;
        }

        return [
            'dark_html' => $darkHtml,
            'warnings' => $warnings,
            'is_dark_mode_ready' => empty($warnings),
        ];
    }
}

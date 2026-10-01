<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Audit;

class WcagContrastAuditor
{
    /**
     * Calculate exact WCAG 2.1 relative luminance for an sRGB hex color.
     */
    public static function getRelativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            return 0.0;
        }

        $r = hexdec(substr($hex, 0, 2)) / 255.0;
        $g = hexdec(substr($hex, 2, 2)) / 255.0;
        $b = hexdec(substr($hex, 4, 2)) / 255.0;

        $rLin = $r <= 0.04045 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $gLin = $g <= 0.04045 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $bLin = $b <= 0.04045 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $rLin + 0.7152 * $gLin + 0.0722 * $bLin;
    }

    /**
     * Calculate contrast ratio between two hex colors according to WCAG 2.1.
     * Ratio ranges from 1.0 to 21.0.
     */
    public static function calculateRatio(string $fgHex, string $bgHex): float
    {
        $l1 = self::getRelativeLuminance($fgHex);
        $l2 = self::getRelativeLuminance($bgHex);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    /**
     * Evaluate contrast ratio against WCAG 2.1 AA and AAA thresholds.
     *
     * @return array{
     *     ratio: float,
     *     aa_normal: bool,
     *     aa_large: bool,
     *     aaa_normal: bool,
     *     aaa_large: bool,
     *     rating: 'AAA'|'AA'|'Fail'
     * }
     */
    public static function evaluate(string $fgHex, string $bgHex): array
    {
        $ratio = self::calculateRatio($fgHex, $bgHex);

        $aaNormal = $ratio >= 4.5;
        $aaLarge = $ratio >= 3.0;
        $aaaNormal = $ratio >= 7.0;
        $aaaLarge = $ratio >= 4.5;

        $rating = match (true) {
            $aaaNormal => 'AAA',
            $aaNormal => 'AA',
            default => 'Fail',
        };

        return [
            'ratio' => $ratio,
            'aa_normal' => $aaNormal,
            'aa_large' => $aaLarge,
            'aaa_normal' => $aaaNormal,
            'aaa_large' => $aaaLarge,
            'rating' => $rating,
        ];
    }

    /**
     * Audit an entire email theme configuration for color accessibility.
     *
     * @param  array<string, mixed>  $theme
     * @return array{
     *     score: int,
     *     passes: bool,
     *     pairs: array<string, array{foreground: string, background: string, ratio: float, rating: string, passes_aa: bool}>,
     *     recommendations: list<string>
     * }
     */
    public static function auditTheme(array $theme): array
    {
        $textColor = (string) ($theme['text_color'] ?? '#334155');
        $headingColor = (string) ($theme['heading_color'] ?? '#0f172a');
        $primaryColor = (string) ($theme['primary_color'] ?? '#2563eb');
        $contentBg = (string) ($theme['content_background_color'] ?? '#ffffff');

        $pairs = [
            'body_text_on_card' => [
                'foreground' => $textColor,
                'background' => $contentBg,
            ],
            'heading_on_card' => [
                'foreground' => $headingColor,
                'background' => $contentBg,
            ],
            'button_text_on_primary' => [
                'foreground' => '#ffffff',
                'background' => $primaryColor,
            ],
        ];

        $results = [];
        $recommendations = [];
        $passedCount = 0;

        foreach ($pairs as $key => $pair) {
            $eval = self::evaluate($pair['foreground'], $pair['background']);
            $passes = $eval['aa_normal'];

            if ($passes) {
                $passedCount++;
            } else {
                $recommendations[] = "Low contrast ({$eval['ratio']}:1) for {$key} [{$pair['foreground']} on {$pair['background']}]. WCAG AA requires at least 4.5:1.";
            }

            $results[$key] = [
                'foreground' => $pair['foreground'],
                'background' => $pair['background'],
                'ratio' => $eval['ratio'],
                'rating' => $eval['rating'],
                'passes_aa' => $passes,
            ];
        }

        $totalPairs = count($pairs);
        $score = (int) round(($passedCount / $totalPairs) * 100);

        return [
            'score' => $score,
            'passes' => $passedCount === $totalPairs,
            'pairs' => $results,
            'recommendations' => $recommendations,
        ];
    }
}

<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Audit;

class RenderPerformanceAuditor
{
    public const OPTIMAL_DOM_NODES = 600;

    public const MAX_RECOMMENDED_DOM_NODES = 1200;

    public const MAX_SAFE_TABLE_DEPTH = 8;

    /**
     * Audit email HTML structure and calculate client render performance benchmarks.
     *
     * @return array{
     *     dom_nodes_count: int,
     *     dom_status: 'optimal'|'good'|'warning'|'critical',
     *     nested_table_depth: int,
     *     table_depth_status: 'optimal'|'good'|'warning'|'critical',
     *     image_count: int,
     *     html_size_kb: float,
     *     estimated_download_ms: array{
     *         slow_3g: int,
     *         fast_4g: int,
     *         wifi_5g: int
     *     },
     *     recommendations: list<string>
     * }
     */
    public static function benchmark(string $html): array
    {
        $bytes = strlen($html);
        $sizeKb = round($bytes / 1024, 2);

        // 1. Calculate DOM nodes count
        preg_match_all('/<([a-z0-9]+)\b[^>]*>/i', $html, $elementMatches);
        $domNodesCount = count($elementMatches[0]);

        $domStatus = match (true) {
            $domNodesCount <= self::OPTIMAL_DOM_NODES => 'optimal',
            $domNodesCount <= 1000 => 'good',
            $domNodesCount <= self::MAX_RECOMMENDED_DOM_NODES => 'warning',
            default => 'critical',
        };

        // 2. Calculate nested table depth
        $nestedTableDepth = self::calculateMaxTableDepth($html);

        $tableDepthStatus = match (true) {
            $nestedTableDepth <= 5 => 'optimal',
            $nestedTableDepth <= 7 => 'good',
            $nestedTableDepth <= self::MAX_SAFE_TABLE_DEPTH => 'warning',
            default => 'critical',
        };

        // 3. Count images
        preg_match_all('/<img\b[^>]*>/i', $html, $imgMatches);
        $imageCount = count($imgMatches[0]);

        // 4. Calculate estimated network payload download times
        // Slow 3G: ~400 KB/s + 250ms RTT
        $slow3gMs = (int) round(($sizeKb / 400) * 1000 + 250);
        // Fast 4G: ~2500 KB/s + 50ms RTT
        $fast4gMs = (int) round(($sizeKb / 2500) * 1000 + 50);
        // 5G / High-speed Fiber: ~10000 KB/s + 15ms RTT
        $wifi5gMs = (int) round(($sizeKb / 10000) * 1000 + 15);

        $recommendations = [];
        if ($domStatus === 'critical') {
            $recommendations[] = "High DOM node count ({$domNodesCount} elements). Older mobile devices and Gmail app may suffer from render lag and scrolling stutter.";
        } elseif ($domStatus === 'warning') {
            $recommendations[] = "DOM node count is elevated ({$domNodesCount} elements). Aim for under 1,000 elements for fastest client rendering.";
        }

        if ($tableDepthStatus === 'critical') {
            $recommendations[] = "Nested table depth ({$nestedTableDepth} levels) exceeds safe limits. Outlook Windows desktop engines can crash or truncate layouts beyond 8 nested tables.";
        } elseif ($tableDepthStatus === 'warning') {
            $recommendations[] = "Table nesting depth is {$nestedTableDepth} levels. Consider flattening column tables where possible.";
        }

        if ($sizeKb > 100.0) {
            $recommendations[] = "HTML size ({$sizeKb} KB) is nearing or above the 102 KB Gmail clipping threshold.";
        }

        if ($imageCount > 15) {
            $recommendations[] = "Template contains {$imageCount} images. High image counts increase HTTP requests and increase the likelihood of images being blocked by default.";
        }

        return [
            'dom_nodes_count' => $domNodesCount,
            'dom_status' => $domStatus,
            'nested_table_depth' => $nestedTableDepth,
            'table_depth_status' => $tableDepthStatus,
            'image_count' => $imageCount,
            'html_size_kb' => $sizeKb,
            'estimated_download_ms' => [
                'slow_3g' => $slow3gMs,
                'fast_4g' => $fast4gMs,
                'wifi_5g' => $wifi5gMs,
            ],
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Calculate maximum nested <table> depth in an HTML string.
     */
    protected static function calculateMaxTableDepth(string $html): int
    {
        $maxDepth = 0;
        $currentDepth = 0;

        preg_match_all('/<\/?table\b[^>]*>/i', $html, $matches);
        foreach ($matches[0] as $tag) {
            if (str_starts_with(strtolower($tag), '</table')) {
                $currentDepth = max(0, $currentDepth - 1);
            } else {
                $currentDepth++;
                if ($currentDepth > $maxDepth) {
                    $maxDepth = $currentDepth;
                }
            }
        }

        return $maxDepth;
    }
}

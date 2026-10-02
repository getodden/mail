<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Compilers;

use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\MailBuilder;

class PlainTextDiffInspector
{
    /**
     * Inspect and compare custom plain text against auto-extracted visual slot plain text.
     *
     * @param  list<array<string, mixed>>|EmailDocument  $slotsOrDoc
     * @return array{
     *     is_in_sync: bool,
     *     similarity_percentage: float,
     *     character_difference: int,
     *     extracted_text: string,
     *     custom_text: string,
     *     missing_merge_tags: list<string>,
     *     recommendations: list<string>
     * }
     */
    public static function inspect(array|EmailDocument $slotsOrDoc, string $customPlainText): array
    {
        $extracted = MailBuilder::plainText($slotsOrDoc);
        $extractedClean = trim(str_replace("\r\n", "\n", $extracted));
        $customClean = trim(str_replace("\r\n", "\n", $customPlainText));

        $extractedLen = mb_strlen($extractedClean);
        $customLen = mb_strlen($customClean);
        $diffLen = abs($customLen - $extractedLen);

        // Find merge tags in visual extracted text: {{ tag }}
        $extractedTags = [];
        if (preg_match_all('/\{\{\s*([a-zA-Z0-9_\.]+)[^}]*\}\}/', $extractedClean, $tagMatches)) {
            $extractedTags = array_unique($tagMatches[1]);
        }

        // Find missing merge tags in custom text
        $missingTags = [];
        foreach ($extractedTags as $tag) {
            if (! str_contains($customClean, $tag)) {
                $missingTags[] = $tag;
            }
        }

        // Calculate Levenshtein / similar_text similarity percentage
        $similarity = 0.0;
        if ($extractedLen === 0 && $customLen === 0) {
            $similarity = 100.0;
        } elseif ($extractedLen > 0 && $customLen > 0) {
            similar_text(mb_substr($extractedClean, 0, 5000), mb_substr($customClean, 0, 5000), $similarity);
        }

        $isInSync = $similarity >= 85.0 && empty($missingTags);

        $recommendations = [];
        if ($customLen === 0 && $extractedLen > 0) {
            $recommendations[] = 'Custom plain text is completely blank. Inboxes with images disabled will display nothing.';
        } elseif (! empty($missingTags)) {
            $tagsList = implode(', ', $missingTags);
            $recommendations[] = "Custom plain text is missing active merge tags present in visual layout: [{$tagsList}].";
        }

        if ($similarity < 70.0 && $customLen > 0) {
            $recommendations[] = "Plain text content deviates significantly ({$similarity}% similarity) from visual layout. Consider 1-click sync to refresh.";
        }

        return [
            'is_in_sync' => $isInSync,
            'similarity_percentage' => round($similarity, 1),
            'character_difference' => $diffLen,
            'extracted_text' => $extractedClean,
            'custom_text' => $customClean,
            'missing_merge_tags' => $missingTags,
            'recommendations' => $recommendations,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Compilers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\MergeTags\MergeTagInterpolator;
use Odden\MailBuilder\Support\BlockMergeTags;
use Odden\MailBuilder\Support\HtmlUrlScrubber;
use Odden\MailBuilder\Support\MergeTagGuard;
use Odden\MailBuilder\Support\OutboundUrlGuard;
use Odden\MailBuilder\Support\SafeColor;
use Odden\MailBuilder\Support\SlotDataSanitizer;
use Odden\MailBuilder\Themes\FontManager;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class EmailSlotCompiler
{
    protected ?MergeTagInterpolator $interpolator = null;

    protected ?CssToInlineStyles $inliner = null;

    /**
     * @param  array<string, mixed>  $defaultConfig
     */
    public function __construct(
        protected ViewFactory $view,
        protected array $defaultConfig = [],
        ?MergeTagInterpolator $interpolator = null,
        ?CssToInlineStyles $inliner = null,
    ) {
        $this->interpolator = $interpolator ?? app(MergeTagInterpolator::class);
        $this->inliner = $inliner ?? new CssToInlineStyles;
    }

    /**
     * Compile an EmailDocument into full bulletproof HTML.
     *
     * @param  array<string, mixed>  $options
     */
    public function compileDocument(EmailDocument $document, array $options = []): string
    {
        $theme = SafeColor::theme(array_merge($this->defaultConfig, $document->theme, $options['theme'] ?? []));
        /** @var array<string, mixed>|null $context */
        $context = $options['context'] ?? null;
        $inlineCss = (bool) ($options['inline_css'] ?? true);
        $interpolate = (bool) ($options['interpolate'] ?? false);

        $editorMarkers = (bool) ($options['editor_markers'] ?? false);

        BlockMergeTags::assertValid('subject', $document->subject);
        BlockMergeTags::assertValid('preview_text', $document->previewText);

        $slotsHtml = '';
        foreach ($document->slots as $index => $slot) {
            if ($context !== null && ! $slot->matchesContext($context)) {
                continue;
            }

            $compiled = $this->compileSlot($slot, $theme, ['fetch_feeds' => $options['fetch_feeds'] ?? true]);

            $slotsHtml .= $editorMarkers ? '<div data-odden-slot="'.$index.'">'.$compiled.'</div>' : $compiled;
        }

        $previewText = $document->previewText;
        $subject = $document->subject;

        $rawHtml = $this->view->make('mail-builder::layout', [
            'content' => $slotsHtml,
            'previewText' => $previewText,
            'subject' => $subject,
            'theme' => $theme,
            'direction' => $options['direction'] ?? ($theme['direction'] ?? 'ltr'),
        ])->render();

        if ($interpolate && $context !== null && $this->interpolator !== null) {
            $rawHtml = $this->interpolator->interpolate($rawHtml, $context, escape: true);
        }

        if (! empty($theme['font_family']) && is_string($theme['font_family']) && str_contains($rawHtml, '</head>')) {
            $typography = FontManager::generateHeadTypography($theme['font_family']);
            $rawHtml = str_replace('</head>', "{$typography}\n</head>", $rawHtml);
        }

        if (! empty($options['gmail_action']) || ! empty($options['gmail_actions'])) {
            $compiler = new GmailActionCompiler;
            if (! empty($options['gmail_action']) && is_array($options['gmail_action'])) {
                $compiler->addAction($options['gmail_action']);
            }
            if (! empty($options['gmail_actions']) && is_array($options['gmail_actions'])) {
                foreach ($options['gmail_actions'] as $act) {
                    if (is_array($act)) {
                        $compiler->addAction($act);
                    }
                }
            }
            $script = $compiler->toScript();
            if (! empty($script) && str_contains($rawHtml, '</head>')) {
                $rawHtml = str_replace('</head>', "    {$script}\n</head>", $rawHtml);
            }
        }

        if ($inlineCss && $this->inliner !== null) {
            $rawHtml = $this->inlineCss($this->inliner, $rawHtml);
            $rawHtml = preg_replace('/^<!doctype html>/i', '<!DOCTYPE html>', $rawHtml) ?? $rawHtml;
        }

        if ((bool) ($options['optimize_images'] ?? true)) {
            $rawHtml = EmailImageOptimizer::optimize($rawHtml);
        }

        return HtmlUrlScrubber::scrub($rawHtml);
    }

    protected function inlineCss(CssToInlineStyles $inliner, string $html): string
    {
        [$protected, $mergeTags] = MergeTagGuard::protect($html);

        return MergeTagGuard::restore($inliner->convert($protected), $mergeTags);
    }

    /**
     * Compile a raw array of slots into full HTML.
     *
     * @param  list<array<string, mixed>>  $slots
     * @param  array<string, mixed>  $options
     */
    public function compileSlots(array $slots, array $options = []): string
    {
        $document = EmailDocument::fromArray([
            'slots' => $slots,
            'preview_text' => $options['preview_text'] ?? null,
            'subject' => $options['subject'] ?? null,
            'theme' => $options['theme'] ?? $options,
        ]);

        return $this->compileDocument($document, $options);
    }

    /**
     * Render an individual slot to HTML.
     *
     * @param  array<string, mixed>  $theme
     * @param  array<string, mixed>  $options
     */
    public function compileSlot(EmailSlot $slot, array $theme = [], array $options = []): string
    {
        $viewName = $slot->viewName();

        if (! $this->view->exists($viewName)) {
            return '';
        }

        $data = $slot->data;

        // If slot has a remote feed URL, attempt runtime hydration
        $fetchFeeds = (bool) ($options['fetch_feeds'] ?? true);

        if ($fetchFeeds && ($slot->type === SlotType::DynamicFeed || $slot->type === SlotType::RssFeed) && ! empty($data['feed_url'])) {
            $data = $this->hydrateFeedSlot($data);
            $slot = new EmailSlot(
                type: $slot->type,
                data: $data,
                visibility: $slot->visibility
            );
        }

        $slot = SlotDataSanitizer::slot(new EmailSlot(type: $slot->type, data: $data, visibility: $slot->visibility));
        $data = $slot->data;

        return $this->view->make($viewName, [
            'slot' => $slot,
            'data' => $data,
            'theme' => SafeColor::theme(array_merge($this->defaultConfig, $theme)),
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hydrateFeedSlot(array $data): array
    {
        $feedUrl = (string) ($data['feed_url'] ?? '');
        if (! str_starts_with($feedUrl, 'http://') && ! str_starts_with($feedUrl, 'https://')) {
            return $data;
        }

        try {
            $guard = app(OutboundUrlGuard::class);
            $response = $guard->request($feedUrl)->get($feedUrl);

            if ($response->successful() && strlen($response->body()) <= $guard->maxBytes()) {
                $json = $response->json();
                if (is_array($json)) {
                    $items = isset($json['items']) && is_array($json['items']) ? $json['items'] : $json;
                    if (! empty($items)) {
                        $data['items'] = array_values($items);
                    }
                }
            }
        } catch (\Throwable) {
            // A refused or failed fetch falls back to the configured static items.
        }

        return $data;
    }
}

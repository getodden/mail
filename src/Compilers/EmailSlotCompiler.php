<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Compilers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\MergeTags\MergeTagInterpolator;
use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;
use Odden\MailBuilder\Support\HtmlUrlScrubber;
use Odden\MailBuilder\Support\OutboundUrlGuard;
use Odden\MailBuilder\Support\SafeColor;
use Odden\MailBuilder\Support\SafeUrl;
use Odden\MailBuilder\Themes\FontManager;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class EmailSlotCompiler
{
    protected ?MergeTagInterpolator $interpolator = null;

    protected ?CssToInlineStyles $inliner = null;

    /** @var array<string, list<SlotField>> */
    protected array $fieldsByKey = [];

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
        $theme = array_merge($this->defaultConfig, $document->theme, $options['theme'] ?? []);
        /** @var array<string, mixed>|null $context */
        $context = $options['context'] ?? null;
        $inlineCss = (bool) ($options['inline_css'] ?? true);
        $interpolate = (bool) ($options['interpolate'] ?? false);

        $editorMarkers = (bool) ($options['editor_markers'] ?? false);

        $slotsHtml = '';
        foreach ($document->slots as $index => $slot) {
            if ($context !== null && ! $slot->matchesContext($context)) {
                continue;
            }

            $compiled = $this->compileSlot($slot, $theme);

            // The drag-and-drop editor finds each slot in the preview by this marker. Never set it for a real send.
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
            $rawHtml = $this->interpolator->interpolate($rawHtml, $context);
        }

        // Inject Web Font imports & MSO typography fallbacks if specified
        if (! empty($theme['font_family']) && is_string($theme['font_family']) && str_contains($rawHtml, '</head>')) {
            $typography = FontManager::generateHeadTypography($theme['font_family']);
            $rawHtml = str_replace('</head>', "{$typography}\n</head>", $rawHtml);
        }

        // Inject Gmail Quick Action (Schema.org) if provided
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
            $rawHtml = $this->inliner->convert($rawHtml);
            $rawHtml = preg_replace('/^<!doctype html>/i', '<!DOCTYPE html>', $rawHtml) ?? $rawHtml;
        }

        // Optimize image tags for Outlook MSO and Retina displays
        if ((bool) ($options['optimize_images'] ?? true)) {
            $rawHtml = EmailImageOptimizer::optimize($rawHtml);
        }

        return HtmlUrlScrubber::scrub($rawHtml);
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
     */
    public function compileSlot(EmailSlot $slot, array $theme = []): string
    {
        $viewName = $slot->viewName();

        if (! $this->view->exists($viewName)) {
            return '';
        }

        $data = $slot->data;

        // If slot has a remote feed URL, attempt runtime hydration
        if (($slot->type === SlotType::DynamicFeed || $slot->type === SlotType::RssFeed) && ! empty($data['feed_url'])) {
            $data = $this->hydrateFeedSlot($data);
            $slot = new EmailSlot(
                type: $slot->type,
                data: $data,
                visibility: $slot->visibility
            );
        }

        $data = $this->sanitize($data, $this->fieldsFor($slot->key()));
        $slot = new EmailSlot(type: $slot->type, data: $data, visibility: $slot->visibility);

        return $this->view->make($viewName, [
            'slot' => $slot,
            'data' => $data,
            'theme' => array_merge($this->defaultConfig, $theme),
        ])->render();
    }

    /**
     * @return list<SlotField>
     */
    protected function fieldsFor(string $key): array
    {
        if (SlotType::tryFrom($key) === null) {
            return array_values(SlotSchemaRegistry::forKey($key)?->fields ?? []);
        }

        return $this->fieldsByKey[$key] ??= array_values(SlotSchemaRegistry::forKey($key)?->fields ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, SlotField>  $fields
     * @return array<string, mixed>
     */
    protected function sanitize(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            $value = $data[$field->key] ?? null;

            if ($field->type === FieldType::Items && is_array($value)) {
                $data[$field->key] = array_map(
                    fn (mixed $item): mixed => is_array($item) ? $this->sanitize($item, $field->items) : $item,
                    $value,
                );

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            match ($field->type) {
                FieldType::Url => $data[$field->key] = SafeUrl::for($value),
                FieldType::Image => $data[$field->key] = SafeUrl::for($value, true),
                FieldType::Color => $this->sanitizeColor($data, $field->key, $value),
                default => null,
            };
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function sanitizeColor(array &$data, string $key, string $value): void
    {
        $color = SafeColor::for($value);

        if ($color === null) {
            unset($data[$key]);

            return;
        }

        $data[$key] = $color;
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

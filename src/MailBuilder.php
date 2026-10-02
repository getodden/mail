<?php

declare(strict_types=1);

namespace Odden\MailBuilder;

use Odden\MailBuilder\Audit\DarkModeSimulator;
use Odden\MailBuilder\Audit\DevicePreviewService;
use Odden\MailBuilder\Audit\DnsDeliverabilityValidator;
use Odden\MailBuilder\Audit\EmailPreFlightAuditor;
use Odden\MailBuilder\Audit\InboxEnvelopeSimulator;
use Odden\MailBuilder\Audit\PreFlightAuditResult;
use Odden\MailBuilder\Audit\RenderPerformanceAuditor;
use Odden\MailBuilder\Audit\WcagContrastAuditor;
use Odden\MailBuilder\Compilers\AmpEmailCompiler;
use Odden\MailBuilder\Compilers\EmailImageOptimizer;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Compilers\GmailActionCompiler;
use Odden\MailBuilder\Compilers\PlainTextDiffInspector;
use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Conditions\SlotVisibilityEvaluator;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Exporters\TemplatePackageExporter;
use Odden\MailBuilder\MergeTags\MergeTagRegistry;
use Odden\MailBuilder\Parsers\HtmlToSlotsParser;
use Odden\MailBuilder\Parsers\MjmlToSlotsParser;
use Odden\MailBuilder\Presets\PresetRegistry;
use Odden\MailBuilder\Themes\ThemeRegistry;
use Odden\MailBuilder\Tracking\EmailTrackingPipeline;
use Odden\MailBuilder\Transport\CidImageEmbedder;

class MailBuilder
{
    /**
     * Start a new blank EmailDocument.
     *
     * @param  array<string, mixed>  $theme
     */
    public static function document(?string $subject = null, ?string $previewText = null, array $theme = []): EmailDocument
    {
        return new EmailDocument(
            subject: $subject,
            previewText: $previewText,
            theme: $theme,
        );
    }

    /**
     * Compile an array of slots or an EmailDocument into responsive HTML.
     *
     * @param  list<array<string, mixed>>|EmailDocument  $slotsOrDoc
     * @param  array<string, mixed>  $options
     */
    public static function compile(array|EmailDocument $slotsOrDoc, array $options = []): string
    {
        /** @var EmailSlotCompiler $compiler */
        $compiler = app(EmailSlotCompiler::class);

        if ($slotsOrDoc instanceof EmailDocument) {
            return $compiler->compileDocument($slotsOrDoc);
        }

        return $compiler->compileSlots($slotsOrDoc, $options);
    }

    /**
     * Compile an array of slots or EmailDocument into AMP for Email (⚡4email) markup.
     *
     * @param  list<array<string, mixed>>|EmailDocument  $slotsOrDoc
     * @param  array<string, mixed>  $options
     */
    public static function amp(array|EmailDocument $slotsOrDoc, array $options = []): string
    {
        /** @var AmpEmailCompiler $compiler */
        $compiler = app(AmpEmailCompiler::class);

        return $compiler->compile($slotsOrDoc, $options);
    }

    /**
     * Extract plain text fallback from slots, document, or HTML string.
     *
     * @param  list<array<string, mixed>>|EmailDocument|string  $content
     */
    public static function plainText(array|EmailDocument|string $content): string
    {
        /** @var PlainTextExtractor $extractor */
        $extractor = app(PlainTextExtractor::class);

        if ($content instanceof EmailDocument) {
            return $extractor->extractFromDocument($content);
        }

        if (is_array($content)) {
            return $extractor->extractFromSlotArray($content);
        }

        return $extractor->extractFromHtml($content);
    }

    /**
     * Retrieve the preset registry.
     */
    public static function presets(): PresetRegistry
    {
        return app(PresetRegistry::class);
    }

    /**
     * Retrieve an EmailDocument pre-configured from a preset key.
     */
    public static function preset(string $key): EmailDocument
    {
        return self::presets()->get($key)->document();
    }

    /**
     * Retrieve the merge tag registry.
     */
    public static function mergeTags(): MergeTagRegistry
    {
        return app(MergeTagRegistry::class);
    }

    /**
     * Interpolate merge tags into content using recipient context or sample fallback.
     *
     * @param  array<string, mixed>|null  $context
     */
    public static function interpolate(string $content, ?array $context = null): string
    {
        return self::mergeTags()->interpolate($content, $context);
    }

    /**
     * Run a pre-flight deliverability, spam, and compliance audit on an email.
     *
     * @param  list<array<string, mixed>>|EmailDocument|string  $content
     * @param  array<string, mixed>  $options
     */
    public static function audit(array|EmailDocument|string $content, array $options = []): PreFlightAuditResult
    {
        /** @var EmailPreFlightAuditor $auditor */
        $auditor = app(EmailPreFlightAuditor::class);

        return $auditor->audit($content, $options);
    }

    /**
     * Retrieve the email tracking pipeline service.
     */
    public static function tracking(): EmailTrackingPipeline
    {
        return app(EmailTrackingPipeline::class);
    }

    /**
     * Retrieve the audience slot visibility evaluator.
     */
    public static function visibility(): SlotVisibilityEvaluator
    {
        return app(SlotVisibilityEvaluator::class);
    }

    /**
     * Retrieve the template package exporter service.
     */
    public static function exporter(): TemplatePackageExporter
    {
        return app(TemplatePackageExporter::class);
    }

    /**
     * Simulate inbox envelope and character limits for Gmail, Apple Mail, and Outlook.
     *
     * @return array<string, mixed>
     */
    public static function simulateEnvelope(
        string $subject,
        ?string $previewText = null,
        ?string $fromName = null,
        ?string $fromEmail = null
    ): array {
        return InboxEnvelopeSimulator::simulate($subject, $previewText, $fromName, $fromEmail);
    }

    /**
     * Optimize HTML images for Outlook MSO table layouts and Retina screens.
     */
    public static function optimizeImages(string $html): string
    {
        return EmailImageOptimizer::optimize($html);
    }

    /**
     * Create a new Gmail Quick Action (Schema.org) builder.
     */
    public static function gmailAction(): GmailActionCompiler
    {
        return GmailActionCompiler::make();
    }

    /**
     * Validate SPF, DMARC, and BIMI DNS records for an email sender domain.
     *
     * @return array<string, mixed>
     */
    public static function validateDns(string $domain): array
    {
        return DnsDeliverabilityValidator::validate($domain);
    }

    /**
     * Simulate dark mode email client rendering and audit contrast issues.
     *
     * @return array<string, mixed>
     */
    public static function simulateDarkMode(string $html): array
    {
        return DarkModeSimulator::simulate($html);
    }

    /**
     * Inspect and compare custom plain text with auto-extracted visual plain text.
     *
     * @param  list<array<string, mixed>>|EmailDocument  $slotsOrDoc
     * @return array<string, mixed>
     */
    public static function diffPlainText(array|EmailDocument $slotsOrDoc, string $customPlainText): array
    {
        return PlainTextDiffInspector::inspect($slotsOrDoc, $customPlainText);
    }

    /**
     * Generate multi-device client screenshot preview statuses and URLs.
     *
     * @param  list<string>  $devices
     * @return array<string, array{status: 'ready'|'pending'|'failed', url: ?string, device_name: string}>
     */
    public static function devicePreviews(string $html, array $devices = []): array
    {
        return DevicePreviewService::render($html, $devices);
    }

    /**
     * Retrieve the theme and design system registry.
     */
    public static function themes(): ThemeRegistry
    {
        return app(ThemeRegistry::class);
    }

    /**
     * Benchmark email DOM complexity, nested table depth, and estimated download times.
     *
     * @return array<string, mixed>
     */
    public static function benchmarkRender(string $html): array
    {
        return RenderPerformanceAuditor::benchmark($html);
    }

    /**
     * Ingest and parse raw external HTML into an EmailDocument with modular slots.
     */
    public static function importHtml(string $html, ?string $subject = null): EmailDocument
    {
        return HtmlToSlotsParser::parse($html, $subject);
    }

    /**
     * Ingest and parse standard MJML XML markup into an EmailDocument with modular slots.
     */
    public static function importMjml(string $mjml, ?string $subject = null): EmailDocument
    {
        return MjmlToSlotsParser::parse($mjml, $subject);
    }

    /**
     * Calculate and evaluate WCAG 2.1 AA/AAA contrast ratio between two hex colors.
     *
     * @return array{ratio: float, aa_normal: bool, aa_large: bool, aaa_normal: bool, aaa_large: bool, rating: 'AAA'|'AA'|'Fail'}
     */
    public static function wcagContrast(string $fgHex, string $bgHex): array
    {
        return WcagContrastAuditor::evaluate($fgHex, $bgHex);
    }

    /**
     * Audit a theme configuration for WCAG 2.1 color contrast compliance.
     *
     * @param  array<string, mixed>  $theme
     * @return array{score: int, passes: bool, pairs: array<string, array{foreground: string, background: string, ratio: float, rating: string, passes_aa: bool}>, recommendations: list<string>}
     */
    public static function auditContrast(array $theme): array
    {
        return WcagContrastAuditor::auditTheme($theme);
    }

    /**
     * Extract base64 and local images into MIME CID inline attachments.
     *
     * @return array{html: string, attachments: list<array{name: string, data: string, mime: string, cid: string}>}
     */
    public static function embedCidImages(string $html, ?string $basePath = null): array
    {
        return CidImageEmbedder::embed($html, $basePath);
    }
}

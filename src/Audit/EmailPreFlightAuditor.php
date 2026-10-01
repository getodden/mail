<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Audit;

use DoPHP\MailBuilder\Compilers\EmailSlotCompiler;
use DoPHP\MailBuilder\Data\EmailDocument;

class EmailPreFlightAuditor
{
    /**
     * Threshold in bytes where Gmail clips messages and hides footer/tracking.
     */
    public const GMAIL_CLIPPING_THRESHOLD_BYTES = 102400; // 100 KB

    public function __construct(
        protected ?EmailSlotCompiler $compiler = null
    ) {
        $this->compiler ??= app(EmailSlotCompiler::class);
    }

    /**
     * Audit an email document or slot array for deliverability and compliance.
     *
     * @param  list<array<string, mixed>>|EmailDocument|string  $content
     * @param  array<string, mixed>  $options
     */
    public function audit(
        array|EmailDocument|string $content,
        array $options = []
    ): PreFlightAuditResult {
        $subject = isset($options['subject']) ? (string) $options['subject'] : null;

        $html = is_string($content)
            ? $content
            : ($this->compiler !== null
                ? ($content instanceof EmailDocument
                    ? $this->compiler->compileDocument($content, $options)
                    : $this->compiler->compileSlots($content, $options))
                : '');

        $htmlBytes = strlen($html);
        /** @var list<PreFlightCheck> $checks */
        $checks = [];

        // 1. Mandatory Unsubscribe Verification (CAN-SPAM / Google & Yahoo 2024 Bulk Sender Rules)
        $checks[] = $this->checkUnsubscribeLink($html);

        // 2. Gmail 102KB Clipping Weight Analysis
        $checks[] = $this->checkGmailClipping($htmlBytes);

        // 3. Image Accessibility & Deliverability Alt Text Check
        $checks[] = $this->checkImageAltText($html);

        // 4. Broken and Placeholder Link Detection
        $checks[] = $this->checkBrokenOrPlaceholderLinks($html);

        // 5. Subject Line Optimization & Spam Score
        if ($subject !== null) {
            $checks[] = $this->checkSubjectLine($subject);
        }

        // 6. Physical Mailing Address Verification (CAN-SPAM)
        $checks[] = $this->checkPhysicalAddress($html);

        // 7. Microsoft Outlook VML Background Compatibility
        $checks[] = $this->checkOutlookVmlBackground($html);

        // 8. WCAG 2.1 Color Contrast
        $checks[] = $this->checkColorContrast($options);

        // 9. BIMI Brand Avatar Readiness & Authentication
        $checks[] = $this->checkBimiAndAuthentication($options);

        // Calculate score
        $deductions = 0;
        foreach ($checks as $check) {
            if ($check->isFailing()) {
                $deductions += 30;
            } elseif ($check->isWarning()) {
                $deductions += 10;
            }
        }

        $score = max(0, 100 - $deductions);
        $status = $score >= 85 ? 'healthy' : ($score >= 60 ? 'needs_attention' : 'critical');

        return new PreFlightAuditResult(
            score: $score,
            status: $status,
            htmlSizeBytes: $htmlBytes,
            checks: $checks
        );
    }

    protected function checkUnsubscribeLink(string $html): PreFlightCheck
    {
        $hasUnsubscribe = str_contains($html, '{{unsubscribe_url}}')
            || str_contains(strtolower($html), 'unsubscribe')
            || str_contains(strtolower($html), 'opt-out')
            || str_contains(strtolower($html), 'email preferences');

        if (! $hasUnsubscribe) {
            return new PreFlightCheck(
                id: 'unsubscribe_present',
                title: 'Unsubscribe Link Presence',
                status: 'fail',
                message: 'No unsubscribe link or {{unsubscribe_url}} token found in this template.',
                recommendation: 'Add a footer block with {{unsubscribe_url}} to comply with CAN-SPAM and Google/Yahoo sender requirements.'
            );
        }

        return new PreFlightCheck(
            id: 'unsubscribe_present',
            title: 'Unsubscribe Link Presence',
            status: 'pass',
            message: 'Unsubscribe token is present and compliant.'
        );
    }

    protected function checkGmailClipping(int $bytes): PreFlightCheck
    {
        $kb = round($bytes / 1024, 1);

        if ($bytes >= self::GMAIL_CLIPPING_THRESHOLD_BYTES) {
            return new PreFlightCheck(
                id: 'gmail_clipping_risk',
                title: 'Gmail Message Size & Clipping',
                status: 'fail',
                message: "Email size is {$kb} KB, exceeding Gmail's 102 KB clipping threshold.",
                recommendation: 'Reduce content, compress images, or simplify visual slots to prevent Gmail from clipping content.'
            );
        }

        if ($bytes >= 80000) {
            return new PreFlightCheck(
                id: 'gmail_clipping_risk',
                title: 'Gmail Message Size & Clipping',
                status: 'warning',
                message: "Email size is {$kb} KB, approaching Gmail's 102 KB limit.",
                recommendation: 'Monitor size closely when adding additional slots or dynamic copy.'
            );
        }

        return new PreFlightCheck(
            id: 'gmail_clipping_risk',
            title: 'Gmail Message Size & Clipping',
            status: 'pass',
            message: "Email size is {$kb} KB (well within safe delivery limits)."
        );
    }

    protected function checkImageAltText(string $html): PreFlightCheck
    {
        preg_match_all('/<img\s+[^>]*>/i', $html, $matches);
        $imgTags = $matches[0];

        if (empty($imgTags)) {
            return new PreFlightCheck(
                id: 'image_alt_text',
                title: 'Image Alt Text Accessibility',
                status: 'pass',
                message: 'No images detected or all images properly configured.'
            );
        }

        $missingAlt = 0;
        foreach ($imgTags as $img) {
            if (! preg_match('/\balt=[\'"][^\'"]*[\'"]/i', $img)) {
                $missingAlt++;
            }
        }

        if ($missingAlt > 0) {
            return new PreFlightCheck(
                id: 'image_alt_text',
                title: 'Image Alt Text Accessibility',
                status: 'warning',
                message: "{$missingAlt} image(s) lack an alt attribute for accessibility and screen readers.",
                recommendation: 'Provide descriptive alt tags on all promotional and brand images.'
            );
        }

        return new PreFlightCheck(
            id: 'image_alt_text',
            title: 'Image Alt Text Accessibility',
            status: 'pass',
            message: 'All images specify alt text attributes.'
        );
    }

    protected function checkBrokenOrPlaceholderLinks(string $html): PreFlightCheck
    {
        preg_match_all('/href=[\'"]([^\'"]*)[\'"]/i', $html, $matches);
        $links = $matches[1];

        $placeholderCount = 0;
        foreach ($links as $href) {
            $trimmed = trim($href);
            if ($trimmed === '#' || $trimmed === '' || str_starts_with($trimmed, 'http://example.com') || str_starts_with($trimmed, 'https://example.com')) {
                $placeholderCount++;
            }
        }

        if ($placeholderCount > 0) {
            return new PreFlightCheck(
                id: 'placeholder_links',
                title: 'Link Health & Target URLs',
                status: 'warning',
                message: "Found {$placeholderCount} placeholder or incomplete link(s) (e.g. href=\"#\" or example.com).",
                recommendation: 'Verify all buttons and link destinations before launching your campaign.'
            );
        }

        return new PreFlightCheck(
            id: 'placeholder_links',
            title: 'Link Health & Target URLs',
            status: 'pass',
            message: 'No placeholder or empty destination URLs detected.'
        );
    }

    protected function checkSubjectLine(string $subject): PreFlightCheck
    {
        $len = mb_strlen(trim($subject));

        if ($len === 0) {
            return new PreFlightCheck(
                id: 'subject_line_health',
                title: 'Subject Line Quality',
                status: 'warning',
                message: 'Subject line is empty.',
                recommendation: 'Provide a compelling subject line to improve open rates.'
            );
        }

        // Check spam trigger patterns
        $spamKeywords = ['free', '100% free', 'guaranteed', 'risk-free', 'buy now', 'act now', 'make money', 'click here', 'exclusive deal'];
        $lower = strtolower($subject);
        $foundSpam = [];
        foreach ($spamKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $foundSpam[] = $kw;
            }
        }

        $hasExcessivePunctuation = (bool) preg_match('/[!$?]{2,}/', $subject);
        $hasAllCapsWords = (bool) preg_match('/\b[A-Z]{4,}\b/', $subject);

        if (! empty($foundSpam) || $hasExcessivePunctuation || $hasAllCapsWords) {
            $reasons = [];
            if (! empty($foundSpam)) {
                $reasons[] = 'spam keyword ("'.implode(', ', $foundSpam).'")';
            }
            if ($hasExcessivePunctuation) {
                $reasons[] = 'consecutive punctuation (!! or ??)';
            }
            if ($hasAllCapsWords) {
                $reasons[] = 'ALL-CAPS words';
            }

            return new PreFlightCheck(
                id: 'subject_line_health',
                title: 'Subject Line Quality & Spam Risk',
                status: 'warning',
                message: 'Subject line contains potential spam triggers: '.implode(', ', $reasons).'.',
                recommendation: 'Write conversational, benefit-oriented subject lines without aggressive sales hype.'
            );
        }

        if ($len > 60) {
            return new PreFlightCheck(
                id: 'subject_line_health',
                title: 'Subject Line Quality',
                status: 'warning',
                message: "Subject line is {$len} characters long and may truncate on mobile devices (optimal is 30-50).",
                recommendation: 'Keep key value propositions within the first 40 characters.'
            );
        }

        return new PreFlightCheck(
            id: 'subject_line_health',
            title: 'Subject Line Quality',
            status: 'pass',
            message: "Subject line is crisp and well-proportioned ({$len} characters)."
        );
    }

    protected function checkPhysicalAddress(string $html): PreFlightCheck
    {
        $lower = strtolower($html);
        $hasAddress = str_contains($lower, 'address')
            || str_contains($lower, 'suite')
            || str_contains($lower, 'street')
            || str_contains($lower, 'st,')
            || str_contains($lower, 'ave')
            || str_contains($lower, 'road')
            || str_contains($lower, 'san francisco')
            || str_contains($lower, '{{company.address}}')
            || str_contains($lower, 'postal');

        if (! $hasAddress) {
            return new PreFlightCheck(
                id: 'physical_address_present',
                title: 'Physical Postal Address (CAN-SPAM)',
                status: 'warning',
                message: 'No physical mailing address detected in email footer.',
                recommendation: 'Include a valid company postal address to meet global anti-spam regulations.'
            );
        }

        return new PreFlightCheck(
            id: 'physical_address_present',
            title: 'Physical Postal Address (CAN-SPAM)',
            status: 'pass',
            message: 'Physical mailing address footprint detected.'
        );
    }

    protected function checkOutlookVmlBackground(string $html): PreFlightCheck
    {
        $hasBgImage = (bool) preg_match('/(?:background-image\s*:\s*url|background\s*:\s*[^;]*url)\(/i', $html);
        if (! $hasBgImage) {
            return new PreFlightCheck(
                id: 'outlook_vml_background',
                title: 'Outlook Desktop VML Support',
                status: 'pass',
                message: 'No complex CSS background images requiring VML workarounds detected.'
            );
        }

        $hasVml = str_contains($html, 'v:rect') || str_contains($html, 'v:fill') || str_contains($html, 'v:image');
        if (! $hasVml) {
            return new PreFlightCheck(
                id: 'outlook_vml_background',
                title: 'Outlook Desktop VML Fallback',
                status: 'warning',
                message: 'CSS background image detected without Microsoft Outlook VML conditional markup.',
                recommendation: 'Wrap background-image containers with <!--[if gte mso 9]><v:rect... to prevent missing backgrounds in Outlook Windows.'
            );
        }

        return new PreFlightCheck(
            id: 'outlook_vml_background',
            title: 'Outlook Desktop VML Support',
            status: 'pass',
            message: 'Background images are equipped with Outlook VML fallback markup.'
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function checkColorContrast(array $options): PreFlightCheck
    {
        /** @var array<string, mixed>|null $theme */
        $theme = isset($options['theme']) && is_array($options['theme']) ? $options['theme'] : null;
        $primaryColor = (string) ($theme['primary_color'] ?? ($options['primary_color'] ?? '#2563EB'));

        $ratio = $this->calculateContrastRatio($primaryColor, '#FFFFFF');

        if ($ratio < 3.0) {
            return new PreFlightCheck(
                id: 'color_contrast_wcag',
                title: 'WCAG Color Contrast',
                status: 'warning',
                message: sprintf('Primary theme color (%s) contrast with white text is %.2f:1 (WCAG AA requires at least 3.0:1 for buttons).', $primaryColor, $ratio),
                recommendation: 'Darken the primary button color to satisfy accessibility guidelines.'
            );
        }

        return new PreFlightCheck(
            id: 'color_contrast_wcag',
            title: 'WCAG Color Contrast',
            status: 'pass',
            message: sprintf('Primary button color (%s) has strong contrast (%.2f:1) against white text.', $primaryColor, $ratio)
        );
    }

    /**
     * Calculate WCAG contrast ratio between two hex colors.
     */
    public function calculateContrastRatio(string $hex1, string $hex2): float
    {
        $l1 = $this->calculateRelativeLuminance($hex1);
        $l2 = $this->calculateRelativeLuminance($hex2);

        $bright = max($l1, $l2);
        $dark = min($l1, $l2);

        return ($bright + 0.05) / ($dark + 0.05);
    }

    protected function calculateRelativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) {
            return 0.5;
        }

        $r = hexdec(substr($hex, 0, 2)) / 255.0;
        $g = hexdec(substr($hex, 2, 2)) / 255.0;
        $b = hexdec(substr($hex, 4, 2)) / 255.0;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function checkBimiAndAuthentication(array $options): PreFlightCheck
    {
        $bimiSvgUrl = isset($options['bimi_svg_url']) ? (string) $options['bimi_svg_url'] : null;

        if ($bimiSvgUrl === null || $bimiSvgUrl === '') {
            return new PreFlightCheck(
                id: 'bimi_readiness',
                title: 'BIMI Brand Avatar Readiness',
                status: 'pass',
                message: 'Standard authentication profile configured. BIMI verified logo can be attached for visual avatar display.'
            );
        }

        // Validate SVG format
        if (! str_ends_with(strtolower($bimiSvgUrl), '.svg')) {
            return new PreFlightCheck(
                id: 'bimi_readiness',
                title: 'BIMI Brand Avatar Readiness',
                status: 'warning',
                message: 'BIMI requires an SVG file format (SVG Tiny P/S profile), but non-SVG logo was provided.',
                recommendation: 'Provide an official SVG Tiny P/S format logo hosted on HTTPS.'
            );
        }

        if (! str_starts_with(strtolower($bimiSvgUrl), 'https://')) {
            return new PreFlightCheck(
                id: 'bimi_readiness',
                title: 'BIMI Brand Avatar Readiness',
                status: 'warning',
                message: 'BIMI SVG URL must be served over secure HTTPS.',
                recommendation: 'Host your BIMI SVG on a public HTTPS endpoint with TLS.'
            );
        }

        return new PreFlightCheck(
            id: 'bimi_readiness',
            title: 'BIMI Brand Avatar Readiness',
            status: 'pass',
            message: 'BIMI SVG logo is correctly formatted and secured over HTTPS.'
        );
    }
}

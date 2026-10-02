<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Audit\EmailPreFlightAuditor;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Tests\TestCase;

class EmailPreFlightAuditorTest extends TestCase
{
    public function test_audit_flags_missing_unsubscribe_link(): void
    {
        $auditor = app(EmailPreFlightAuditor::class);

        $html = '<html><body><h1>Welcome to Focal</h1><p>Enjoy your trial!</p></body></html>';
        $result = $auditor->audit($html, ['subject' => 'Welcome']);

        $this->assertFalse($result->checks[0]->isPassing());
        $this->assertTrue($result->checks[0]->isFailing());
        $this->assertTrue($result->hasBlockingIssues());
        $this->assertSame('unsubscribe_present', $result->checks[0]->id);
    }

    public function test_audit_detects_spam_trigger_words_in_subject(): void
    {
        $auditor = app(EmailPreFlightAuditor::class);

        $html = '<html><body><a href="{{unsubscribe_url}}">Unsubscribe</a><p>Address: 123 Main St</p></body></html>';
        $result = $auditor->audit($html, ['subject' => 'ACT NOW FOR 100% FREE MONEY!!!']);

        $subjectCheck = null;
        foreach ($result->checks as $check) {
            if ($check->id === 'subject_line_health') {
                $subjectCheck = $check;
            }
        }

        $this->assertNotNull($subjectCheck);
        $this->assertTrue($subjectCheck->isWarning());
        $this->assertStringContainsString('spam keyword', $subjectCheck->message);
    }

    public function test_audit_passes_clean_compliant_template(): void
    {
        $slots = [
            ['type' => 'header', 'data' => ['brand_name' => 'Focal']],
            ['type' => 'hero', 'data' => ['title' => 'Product Update 2.0', 'button_text' => 'Read Release Notes', 'button_url' => 'https://focal.test/notes']],
            ['type' => 'footer', 'data' => ['company_name' => 'Focal Inc.', 'address' => '548 Market St, San Francisco, CA']],
        ];

        $result = MailBuilder::audit($slots, [
            'subject' => 'Announcing Focal RevOps 2.0',
        ]);

        $this->assertSame('healthy', $result->status);
        $this->assertGreaterThanOrEqual(85, $result->score);
        $this->assertFalse($result->hasBlockingIssues());
    }

    public function test_audit_flags_background_image_without_outlook_vml(): void
    {
        $auditor = app(EmailPreFlightAuditor::class);
        $html = '<div style="background-image: url(\'https://example.com/bg.jpg\')"><a href="{{unsubscribe_url}}">Unsubscribe</a><p>Address: 123 Main St</p></div>';

        $result = $auditor->audit($html, ['subject' => 'Monthly Report']);

        $vmlCheck = null;
        foreach ($result->checks as $check) {
            if ($check->id === 'outlook_vml_background') {
                $vmlCheck = $check;
            }
        }

        $this->assertNotNull($vmlCheck);
        $this->assertTrue($vmlCheck->isWarning());
        $this->assertStringContainsString('Outlook VML conditional markup', $vmlCheck->message);
    }

    public function test_audit_flags_low_contrast_theme_colors(): void
    {
        $auditor = app(EmailPreFlightAuditor::class);
        $html = '<div><a href="{{unsubscribe_url}}">Unsubscribe</a><p>Address: 123 Main St</p></div>';

        // Very light yellow button on white background has very low contrast (< 2:1)
        $result = $auditor->audit($html, [
            'subject' => 'Welcome',
            'theme' => ['primary_color' => '#FFF3C4'],
        ]);

        $contrastCheck = null;
        foreach ($result->checks as $check) {
            if ($check->id === 'color_contrast_wcag') {
                $contrastCheck = $check;
            }
        }

        $this->assertNotNull($contrastCheck);
        $this->assertTrue($contrastCheck->isWarning());
        $this->assertStringContainsString('WCAG AA requires at least 3.0:1', $contrastCheck->message);
    }

    public function test_audit_evaluates_bimi_svg_url(): void
    {
        $auditor = app(EmailPreFlightAuditor::class);
        $html = '<div><a href="{{unsubscribe_url}}">Unsubscribe</a><p>Address: 123 Main St</p></div>';

        // Warning on non-SVG image
        $result = $auditor->audit($html, [
            'subject' => 'Welcome',
            'bimi_svg_url' => 'https://example.com/logo.png',
        ]);

        $bimiCheck = null;
        foreach ($result->checks as $check) {
            if ($check->id === 'bimi_readiness') {
                $bimiCheck = $check;
            }
        }

        $this->assertNotNull($bimiCheck);
        $this->assertTrue($bimiCheck->isWarning());
        $this->assertStringContainsString('BIMI requires an SVG file format', $bimiCheck->message);

        // Pass on valid HTTPS SVG
        $validResult = $auditor->audit($html, [
            'subject' => 'Welcome',
            'bimi_svg_url' => 'https://example.com/brand/bimi-logo.svg',
        ]);

        $validBimiCheck = null;
        foreach ($validResult->checks as $check) {
            if ($check->id === 'bimi_readiness') {
                $validBimiCheck = $check;
            }
        }

        $this->assertNotNull($validBimiCheck);
        $this->assertTrue($validBimiCheck->isPassing());
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Tests\TestCase;

class RtlDarkModeCompileTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $theme
     */
    private function compile(array $theme = []): string
    {
        $doc = new EmailDocument(subject: 'Fixture', previewText: 'Preview', theme: $theme);

        $doc->append(SlotType::Header, ['brand_name' => 'Focal', 'tagline' => 'Platform', 'web_view_url' => 'https://focal.test/view']);
        $doc->append(SlotType::TwoColumn, ['left_title' => 'Left', 'right_title' => 'Right']);
        $doc->append(SlotType::TwoColumn, ['left_title' => 'Left', 'right_title' => 'Right', 'reverse_stack_on_mobile' => true]);
        $doc->append(SlotType::ThreeColumn, []);
        $doc->append(SlotType::AsymmetricColumns, []);
        $doc->append(SlotType::Gallery, ['images' => [['image_url' => 'https://focal.test/a.png'], ['image_url' => 'https://focal.test/b.png']]]);

        return app(EmailSlotCompiler::class)->compileDocument($doc);
    }

    public function test_ltr_output_is_unchanged(): void
    {
        $this->assertSame(file_get_contents(__DIR__.'/../fixtures/ltr-document.html'), $this->compile());
    }

    public function test_rtl_header_aligns_right_to_left_and_keeps_the_default_label(): void
    {
        $html = $this->compile(['direction' => 'rtl']);

        $this->assertMatchesRegularExpression('/<td align="right" valign="middle"[^>]*>.*?Focal/s', $html);
        $this->assertMatchesRegularExpression('/<td align="left" valign="middle" style[^>]*>.*?View in browser/s', $html);
    }

    public function test_rtl_columns_float_from_the_right_and_follow_the_document_direction(): void
    {
        $html = $this->compile(['direction' => 'rtl']);

        $this->assertStringNotContainsString('dir="ltr"', $html);
        $this->assertStringNotContainsString('direction: ltr', $html);
        // Two-column: the first column is on the right, the second on the left.
        $this->assertMatchesRegularExpression('/align="right" class="stack-column" dir="rtl"[^>]*>.*?Left.*?align="left" class="stack-column" dir="rtl"[^>]*>.*?Right/s', $html);
        // Three-column, asymmetric and gallery columns float from the start edge.
        $this->assertMatchesRegularExpression('/align="right" class="stack-column" style="[^"]*max-width: 190px; margin-bottom: 16px; margin-left: 2%;"/', $html);
        $this->assertStringContainsString('text-align: right;', $html);
        $this->assertStringContainsString('margin-left: 2%;', $html);
        $this->assertStringContainsString('width="32%" align="right"', $html);
        $this->assertStringContainsString('width="64%" align="left"', $html);
    }

    public function test_reverse_on_mobile_flips_the_columns_back_in_rtl(): void
    {
        $html = $this->compile(['direction' => 'rtl']);

        $this->assertStringContainsString('width="48%" align="left" class="stack-column" dir="rtl"', $html);
    }

    public function test_the_view_in_browser_label_is_a_theme_key(): void
    {
        $html = $this->compile(['direction' => 'rtl', 'view_in_browser_label' => 'عرض في المتصفح']);

        $this->assertStringContainsString('>عرض في المتصفح</a>', $html);
        $this->assertStringNotContainsString('View in browser', $html);
    }

    public function test_the_label_is_escaped(): void
    {
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $this->compile(['view_in_browser_label' => '<b>x</b>']));
    }

    public function test_dark_mode_css_is_driven_by_optional_theme_keys(): void
    {
        $html = $this->compile([
            'dark_background_color' => '#0f172a',
            'dark_text_color' => '#e2e8f0',
            'dark_content_background_color' => '#1e293b',
        ]);

        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $html);
        $this->assertStringContainsString('body, center { background-color: #0f172a !important; }', $html);
        $this->assertStringContainsString('.email-container { background-color: #1e293b !important; }', $html);
        $this->assertStringContainsString('body, p { color: #e2e8f0 !important; }', $html);
    }

    public function test_a_single_dark_key_only_emits_its_own_rule_and_a_bad_colour_is_dropped(): void
    {
        $html = $this->compile(['dark_text_color' => '#e2e8f0']);

        $this->assertStringContainsString('prefers-color-scheme: dark', $html);
        $this->assertStringNotContainsString('.email-container { background-color', $html);

        $this->assertStringNotContainsString('prefers-color-scheme: dark', $this->compile(['dark_text_color' => 'red; } body { display:none']));
    }

    public function test_without_dark_keys_only_the_color_scheme_metas_are_present(): void
    {
        $html = $this->compile();

        $this->assertStringContainsString('<meta name="color-scheme" content="light dark">', $html);
        $this->assertStringContainsString('<meta name="supported-color-schemes" content="light dark">', $html);
        $this->assertStringNotContainsString('prefers-color-scheme', $html);
    }
}

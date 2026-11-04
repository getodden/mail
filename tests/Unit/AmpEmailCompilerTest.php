<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Tests\TestCase;

class AmpEmailCompilerTest extends TestCase
{
    public function test_can_compile_slots_to_amp_for_email(): void
    {
        $doc = new EmailDocument;
        $doc->append(SlotType::Hero, [
            'title' => 'Interactive Briefing',
            'subtitle' => 'AMP enabled interactive email.',
            'button_text' => 'Explore',
            'button_url' => 'https://focal.test/explore',
        ]);
        $doc->append(SlotType::Accordion, [
            'heading' => 'Interactive FAQ',
            'items' => [
                ['question' => 'How does AMP work?', 'answer' => 'It renders dynamic components securely.'],
            ],
        ]);

        $ampHtml = MailBuilder::amp($doc);

        $this->assertStringContainsString('⚡4email', $ampHtml);
        $this->assertStringContainsString('amp-accordion', $ampHtml);
        $this->assertStringContainsString('Interactive Briefing', $ampHtml);
        $this->assertStringContainsString('How does AMP work?', $ampHtml);
        $this->assertStringContainsString('amp-custom', $ampHtml);
    }

    public function test_raw_content_and_urls_are_sanitized_in_amp(): void
    {
        $doc = new EmailDocument;
        $doc->append(SlotType::BodyText, ['content' => "<p x'> y='<a href=javascript:alert(1) '>click</a><script>alert(1)</script><img src=x onerror=alert(1)>"]);
        $doc->append(SlotType::Hero, ['title' => 'T', 'button_text' => 'Go', 'button_url' => 'javascript:alert(1)']);

        $ampHtml = MailBuilder::amp($doc);

        $this->assertStringNotContainsString('javascript:', $ampHtml);
        $this->assertStringNotContainsString('<script>alert', $ampHtml);
        $this->assertStringNotContainsString('onerror', $ampHtml);
        $this->assertStringContainsString('click</a>', $ampHtml);
    }
}

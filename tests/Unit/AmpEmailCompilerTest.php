<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Unit;

use DoPHP\MailBuilder\Data\EmailDocument;
use DoPHP\MailBuilder\Enums\SlotType;
use DoPHP\MailBuilder\MailBuilder;
use DoPHP\MailBuilder\Tests\TestCase;

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
}

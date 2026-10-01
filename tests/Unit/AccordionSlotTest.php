<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Unit;

use DoPHP\MailBuilder\Compilers\PlainTextExtractor;
use DoPHP\MailBuilder\Data\EmailSlot;
use DoPHP\MailBuilder\Enums\SlotType;
use DoPHP\MailBuilder\MailBuilder;
use DoPHP\MailBuilder\Tests\TestCase;

class AccordionSlotTest extends TestCase
{
    public function test_accordion_slot_compiles_cleanly_into_html(): void
    {
        $slotData = [
            'type' => SlotType::Accordion->value,
            'data' => [
                'heading' => 'Got Questions?',
                'subtitle' => 'Everything you need to know about our billing.',
                'items' => [
                    ['question' => 'Can I cancel anytime?', 'answer' => 'Yes, without any cancellation fees.'],
                    ['question' => 'Do you offer team discounts?', 'answer' => 'Yes, for teams with over 10 seats.'],
                ],
            ],
        ];

        $html = MailBuilder::compile([$slotData]);

        $this->assertStringContainsString('Got Questions?', $html);
        $this->assertStringContainsString('Everything you need to know', $html);
        $this->assertStringContainsString('Can I cancel anytime?', $html);
        $this->assertStringContainsString('Yes, without any cancellation fees.', $html);
        $this->assertStringContainsString('Do you offer team discounts?', $html);
    }

    public function test_accordion_plain_text_extractor(): void
    {
        $slot = new EmailSlot(SlotType::Accordion, [
            'heading' => 'FAQ Highlights',
            'items' => [
                ['question' => 'Is my data secure?', 'answer' => 'We are SOC2 Type II and ISO 27001 certified.'],
            ],
        ]);

        $extractor = new PlainTextExtractor;
        $plain = $extractor->extractFromSlot($slot);

        $this->assertStringContainsString('=== FAQ Highlights ===', $plain);
        $this->assertStringContainsString('Q: Is my data secure?', $plain);
        $this->assertStringContainsString('A: We are SOC2 Type II and ISO 27001 certified.', $plain);
    }
}

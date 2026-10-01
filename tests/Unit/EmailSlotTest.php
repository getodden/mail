<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Unit;

use DoPHP\MailBuilder\Data\EmailSlot;
use DoPHP\MailBuilder\Enums\ButtonStyle;
use DoPHP\MailBuilder\Enums\SlotType;
use DoPHP\MailBuilder\Tests\TestCase;
use InvalidArgumentException;

class EmailSlotTest extends TestCase
{
    public function test_can_instantiate_slot_and_access_data(): void
    {
        $slot = new EmailSlot(SlotType::Hero, [
            'title' => 'Big Announcement',
            'subtitle' => 'Our new product is live',
        ]);

        $this->assertSame(SlotType::Hero, $slot->type);
        $this->assertSame('Big Announcement', $slot->get('title'));
        $this->assertSame('Our new product is live', $slot->get('subtitle'));
        $this->assertNull($slot->get('non_existent'));
        $this->assertSame('fallback', $slot->get('non_existent', 'fallback'));
    }

    public function test_can_create_from_array_and_convert_to_array(): void
    {
        $payload = [
            'type' => 'button',
            'data' => [
                'text' => 'Click Here',
                'url' => 'https://focal.test',
                'style' => 'primary',
            ],
        ];

        $slot = EmailSlot::fromArray($payload);

        $this->assertSame(SlotType::Button, $slot->type);
        $this->assertSame('Click Here', $slot->get('text'));
        $this->assertSame($payload, $slot->toArray());
    }

    public function test_from_array_throws_exception_on_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EmailSlot::fromArray([
            'type' => 'invalid_random_type',
            'data' => [],
        ]);
    }

    public function test_button_style_returns_expected_colors(): void
    {
        $this->assertSame('#2563eb', ButtonStyle::Primary->backgroundColor());
        $this->assertSame('#ffffff', ButtonStyle::Primary->textColor());
        $this->assertSame('transparent', ButtonStyle::Outline->backgroundColor());
        $this->assertSame('#0f172a', ButtonStyle::Outline->textColor());
    }

    public function test_slot_type_enum_metadata(): void
    {
        $this->assertNotEmpty(SlotType::Hero->label());
        $this->assertNotEmpty(SlotType::Hero->description());
        $this->assertSame('mail-builder::slots.hero', SlotType::Hero->viewName());
    }
}

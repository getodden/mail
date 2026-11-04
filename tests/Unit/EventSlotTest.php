<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Conditions\SlotVisibilityEvaluator;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotGroup;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;
use Odden\MailBuilder\Tests\TestCase;

class EventSlotTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function data(): array
    {
        return [
            'title' => 'Barrel Tasting Night',
            'date' => 'Saturday, 14 June',
            'time' => '6:00 PM',
            'location' => 'The Cellar, 12 Main St',
            'description' => 'Taste three vintages straight from the barrel.',
            'button_text' => 'Reserve Your Spot',
            'button_url' => 'https://example.test/rsvp',
        ];
    }

    public function test_the_schema_exports_every_field_the_editor_reads(): void
    {
        $exported = collect(SlotSchemaRegistry::toArray()['slots'])->firstWhere('type', 'event');

        $this->assertSame('clock', $exported['icon']);
        $this->assertSame(SlotGroup::Content, SlotType::Event->group());
        $this->assertTrue($exported['supports_visibility']);

        $fields = collect($exported['fields'])->keyBy('key');
        $this->assertSame(['title', 'date', 'time', 'location', 'description', 'button_text', 'button_url', 'bg_color'], $fields->keys()->all());
        $this->assertTrue($fields['title']['required']);
        $this->assertTrue($fields['date']['required']);
        $this->assertSame('textarea', $fields['description']['type']);
        $this->assertSame('url', $fields['button_url']['type']);
        $this->assertSame('color', $fields['bg_color']['type']);
        $this->assertTrue($fields['bg_color']['advanced']);
    }

    public function test_it_compiles_its_content(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Event, $this->data()));

        foreach (['Barrel Tasting Night', 'Saturday, 14 June', '6:00 PM', 'The Cellar, 12 Main St', 'Taste three vintages', 'Reserve Your Spot', 'https://example.test/rsvp'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }

    public function test_the_button_is_left_out_without_a_label_and_url(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Event, ['title' => 'Open House', 'date' => 'Friday']));

        $this->assertStringContainsString('Open House', $html);
        $this->assertStringNotContainsString('<a href', $html);
    }

    public function test_values_are_escaped(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Event, [
            'title' => '<script>alert("x")</script> "quoted" \'single\'',
            'date' => '<b>today</b>',
            'location' => '<img src=x onerror=alert(1)>',
            'button_text' => 'Go',
            'button_url' => 'https://example.test/?a="><script>alert(2)</script>',
        ]));

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<b>today</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &quot;quoted&quot;', $html);
    }

    public function test_plain_text_contains_its_content(): void
    {
        $text = app(PlainTextExtractor::class)->extractFromSlot(new EmailSlot(SlotType::Event, $this->data()));

        $this->assertStringContainsString('Barrel Tasting Night', $text);
        $this->assertStringContainsString('Date: Saturday, 14 June', $text);
        $this->assertStringContainsString('Time: 6:00 PM', $text);
        $this->assertStringContainsString('Location: The Cellar, 12 Main St', $text);
        $this->assertStringContainsString('Reserve Your Spot: https://example.test/rsvp', $text);
    }

    public function test_it_supports_a_visibility_rule(): void
    {
        $slot = new EmailSlot(SlotType::Event, [...$this->data(), 'visibility' => [
            'field' => 'contact.region',
            'operator' => 'equals',
            'value' => 'north',
        ]]);
        $evaluator = new SlotVisibilityEvaluator;

        $this->assertTrue($evaluator->isVisible($slot, ['contact' => ['region' => 'north']]));
        $this->assertFalse($evaluator->isVisible($slot, ['contact' => ['region' => 'south']]));
    }
}

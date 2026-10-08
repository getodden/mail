<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Tests\TestCase;

class EditorMarkersTest extends TestCase
{
    private function document(): EmailDocument
    {
        $document = new EmailDocument;
        $document->append(SlotType::Hero, ['title' => 'First']);
        $document->append(SlotType::Divider, []);
        $document->append(SlotType::BodyText, ['content' => '<p>Third</p>']);

        return $document;
    }

    public function test_no_markers_are_added_unless_asked_for(): void
    {
        $html = app(EmailSlotCompiler::class)->compileDocument($this->document());

        $this->assertStringNotContainsString('data-odden-slot', $html);
    }

    public function test_each_slot_gets_a_marker_with_its_index(): void
    {
        $html = app(EmailSlotCompiler::class)->compileDocument($this->document(), ['editor_markers' => true]);

        $this->assertSame(3, substr_count($html, 'data-odden-slot='));
        $this->assertStringContainsString('data-odden-slot="0"', $html);
        $this->assertStringContainsString('data-odden-slot="2"', $html);
    }

    public function test_the_index_is_the_slots_position_in_the_document_even_when_one_is_hidden(): void
    {
        $document = EmailDocument::fromArray(['slots' => [
            ['type' => 'hero', 'data' => ['title' => 'Always']],
            ['type' => 'divider', 'data' => [], 'visibility' => ['field' => 'contact.lifecycle_stage', 'operator' => 'equals', 'value' => 'customer']],
            ['type' => 'body_text', 'data' => ['content' => '<p>Last</p>']],
        ]]);

        $html = app(EmailSlotCompiler::class)->compileDocument($document, [
            'editor_markers' => true,
            'context' => ['contact' => ['lifecycle_stage' => 'lead']],
        ]);

        $this->assertStringContainsString('data-odden-slot="0"', $html);
        $this->assertStringNotContainsString('data-odden-slot="1"', $html);
        $this->assertStringContainsString('data-odden-slot="2"', $html);
    }
}

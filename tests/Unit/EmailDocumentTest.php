<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Unit;

use DoPHP\MailBuilder\Data\EmailDocument;
use DoPHP\MailBuilder\Enums\SlotType;
use DoPHP\MailBuilder\Tests\TestCase;

class EmailDocumentTest extends TestCase
{
    public function test_can_build_document_programmatically(): void
    {
        $doc = new EmailDocument(
            subject: 'Hello World',
            previewText: 'Snippet text',
            theme: ['primary_color' => '#10b981'],
        );

        $doc->append(SlotType::Header, ['brand_name' => 'Acme']);
        $doc->append(SlotType::BodyText, ['content' => '<p>Welcome!</p>']);

        $this->assertCount(2, $doc->slots);
        $this->assertSame('Hello World', $doc->subject);
        $this->assertSame('Snippet text', $doc->previewText);
        $this->assertSame('#10b981', $doc->theme['primary_color']);
    }

    public function test_can_serialize_to_and_from_json(): void
    {
        $doc = new EmailDocument(
            subject: 'Test Subject',
            previewText: 'Preheader',
        );
        $doc->append(SlotType::Button, ['text' => 'Get Started', 'url' => 'https://focal.test']);

        $json = $doc->toJson();
        $this->assertJson($json);

        $restored = EmailDocument::fromJson($json);

        $this->assertSame('Test Subject', $restored->subject);
        $this->assertSame('Preheader', $restored->previewText);
        $this->assertCount(1, $restored->slots);
        $this->assertSame(SlotType::Button, $restored->slots[0]->type);
        $this->assertSame('Get Started', $restored->slots[0]->get('text'));
    }

    public function test_document_theme_override(): void
    {
        $doc = new EmailDocument;
        $doc->setTheme('container_width', 640);

        $this->assertSame(640, $doc->theme['container_width']);
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Tests\TestCase;

class MergeTagInlineProtectionTest extends TestCase
{
    public function test_button_href_keeps_merge_tag_whitespace_with_default_options(): void
    {
        $html = MailBuilder::compile([
            ['type' => 'button', 'data' => ['text' => 'Pick', 'url' => '{{ preference_url option="Red Wine, dry" }}']],
        ]);

        $this->assertStringContainsString('{{ preference_url option=&quot;Red Wine, dry&quot; }}', $html);
        $this->assertStringNotContainsString('%20', $html);
    }

    public function test_footer_unsubscribe_href_keeps_merge_tag_whitespace(): void
    {
        $html = MailBuilder::compile([
            ['type' => 'footer', 'data' => ['unsubscribe_url' => '{{ unsubscribe_url }}']],
        ]);

        $this->assertStringContainsString('href="{{ unsubscribe_url }}"', $html);
        $this->assertStringNotContainsString('%20', $html);
    }

    public function test_text_merge_tags_are_unchanged(): void
    {
        $html = MailBuilder::compile([
            ['type' => 'body_text', 'data' => ['content' => '<p>Hi {{ contact.first_name }} {% if x %}there{% endif %}</p>']],
        ]);

        $this->assertStringContainsString('Hi {{ contact.first_name }} {% if x %}there{% endif %}', $html);
    }

    public function test_unmatched_opening_braces_do_not_break_compilation(): void
    {
        $html = MailBuilder::compile([
            ['type' => 'body_text', 'data' => ['content' => '<p>Open {{ never closed</p>']],
            ['type' => 'body_text', 'data' => ['content' => '<p>After block</p>']],
        ]);

        $this->assertStringContainsString('Open {{ never closed', $html);
        $this->assertStringContainsString('After block', $html);
    }

    public function test_documents_without_merge_tags_are_unaffected(): void
    {
        $html = MailBuilder::compile([
            ['type' => 'body_text', 'data' => ['content' => '<p>Plain</p>']],
        ]);

        $this->assertStringContainsString('margin-bottom: 16px', $html);
        $this->assertStringNotContainsString('mbtag', $html);
    }

    public function test_interpolation_still_replaces_tags_and_scrubber_neutralizes_javascript(): void
    {
        $slots = [
            ['type' => 'body_text', 'data' => ['content' => '<p>Hi {{ contact.first_name }}</p>']],
            ['type' => 'button', 'data' => ['text' => 'Go', 'url' => '{{ contact.link }}']],
        ];

        $html = MailBuilder::compile($slots, [
            'interpolate' => true,
            'context' => ['contact' => ['first_name' => 'Sarah', 'link' => 'https://example.com/a b']],
        ]);

        $this->assertStringContainsString('Hi Sarah', $html);
        $this->assertStringContainsString('https://example.com/a', $html);

        $evil = MailBuilder::compile($slots, [
            'interpolate' => true,
            'context' => ['contact' => ['first_name' => 'Sarah', 'link' => 'javascript:alert(1)']],
        ]);

        $this->assertStringNotContainsString('javascript:', $evil);
    }
}

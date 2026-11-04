<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Editor\PreviewRenderer;
use Odden\MailBuilder\Exceptions\InvalidMergeTagStructureException;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class BlockMergeTagAuthoringTest extends TestCase
{
    private const LEAK = '<p>Hi {% if subscribed %}welcome {% if is_vip %}VIPSECRET{% endif %}</p>';

    /**
     * @param  list<array<string, mixed>>  $slots
     * @param  array<string, mixed>  $options
     */
    private function compile(array $slots, array $options = []): string
    {
        return app(EmailSlotCompiler::class)->compileSlots($slots, $options);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badRichTexts(): array
    {
        return [
            'the leak' => [self::LEAK],
            'balanced nesting' => ['<p>{% if a %}1{% if b %}2{% endif %}3{% endif %}</p>'],
            'unclosed' => ['<p>{% if a %}secret</p>'],
            'stray closer' => ['<p>x {% endif %}</p>'],
            'handlebars each in if' => ['<p>{{#if a}}{{#each xs}}x{{/each}}{{/if}}</p>'],
        ];
    }

    #[DataProvider('badRichTexts')]
    public function test_a_rich_field_with_bad_block_tags_is_never_compiled(string $content): void
    {
        try {
            $this->compile([['type' => 'body_text', 'data' => ['content' => $content]]]);
            $this->fail('The rich text was compiled.');
        } catch (InvalidMergeTagStructureException $exception) {
            $this->assertSame('content', $exception->field);
            $this->assertNotSame([], $exception->problems);
        }
    }

    public function test_a_rich_field_whose_sanitized_text_is_unbalanced_is_rejected(): void
    {
        $this->expectException(InvalidMergeTagStructureException::class);

        $this->compile([['type' => 'body_text', 'data' => ['content' => '<p title="{% if nope %}">x</p>{% endif %}']]]);
    }

    public function test_a_balanced_single_level_block_still_compiles(): void
    {
        $html = $this->compile(
            [['type' => 'body_text', 'data' => ['content' => '<p>{% if vip %}VIP{% else %}Regular{% endif %}</p>']]],
            ['interpolate' => true, 'context' => ['vip' => false]],
        );

        $this->assertStringContainsString('Regular', $html);
        $this->assertStringNotContainsString('VIP<', $html);
    }

    public function test_the_raw_html_slot_is_not_validated_and_its_blocks_are_stripped(): void
    {
        $html = $this->compile([['type' => 'html', 'data' => ['html' => '<p>a {% if x %}b {% if y %}SECRET{% endif %} c</p>']]]);

        $this->assertStringNotContainsString('SECRET', $html);
        $this->assertStringNotContainsString('{% if', $html);
    }

    public function test_a_text_field_is_validated(): void
    {
        try {
            $this->compile([['type' => 'hero', 'data' => ['title' => '{% if a %}x{% if b %}y{% endif %}{% endif %}']]]);
            $this->fail('The title was compiled.');
        } catch (InvalidMergeTagStructureException $exception) {
            $this->assertSame('title', $exception->field);
            $this->assertSame(['nested_block'], $exception->problems);
        }
    }

    public function test_a_textarea_field_inside_an_items_field_is_validated_with_its_path(): void
    {
        try {
            $this->compile([['type' => 'features', 'data' => ['items' => [
                ['title' => 'fine', 'text' => 'fine'],
                ['title' => 'fine', 'text' => 'oops {% if a %}x'],
            ]]]]);
            $this->fail('The feature was compiled.');
        } catch (InvalidMergeTagStructureException $exception) {
            $this->assertSame('items.1.text', $exception->field);
            $this->assertSame(['unbalanced_opener'], $exception->problems);
        }
    }

    public function test_a_tags_field_value_is_validated(): void
    {
        $this->expectException(InvalidMergeTagStructureException::class);

        $this->compile([['type' => 'pricing_grid', 'data' => ['tiers' => [['name' => 'a', 'features' => ['ok', '{% endif %}']]]]]]);
    }

    public function test_the_subject_and_the_preview_text_are_validated(): void
    {
        foreach (['subject', 'preview_text'] as $field) {
            try {
                $this->compile([], [$field => 'Hello {% if a %}x']);
                $this->fail("The {$field} was compiled.");
            } catch (InvalidMergeTagStructureException $exception) {
                $this->assertSame($field, $exception->field);
            }
        }
    }

    public function test_the_preview_reports_the_field_with_a_plain_english_message(): void
    {
        $payload = ['slots' => [
            ['type' => 'body_text', 'data' => ['content' => '<p>fine</p>']],
            ['type' => 'body_text', 'data' => ['content' => self::LEAK]],
        ]];

        try {
            app(PreviewRenderer::class)->render($payload);
            $this->fail('The preview was rendered.');
        } catch (ValidationException $exception) {
            $this->assertSame(['slots.1.data.content'], array_keys($exception->errors()));
            $messages = implode(' ', $exception->errors()['slots.1.data.content']);
            $this->assertStringContainsString('never closed', $messages);
            $this->assertStringContainsString('cannot be nested', $messages);
        }
    }

    public function test_the_preview_reports_a_nested_items_field(): void
    {
        $payload = ['slots' => [['type' => 'features', 'data' => ['items' => [['title' => '{% endif %}']]]]]];

        try {
            app(PreviewRenderer::class)->render($payload);
            $this->fail('The preview was rendered.');
        } catch (ValidationException $exception) {
            $this->assertSame(['slots.0.data.items.0.title'], array_keys($exception->errors()));
        }
    }

    public function test_the_preview_reports_the_subject(): void
    {
        try {
            app(PreviewRenderer::class)->render(['slots' => [], 'subject' => '{% if a %}x']);
            $this->fail('The preview was rendered.');
        } catch (ValidationException $exception) {
            $this->assertSame(['subject'], array_keys($exception->errors()));
        }
    }

    public function test_the_preview_reports_the_preview_text(): void
    {
        try {
            app(PreviewRenderer::class)->render(['slots' => [], 'preview_text' => '{{/if}}']);
            $this->fail('The preview was rendered.');
        } catch (ValidationException $exception) {
            $this->assertSame(['preview_text'], array_keys($exception->errors()));
        }
    }

    public function test_a_slot_field_named_subject_is_not_mistaken_for_the_document_subject(): void
    {
        $payload = ['slots' => [['type' => 'body_text', 'data' => ['content' => self::LEAK]]], 'subject' => 'Hello'];

        try {
            app(PreviewRenderer::class)->render($payload);
            $this->fail('The preview was rendered.');
        } catch (ValidationException $exception) {
            $this->assertSame(['slots.0.data.content'], array_keys($exception->errors()));
        }
    }
}

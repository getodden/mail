<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PlainTextSafetyTest extends TestCase
{
    /**
     * @return array<string, array{SlotType}>
     */
    public static function slotTypes(): array
    {
        $types = [];

        foreach (SlotSchemaRegistry::types() as $type) {
            $types[$type->value] = [$type];
        }

        return $types;
    }

    /**
     * @param  array<int, SlotField>  $fields
     * @return array<string, mixed>
     */
    private function hostileData(array $fields): array
    {
        $data = [];

        foreach ($fields as $field) {
            $data[$field->key] = match ($field->type) {
                FieldType::Url, FieldType::Image => 'javascript:alert(1)',
                FieldType::Items => array_fill(0, 2, $this->hostileData($field->items)),
                FieldType::RichText => '<a href="javascript:alert(1)">rich</a>',
                FieldType::Text, FieldType::Textarea => $field->rawHtml ? '<a href="javascript:alert(1)">raw</a>' : 'text',
                default => $field->resolveDefault(),
            };
        }

        return $data;
    }

    #[DataProvider('slotTypes')]
    public function test_no_slot_prints_an_unsafe_url_in_plain_text(SlotType $type): void
    {
        $fields = array_values(SlotSchemaRegistry::for($type)->fields);
        $slot = new EmailSlot($type, $this->hostileData($fields));

        $text = app(PlainTextExtractor::class)->extractFromSlot($slot);

        $this->assertDoesNotMatchRegularExpression('/javascript:/i', $text);
    }

    public function test_every_url_the_extractor_reads_is_a_url_field_in_the_schema(): void
    {
        $urlKeys = [];

        foreach (SlotSchemaRegistry::types() as $type) {
            $urlKeys = [...$urlKeys, ...$this->urlFieldKeys(SlotSchemaRegistry::for($type)->fields)];
        }

        preg_match_all("/'([a-z_]*(?:url|link|src|thumbnail)[a-z_]*)'/", (string) file_get_contents(__DIR__.'/../../src/Compilers/PlainTextExtractor.php'), $matches);

        $this->assertSame([], array_values(array_diff(array_unique($matches[1]), $urlKeys, ['links'])));
    }

    /**
     * @param  array<int, SlotField>  $fields
     * @return list<string>
     */
    private function urlFieldKeys(array $fields): array
    {
        $keys = [];

        foreach ($fields as $field) {
            if (in_array($field->type, [FieldType::Url, FieldType::Image], true)) {
                $keys[] = $field->key;
            }

            $keys = [...$keys, ...$this->urlFieldKeys($field->items)];
        }

        return $keys;
    }

    public function test_a_gallery_image_the_html_drops_is_not_listed(): void
    {
        $slot = new EmailSlot(SlotType::Gallery, ['images' => [
            ['image_url' => 'javascript:x', 'caption' => 'Hidden'],
            ['image_url' => 'https://example.test/a.png', 'caption' => 'Shown'],
        ]]);

        $text = app(PlainTextExtractor::class)->extractFromSlot($slot);

        $this->assertStringNotContainsString('Hidden', $text);
        $this->assertStringContainsString('Shown', $text);
    }

    public function test_links_in_extracted_html_use_the_safe_url_rules(): void
    {
        $extractor = app(PlainTextExtractor::class);

        $this->assertSame('click', $extractor->extractFromHtml('<a href="javascript:alert(1)">click</a>'));
        $this->assertSame('click', $extractor->extractFromHtml('<a href="&#106;avascript:alert(1)">click</a>'));
        $this->assertSame('click (https://example.test/a?b=1&c=2)', $extractor->extractFromHtml('<a href="https://example.test/a?b=1&amp;c=2">click</a>'));
        $this->assertSame('click ({{ link }})', $extractor->extractFromHtml('<a href="{{ link }}">click</a>'));
    }

    public function test_the_plain_text_of_a_string_template_has_no_script_url(): void
    {
        $this->assertStringNotContainsString('javascript:', MailBuilder::plainText('<p><a href="javascript:alert(1)">go</a></p>'));
    }

    public function test_rich_text_goes_through_the_same_sanitizer_as_the_html(): void
    {
        $slot = new EmailSlot(SlotType::BodyText, ['content' => '<p>Hello <a href="javascript:alert(1)">there</a></p><script>alert(1)</script>']);

        $text = app(PlainTextExtractor::class)->extractFromSlot($slot);

        $this->assertStringNotContainsString('alert', $text);
        $this->assertStringContainsString('Hello there', $text);
    }
}

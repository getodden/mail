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

class GallerySlotTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function data(int $columns = 3): array
    {
        return [
            'heading' => 'From the Cellar',
            'columns' => $columns,
            'images' => [
                ['image_url' => 'https://example.test/a.jpg', 'alt' => 'Barrels', 'caption' => 'Oak barrels', 'link_url' => 'https://example.test/a'],
                ['image_url' => 'https://example.test/b.jpg', 'alt' => 'Bottles', 'caption' => 'Fresh bottles'],
                ['image_url' => 'https://example.test/c.jpg', 'alt' => 'Vines'],
                ['image_url' => 'https://example.test/d.jpg', 'alt' => 'Press'],
            ],
        ];
    }

    public function test_the_schema_exports_every_field_the_editor_reads(): void
    {
        $exported = collect(SlotSchemaRegistry::toArray()['slots'])->firstWhere('type', 'gallery');

        $this->assertSame('photo', $exported['icon']);
        $this->assertSame(SlotGroup::Content, SlotType::Gallery->group());
        $this->assertTrue($exported['supports_visibility']);

        $fields = collect($exported['fields'])->keyBy('key');
        $this->assertSame(['heading', 'columns', 'images'], $fields->keys()->all());
        $this->assertSame('select', $fields['columns']['type']);
        $this->assertSame([2, 3], array_column($fields['columns']['options'], 'value'));
        $this->assertSame(3, $fields['columns']['default']);
        $this->assertSame('items', $fields['images']['type']);
        $this->assertSame(3, $fields['images']['default_items']);
        $this->assertSame(['image_url', 'alt', 'caption', 'link_url'], array_column($fields['images']['items'], 'key'));
        $this->assertSame('image', $fields['images']['items'][0]['type']);
        $this->assertTrue($fields['images']['items'][0]['required']);
        $this->assertSame('url', $fields['images']['items'][3]['type']);
    }

    public function test_it_compiles_its_content_and_stacks_on_mobile(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Gallery, $this->data()));

        foreach (['From the Cellar', 'https://example.test/a.jpg', 'alt="Barrels"', 'Oak barrels', 'Fresh bottles', 'href="https://example.test/a"', 'https://example.test/d.jpg'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $this->assertSame(4, substr_count($html, 'class="stack-column"'));
        $this->assertStringContainsString('<!--[if mso]>', $html);
    }

    public function test_two_columns_use_wider_cells_than_three(): void
    {
        $compiler = app(EmailSlotCompiler::class);

        $this->assertStringContainsString('width="48%"', $compiler->compileSlot(new EmailSlot(SlotType::Gallery, $this->data(2))));
        $this->assertStringContainsString('width="31%"', $compiler->compileSlot(new EmailSlot(SlotType::Gallery, $this->data(3))));
    }

    public function test_values_are_escaped(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Gallery, [
            'heading' => '<script>alert(1)</script>',
            'images' => [[
                'image_url' => 'https://example.test/a.jpg',
                'alt' => '"><script>alert(2)</script>',
                'caption' => '<script>alert("x")</script> it\'s "quoted"',
                'link_url' => 'https://example.test/?q="><script>alert(3)</script>',
            ]],
        ]));

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; it&#039;s &quot;quoted&quot;', $html);
    }

    public function test_plain_text_contains_its_content(): void
    {
        $text = app(PlainTextExtractor::class)->extractFromSlot(new EmailSlot(SlotType::Gallery, $this->data()));

        $this->assertStringContainsString('=== From the Cellar ===', $text);
        $this->assertStringContainsString('* Oak barrels (https://example.test/a)', $text);
        $this->assertStringContainsString('* Fresh bottles', $text);
        $this->assertStringContainsString('* Vines', $text);
    }

    public function test_it_supports_a_visibility_rule(): void
    {
        $slot = new EmailSlot(SlotType::Gallery, [...$this->data(), 'visibility' => [
            'field' => 'contact.region',
            'operator' => 'equals',
            'value' => 'north',
        ]]);
        $evaluator = new SlotVisibilityEvaluator;

        $this->assertTrue($evaluator->isVisible($slot, ['contact' => ['region' => 'north']]));
        $this->assertFalse($evaluator->isVisible($slot, ['contact' => ['region' => 'south']]));
    }
}

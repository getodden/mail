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

    /**
     * @return list<array<string, string>>
     */
    private function images(int $count): array
    {
        return array_map(fn (int $i): array => ['image_url' => "https://example.test/{$i}.jpg", 'alt' => "Image {$i}"], range(1, $count));
    }

    private function msoBlocks(string $html): string
    {
        preg_match_all('/<!--\[if mso\]>(.*?)<!\[endif\]-->/s', $html, $matches);

        return implode("\n", $matches[1]);
    }

    public function test_four_images_in_three_columns_make_two_rows(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Gallery, ['columns' => 3, 'images' => $this->images(4)]));

        $this->assertSame(2, substr_count($this->msoBlocks($html), '<tr>'));
        $this->assertSame(4, substr_count($html, 'class="stack-column"'));
    }

    public function test_five_images_in_two_columns_make_three_rows(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Gallery, ['columns' => 2, 'images' => $this->images(5)]));

        $this->assertSame(3, substr_count($this->msoBlocks($html), '<tr>'));
    }

    public function test_outlook_markup_has_one_balanced_cell_per_image(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Gallery, ['columns' => 3, 'images' => $this->images(4)]));
        $mso = $this->msoBlocks($html);

        $this->assertSame(4, substr_count($mso, '<td'));
        $this->assertSame(4, substr_count($mso, '</td>'));
        $this->assertSame(substr_count($mso, '<table'), substr_count($mso, '</table>'));
        $this->assertSame(substr_count($mso, '<tr>'), substr_count($mso, '</tr>'));
        $this->assertSame(4, substr_count($mso, 'width="160"'));
    }

    public function test_two_columns_use_250_pixel_cells(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Gallery, ['columns' => 2, 'images' => $this->images(2)]));

        $this->assertSame(2, substr_count($this->msoBlocks($html), 'width="250"'));
        $this->assertStringContainsString('max-width: 250px', $html);
    }

    public function test_string_column_counts_behave_like_integers(): void
    {
        $compiler = app(EmailSlotCompiler::class);

        $this->assertStringContainsString('width="48%"', $compiler->compileSlot(new EmailSlot(SlotType::Gallery, ['columns' => '2', 'images' => $this->images(2)])));
        $this->assertStringContainsString('width="31%"', $compiler->compileSlot(new EmailSlot(SlotType::Gallery, ['columns' => '3', 'images' => $this->images(2)])));
    }

    public function test_a_default_empty_gallery_has_no_plain_text(): void
    {
        $data = ['columns' => 3, 'images' => [[], [], []]];

        $this->assertSame('', app(PlainTextExtractor::class)->extractFromSlot(new EmailSlot(SlotType::Gallery, $data)));
    }

    public function test_images_are_capped_at_twelve(): void
    {
        $slot = new EmailSlot(SlotType::Gallery, ['columns' => 3, 'images' => $this->images(15)]);

        $this->assertSame(12, substr_count(app(EmailSlotCompiler::class)->compileSlot($slot), 'class="stack-column"'));
        $this->assertSame(12, substr_count(app(PlainTextExtractor::class)->extractFromSlot($slot), '* '));
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

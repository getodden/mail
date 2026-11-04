<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;
use Odden\MailBuilder\Tests\TestCase;

class FooterWarningTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function data(): array
    {
        return [
            'company_name' => 'Cask Co.',
            'address' => '1 Barrel Way',
            'unsubscribe_url' => 'https://example.test/unsubscribe',
            'copyright_year' => 2030,
        ];
    }

    public function test_the_schema_exports_the_warning_field(): void
    {
        $footer = collect(SlotSchemaRegistry::toArray()['slots'])->firstWhere('type', 'footer');
        $warning = collect($footer['fields'])->firstWhere('key', 'warning');

        $this->assertSame('textarea', $warning['type']);
        $this->assertSame('Required Notice / Warning', $warning['label']);
        $this->assertFalse($warning['required']);
    }

    public function test_the_warning_renders_above_the_address_and_is_escaped(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlot(new EmailSlot(SlotType::Footer, [
            ...$this->data(),
            'warning' => "Must be 21 or older.\n<script>alert(1)</script>",
        ]));

        $this->assertStringContainsString('Must be 21 or older.<br />', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertLessThan(strpos($html, '1 Barrel Way'), strpos($html, 'Must be 21 or older.'));
    }

    public function test_an_empty_warning_leaves_the_footer_unchanged(): void
    {
        $compiler = app(EmailSlotCompiler::class);
        $without = $compiler->compileSlot(new EmailSlot(SlotType::Footer, $this->data()));

        $this->assertSame($without, $compiler->compileSlot(new EmailSlot(SlotType::Footer, [...$this->data(), 'warning' => ''])));
        $this->assertStringNotContainsString('font-size: 11px', $without);
    }

    public function test_the_warning_is_in_plain_text_and_absent_when_empty(): void
    {
        $extractor = app(PlainTextExtractor::class);
        $with = $extractor->extractFromSlot(new EmailSlot(SlotType::Footer, [...$this->data(), 'warning' => 'Must be 21 or older.']));
        $without = $extractor->extractFromSlot(new EmailSlot(SlotType::Footer, $this->data()));

        $this->assertStringContainsString('Must be 21 or older.', $with);
        $this->assertStringNotContainsString('Must be 21', $without);
        $this->assertSame(str_replace("Must be 21 or older.\n", '', $with), $without);
    }
}

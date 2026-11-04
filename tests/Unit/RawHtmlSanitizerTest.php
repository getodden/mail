<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use DOMDocument;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Support\MergeTagGuard;
use Odden\MailBuilder\Support\SlotDataSanitizer;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RawHtmlSanitizerTest extends TestCase
{
    private const FORBIDDEN = '/javascript:|onerror|<script|<iframe|<form|<input|<meta|<svg|<object|<embed|srcdoc|xlink|<animate|evil\.test|http-equiv/i';

    /**
     * @return array<string, array{string}>
     */
    public static function slotFields(): array
    {
        return [
            'html slot' => ['html', 'html'],
            'body text slot' => ['body_text', 'content'],
        ];
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function exploits(): array
    {
        $cases = [
            'quote desync single' => "<p x'> y='<a href=javascript:alert(1) '>click</a>",
            'quote desync double' => '<b q"> z="<a href=javascript:alert(1) ">click</a>',
            'script element' => '<script>alert(1)</script>',
            'image onerror' => '<img src=x onerror=alert(1)>',
            'meta refresh' => '<meta http-equiv=refresh content="0;url=javascript:alert(1)">',
            'iframe srcdoc' => '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
            'form and input' => '<form action=https://evil.test><input name=p></form>',
            'svg animate' => '<svg><animate attributeName="href" values="javascript:alert(1)"/></svg>',
            'xlink href' => '<svg><a xlink:href="javascript:alert(1)"><text>x</text></a></svg><a xlink:href="javascript:alert(1)">y</a>',
            'style url javascript' => '<p style="background:url(javascript:alert(1))">x</p>',
            'style url remote' => '<p style="list-style-image:url(https://evil.test/x)">x</p>',
            'style css escape' => '<p style="background:u\\72l(https://evil.test/x)">x</p>',
            'entity encoded scheme' => '<a href="&#106;avascript:alert(1)">x</a>',
            'tab in scheme' => "<a href=\"java\tscript:alert(1)\">x</a>",
            'object and embed' => '<object data="javascript:alert(1)"></object><embed src="javascript:alert(1)">',
            'base element' => '<base href="https://evil.test/"><a href="x">y</a>',
            'merge tag attribute injection' => "<a href='{{ a|default(\" onmouseover=\"alert(1)) }}'>x</a>",
        ];

        $dataset = [];

        foreach ($cases as $name => $html) {
            foreach (self::slotFields() as $slotName => [$type, $field]) {
                $dataset["{$name} in {$slotName}"] = [$type, $field, $html];
            }
        }

        return $dataset;
    }

    private function compileSlot(string $type, string $field, string $html): string
    {
        return app(EmailSlotCompiler::class)->compileSlot(EmailSlot::fromArray(['type' => $type, 'data' => [$field => $html]]));
    }

    /**
     * @return list<array<string, string>>
     */
    private function elementAttributes(string $html, string $tag): array
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>');
        $found = [];

        foreach ($document->getElementsByTagName($tag) as $element) {
            $attributes = [];

            foreach ($element->attributes ?? [] as $attribute) {
                $attributes[$attribute->nodeName] = $attribute->nodeValue ?? '';
            }

            $found[] = $attributes;
        }

        return $found;
    }

    #[DataProvider('exploits')]
    public function test_exploits_are_neutralised_in_raw_slots(string $type, string $field, string $html): void
    {
        $output = $this->compileSlot($type, $field, $html);

        $this->assertDoesNotMatchRegularExpression(self::FORBIDDEN, $output);

        foreach ($this->elementAttributes($output, '*') as $attributes) {
            $this->assertSame([], array_filter(array_keys($attributes), fn (string $name): bool => str_starts_with($name, 'on')));
            $this->assertDoesNotMatchRegularExpression('/^\s*(?:javascript|data|vbscript):/i', $attributes['href'] ?? '');
        }
    }

    public function test_the_quote_desync_payload_leaves_a_link_without_a_destination(): void
    {
        $output = $this->compileSlot('html', 'html', "<p x'> y='<a href=javascript:alert(1) '>click</a>");

        $this->assertStringContainsString('click</a>', $output);
        $this->assertSame([[]], $this->elementAttributes($output, 'a'));
    }

    public function test_a_bulletproof_table_with_inline_styles_survives(): void
    {
        $html = '<table width="600" align="center" bgcolor="#f4f4f4" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#f4f4f4;border-radius:4px"><tr><td align="center" valign="top" colspan="2" style="padding:10px 20px;font-family:Arial,sans-serif">Hello <strong>world</strong></td></tr></table>';

        $output = $this->compileSlot('html', 'html', $html);

        $this->assertStringContainsString('<table width="600" align="center" bgcolor="#f4f4f4" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#f4f4f4;border-radius:4px">', $output);
        $this->assertStringContainsString('<td align="center" valign="top" colspan="2" style="padding:10px 20px;font-family:Arial,sans-serif">Hello <strong>world</strong></td>', $output);
    }

    public function test_style_declarations_that_load_or_run_things_are_removed_and_the_rest_kept(): void
    {
        $output = $this->compileSlot('html', 'html', '<p style="color:red; list-style-image:url(https://evil.test/x); width:50%; behavior:url(x.htc); -moz-binding:url(x)">x</p>');

        $this->assertStringContainsString('style="color:red; width:50%"', $output);
        $this->assertStringNotContainsString('evil.test', $output);
    }

    public function test_links_and_images_with_safe_destinations_survive(): void
    {
        $output = $this->compileSlot('body_text', 'content', '<a href="https://example.com/?utm_source=a:b">l</a><img src="https://cdn.example.com/a.png" width="600" alt="x"><a href="mailto:a@b.co">m</a><a href="/relative">r</a>');

        [$link, $mail, $relative] = $this->elementAttributes($output, 'a');
        $this->assertSame('https://example.com/?utm_source=a:b', $link['href']);
        $this->assertSame('mailto:a@b.co', $mail['href']);
        $this->assertSame('/relative', $relative['href']);
        $this->assertSame([['src' => 'https://cdn.example.com/a.png', 'width' => '600', 'alt' => 'x']], $this->elementAttributes($output, 'img'));
    }

    public function test_cid_and_raster_data_images_survive_and_other_data_uris_do_not(): void
    {
        $output = $this->compileSlot('html', 'html', '<img src="cid:logo" alt="a"><img src="data:image/png;base64,iVBORw0KGgo=" alt="b"><img src="data:image/svg+xml;base64,PHN2Zz4=" alt="c">');

        $this->assertSame([['src' => 'cid:logo', 'alt' => 'a'], ['src' => 'data:image/png;base64,iVBORw0KGgo=', 'alt' => 'b'], ['alt' => 'c']], $this->elementAttributes($output, 'img'));
    }

    public function test_merge_tags_survive_byte_for_byte_in_links_and_text(): void
    {
        $html = '<p>Hi {{ contact.first_name | default("friend") }}</p><a href="{{ unsubscribe_url }}">Leave</a><img src="{{ logo_url }}" alt="x">';

        $output = $this->compileSlot('html', 'html', $html);

        $this->assertStringContainsString('{{ contact.first_name | default("friend") }}', $output);
        $this->assertStringContainsString('<a href="{{ unsubscribe_url }}">Leave</a>', $output);
        $this->assertStringContainsString('<img src="{{ logo_url }}" alt="x" />', $output);
    }

    public function test_block_merge_tags_survive_in_rich_text_but_not_in_the_html_slot(): void
    {
        $html = '<p>{% if vip %}welcome back{% else %}welcome{% endif %}</p>';

        $this->assertStringContainsString('{% if vip %}welcome back{% else %}welcome{% endif %}', $this->compileSlot('body_text', 'content', $html));
        $this->assertStringNotContainsString('welcome', $this->compileSlot('html', 'html', $html));
    }

    public function test_a_merge_tag_cannot_break_out_of_an_attribute(): void
    {
        $output = $this->compileSlot('html', 'html', "<a href='{{ a|default(\" onmouseover=\"alert(1)) }}'>x</a>");

        $links = $this->elementAttributes($output, 'a');

        $this->assertCount(1, $links);
        $this->assertSame(['href'], array_keys($links[0]));
    }

    public function test_mso_conditional_comments_are_dropped(): void
    {
        $output = $this->compileSlot('html', 'html', '<!--[if mso]><v:roundrect href="x"></v:roundrect><![endif]--><p>after</p>');

        $this->assertStringNotContainsString('roundrect', $output);
        $this->assertStringContainsString('<p>after</p>', $output);
    }

    public function test_script_and_style_are_dropped_with_their_contents(): void
    {
        $output = $this->compileSlot('body_text', 'content', 'before<script>secretScript()</script><style>.secretStyle{}</style>after');

        $this->assertStringNotContainsString('secret', $output);
        $this->assertStringContainsString('beforeafter', $output);
    }

    public function test_head_and_form_elements_are_dropped_or_unwrapped(): void
    {
        $output = $this->compileSlot('html', 'html', '<title>secretTitle</title><textarea>secretArea</textarea><form action="https://evil.test"><label>Name</label><input value="secretInput"></form><p>kept</p>');

        $this->assertStringNotContainsString('secret', $output);
        $this->assertStringNotContainsString('evil.test', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('<p>kept</p>', $output);
    }

    public function test_a_large_document_is_not_truncated(): void
    {
        $html = str_repeat('<p>paragraph of normal text</p>', 3000);

        $this->assertSame(3000, substr_count($this->compileSlot('body_text', 'content', $html), '</p>'));
    }

    public function test_a_body_text_just_under_the_cap_compiles_quickly(): void
    {
        $html = str_repeat('<p style="color:#333">Hello <a href="https://example.com/a?b=1">link</a> {{ name }} and <strong>bold</strong> text.</p>', 750);
        $this->assertGreaterThan(80_000, strlen($html));
        $this->assertLessThan(100_000, strlen($html));

        $started = microtime(true);
        $output = $this->compileSlot('body_text', 'content', $html);

        $this->assertLessThan(1.0, microtime(true) - $started);
        $this->assertSame(750, substr_count($output, '{{ name }}'));
    }

    public function test_unclosed_merge_tag_openers_compile_in_linear_time(): void
    {
        $html = str_repeat('{{ ', 30_000).'}';

        $started = microtime(true);
        $output = app(EmailSlotCompiler::class)->compileSlots([['type' => 'body_text', 'data' => ['content' => $html]]]);

        $this->assertLessThan(1.0, microtime(true) - $started);
        $this->assertStringContainsString('{{ {{ ', $output);
    }

    public function test_the_merge_tag_guard_round_trips_tags_and_ignores_unclosed_openers(): void
    {
        $html = 'a {{ one }} b {% two %} c {{ three {{ four }} {{ unclosed {% x %} {{ y <b> }} {{}} {%%}';

        [$protected, $originals] = MergeTagGuard::protect($html);

        $this->assertSame(array_values($originals), ['{{ one }}', '{% two %}', '{{ four }}', '{% x %}', '{{}}', '{%%}']);
        $this->assertStringNotContainsString('{{ one }}', $protected);
        $this->assertStringContainsString('{{ three ', $protected);
        $this->assertSame($html, MergeTagGuard::restore($protected, $originals));
    }

    public function test_rich_text_fields_of_any_slot_are_sanitised(): void
    {
        $fields = [new SlotField('note', FieldType::RichText, 'Note'), new SlotField('code', FieldType::Textarea, 'Code', rawHtml: true), new SlotField('plain', FieldType::Textarea, 'Plain')];

        $data = SlotDataSanitizer::data(['note' => '<script>x</script><b>n</b>', 'code' => '<img src=x onerror=y>', 'plain' => '<script>x</script>'], $fields);

        $this->assertSame('<b>n</b>', $data['note']);
        $this->assertSame('<img src="x" />', $data['code']);
        $this->assertSame('<script>x</script>', $data['plain']);
    }

    public function test_a_non_scalar_value_in_a_markup_field_is_dropped(): void
    {
        $slot = EmailSlot::fromArray(['type' => 'body_text', 'data' => ['content' => ['<script>x</script>']]]);

        $this->assertStringNotContainsString('<script>', app(EmailSlotCompiler::class)->compileSlot($slot));
    }

    public function test_the_full_document_pipeline_neutralises_the_quote_desync_payloads(): void
    {
        $html = MailBuilder::compile([
            ['type' => 'html', 'data' => ['html' => "<p x'> y='<a href=javascript:alert(1) '>click</a>"]],
            ['type' => 'body_text', 'data' => ['content' => '<b q"> z="<a href=javascript:alert(1) ">click</a>']],
        ]);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertSame(2, substr_count($html, 'click</a>'));
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Dom\HTMLDocument;
use DOMDocument;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Exceptions\InvalidMergeTagStructureException;
use Odden\MailBuilder\Mail\TemplateMailable;
use Odden\MailBuilder\Support\HtmlUrlScrubber;
use Odden\MailBuilder\Support\RawHtmlSanitizer;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MergeTagPolicyTest extends TestCase
{
    private const VIP_TABLE = '<table><tr><td>Everyone</td></tr>{% if contact.is_vip %}<tr><td>VIP row</td></tr>{% endif %}</table>';

    /**
     * @return list<string>
     */
    private function eventAttributes(string $html): array
    {
        $found = [];

        if (class_exists(HTMLDocument::class)) {
            $document = @HTMLDocument::createFromString($html, \LIBXML_NOERROR);

            foreach ($document->getElementsByTagName('*') as $element) {
                foreach ($element->attributes as $attribute) {
                    $found[] = $attribute->nodeName;
                }
            }
        } else {
            $document = new DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

            foreach ($document->getElementsByTagName('*') as $element) {
                foreach ($element->attributes ?? [] as $attribute) {
                    $found[] = $attribute->nodeName;
                }
            }
        }

        return array_values(array_filter($found, static fn (string $name): bool => str_starts_with(strtolower($name), 'on')));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function compileWith(string $type, string $field, string $html, array $context, bool $inlineCss = true): string
    {
        return app(EmailSlotCompiler::class)->compileSlots(
            [['type' => $type, 'data' => [$field => $html]]],
            ['interpolate' => true, 'context' => $context, 'inline_css' => $inlineCss],
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function markupSlots(): array
    {
        return ['html slot' => ['html', 'html'], 'body text slot' => ['body_text', 'content']];
    }

    public function test_the_rich_profile_unwraps_table_elements_and_the_html_profile_keeps_them(): void
    {
        $html = '<table><thead><tr><th>H</th></tr></thead><tbody><tr><td>Cell</td></tr></tbody></table>';

        $rich = RawHtmlSanitizer::sanitize($html, 'rich');
        $full = RawHtmlSanitizer::sanitize($html, 'html');

        $this->assertDoesNotMatchRegularExpression('/<\/?(?:table|thead|tbody|tfoot|tr|td|th)\b/', $rich);
        $this->assertStringContainsString('HCell', $rich);
        $this->assertStringContainsString('<td>Cell</td>', $full);
        $this->assertStringContainsString('<table>', RawHtmlSanitizer::sanitize($html));
    }

    public function test_body_text_slots_use_the_rich_profile_and_html_slots_the_html_profile(): void
    {
        $compiler = app(EmailSlotCompiler::class);
        $table = '<table role="presentation"><tr><td class="probe-cell">Cell</td></tr></table>';

        $body = $compiler->compileSlots([['type' => 'body_text', 'data' => ['content' => $table]]]);
        $html = $compiler->compileSlots([['type' => 'html', 'data' => ['html' => $table]]]);

        $this->assertStringNotContainsString('probe-cell', $body);
        $this->assertStringContainsString('probe-cell', $html);
    }

    public function test_an_unknown_profile_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RawHtmlSanitizer::sanitize('<p>x</p>', 'other');
    }

    #[DataProvider('vipStates')]
    public function test_a_conditional_row_in_the_html_slot_is_removed_whole_and_never_shown_to_the_wrong_audience(bool $isVip): void
    {
        $output = $this->compileWith('html', 'html', self::VIP_TABLE, ['contact' => ['is_vip' => $isVip]]);

        $this->assertStringContainsString('Everyone', $output);
        $this->assertStringNotContainsString('VIP row', $output);
        $this->assertStringNotContainsString('{%', $output);
        $this->assertStringNotContainsString('is_vip', $output);
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function vipStates(): array
    {
        return ['not vip' => [false], 'vip' => [true]];
    }

    #[DataProvider('vipStates')]
    public function test_a_conditional_in_the_rich_profile_still_wraps_its_content(bool $isVip): void
    {
        $output = $this->compileWith('body_text', 'content', self::VIP_TABLE, ['contact' => ['is_vip' => $isVip]]);

        $this->assertStringContainsString('Everyone', $output);
        $this->assertSame($isVip, str_contains($output, 'VIP row'));
    }

    public function test_the_rich_profile_keeps_block_tags_in_text_for_the_interpolator(): void
    {
        $this->assertStringContainsString('{% if a %}x{% else %}y{% endif %}', RawHtmlSanitizer::sanitize('<p>{% if a %}x{% else %}y{% endif %}</p>', 'rich'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function blockRegions(): array
    {
        return [
            'if' => ['a {% if x %}SECRET{% endif %} b', 'a  b'],
            'if else' => ['a {% if x %}SECRET{% else %}OTHER{% endif %} b', 'a  b'],
            'nested' => ['a {% if x %}SECRET{% if y %}INNER{% endif %}MORE{% endif %} b', 'a  b'],
            'for' => ['a {% for i in items %}SECRET{% endfor %} b', 'a  b'],
            'handlebars if' => ['a {{#if x}}SECRET{{else}}OTHER{{/if}} b', 'a  b'],
            'handlebars each' => ['a {{#each items}}SECRET{{/each}} b', 'a  b'],
            'handlebars unless' => ['a {{#unless x}}SECRET{{/unless}} b', 'a  b'],
            'spacing and case' => ['a {%  IF x  %}SECRET{%ENDIF%} b', 'a  b'],
            'inside markup' => ['<p>a {% if x %}<b>SECRET</b>{% endif %} b</p>', '<p>a  b</p>'],
            'stray endif' => ['a {% endif %} b', 'a  b'],
            'stray opener keeps content' => ['a {% if x %}KEPT b', 'a KEPT b'],
            'stray else' => ['a {{else}} b {{/if}} c', 'a  b  c'],
            'stray handlebars opener' => ['a {{#if x}}KEPT b', 'a KEPT b'],
            'balanced inside unbalanced opener' => ['{% if a %}KEPT {% if b %}SECRET{% endif %} z', 'KEPT  z'],
            'other tag' => ['a {% set x = 1 %} b', 'a  b'],
        ];
    }

    #[DataProvider('blockRegions')]
    public function test_block_tags_and_the_regions_they_wrap_are_removed_from_the_html_profile(string $input, string $expected): void
    {
        $this->assertSame($expected, RawHtmlSanitizer::sanitize($input));
    }

    public function test_variable_tags_survive_the_html_profile(): void
    {
        $this->assertSame('<p>Hi {{ contact.first_name | default("friend") }}</p>', RawHtmlSanitizer::sanitize('<p>Hi {{ contact.first_name | default("friend") }}</p>'));
    }

    public function test_a_block_tag_formed_by_dropped_markup_is_removed_with_its_region(): void
    {
        $output = RawHtmlSanitizer::sanitize('<p>a {<script></script>% if nope %}SECRET{<script></script>% endif %} b</p>');

        $this->assertStringNotContainsString('SECRET', $output);
        $this->assertStringNotContainsString('{%', $output);
        $this->assertStringContainsString('a', $output);
    }

    public function test_a_block_tag_hidden_in_an_attribute_by_an_entity_is_removed(): void
    {
        $output = RawHtmlSanitizer::sanitize('<p title="{&#37; if nope %}">x</p>{% endif %}');

        $this->assertStringNotContainsString('{%', $output);
        $this->assertStringNotContainsString('{', $output);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function attributeTokens(): array
    {
        $prefs = "{{ contact.prefs_url | default('https://example.com/prefs') }}";

        return [
            'single quoted default stays byte for byte' => ["<a href=\"{$prefs}\">x</a>", "<a href=\"{$prefs}\">x</a>"],
            'plain tag' => ['<a href="{{ unsubscribe_url }}">x</a>', '<a href="{{ unsubscribe_url }}">x</a>'],
            'tag inside a url' => ['<img src="https://cdn.test/{{ id }}.png" alt="x">', '<img src="https://cdn.test/{{ id }}.png" alt="x" />'],
            'filters and bare arguments' => ['<a href="{{ a.b | upper | truncate(20) }}">x</a>', '<a href="{{ a.b | upper | truncate(20) }}">x</a>'],
            'double quoted argument is rewritten' => ['<a href=\'{{ a | default("https://x.test/p") }}\'>x</a>', "<a href=\"{{ a | default('https://x.test/p') }}\">x</a>"],
            'double quoted argument with a single quote is dropped' => ['<a href=\'{{ a | default("it\'s") }}\' title="t">x</a>', '<a href="" title="t">x</a>'],
            'quote in the token is dropped' => ['<p title=\'{{ " onmouseover=alert(1) x }}\'>x</p>', '<p title="">x</p>'],
            'ampersand argument is dropped' => ["<a href=\"{{ a | default('a&b') }}\">x</a>", '<a href="">x</a>'],
            'block token is dropped' => ['<p title="a{% if x %}b">x</p>', '<p title="ab">x</p>'],
            'path with a space is dropped' => ['<p title="{{ a b }}">x</p>', '<p title="">x</p>'],
            'stray braces are dropped' => ['<p title="a{b}c}}d{{">x</p>', '<p title="abcd">x</p>'],
        ];
    }

    #[DataProvider('attributeTokens')]
    public function test_merge_tags_in_attributes_follow_a_strict_grammar(string $input, string $expected): void
    {
        $this->assertSame($expected, RawHtmlSanitizer::sanitize($input));
        $this->assertSame($expected, RawHtmlSanitizer::sanitize($input, 'rich'));
    }

    public function test_a_merge_tag_in_a_style_attribute_follows_the_same_grammar(): void
    {
        $this->assertSame(
            "<p style=\"color:{{ brand | default('#333') }}\">x</p>",
            RawHtmlSanitizer::sanitize("<p style=\"color:{{ brand | default('#333') }}\">x</p>"),
        );
        $this->assertSame('<p style="color:">x</p>', RawHtmlSanitizer::sanitize('<p style=\'color:{{ " x }}\'>x</p>'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function tagBreakingPayloads(): array
    {
        return [
            'title and onmouseover' => ['<p title="{% if nope %}">{% endif %}{{ " onmouseover=alert(1) x }}</p>'],
            'link and autofocus' => ['<a href="https://ok.test/{% if nope %}">x{% endif %}{{ " onfocus=alert(1) autofocus tabindex=1 x }}'],
            'entity hidden opener' => ['<p title="{&#37; if nope %}">{% endif %}{{ " onmouseover=alert(1) x }}</p>'],
            'handlebars' => ['<p title="{{#if nope}}">{{/if}}{{ " onmouseover=alert(1) x }}</p>'],
        ];
    }

    #[DataProvider('tagBreakingPayloads')]
    public function test_the_tag_breaking_payloads_stay_inert_in_both_slots_whatever_the_context(string $payload): void
    {
        foreach ([false, true] as $nope) {
            foreach (self::markupSlots() as [$type, $field]) {
                foreach ([true, false] as $inlineCss) {
                    try {
                        $output = $this->compileWith($type, $field, $payload, ['nope' => $nope], $inlineCss);
                    } catch (InvalidMergeTagStructureException) {
                        continue;
                    }

                    $this->assertSame([], $this->eventAttributes($output), "{$type} nope=".var_export($nope, true).": {$output}");
                }
            }
        }
    }

    #[DataProvider('tagBreakingPayloads')]
    public function test_the_tag_breaking_payloads_stay_inert_through_template_mailable(string $payload): void
    {
        foreach ([false, true] as $nope) {
            foreach (self::markupSlots() as [$type, $field]) {
                try {
                    $mailable = new TemplateMailable(
                        template: EmailDocument::fromArray(['slots' => [['type' => $type, 'data' => [$field => $payload]]]]),
                        data: ['nope' => $nope],
                    );
                } catch (InvalidMergeTagStructureException) {
                    continue;
                }

                $this->assertSame([], $this->eventAttributes($mailable->compiledHtml), $mailable->compiledHtml);
            }
        }
    }

    public function test_the_url_scrubber_strips_event_handler_attributes(): void
    {
        $output = HtmlUrlScrubber::scrub('<p onclick="a()" title="t" ONMOUSEOVER=b() onfocus>x</p><a href="https://ok.test" onfocus = "c()">y</a>');

        $this->assertSame([], $this->eventAttributes($output));
        $this->assertStringContainsString('title="t"', $output);
        $this->assertStringContainsString('href="https://ok.test"', $output);
    }

    public function test_the_url_scrubber_keeps_the_amp_on_attribute_and_text_about_ons(): void
    {
        $html = '<button on="tap:x.toggle">online only</button>';

        $this->assertSame($html, HtmlUrlScrubber::scrub($html));
    }
}

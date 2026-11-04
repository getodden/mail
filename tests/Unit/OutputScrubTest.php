<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Exceptions\UnscrubbableHtmlException;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Support\HtmlUrlScrubber;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class OutputScrubTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $data
     */
    private function compile(SlotType $type, array $data, array $context = []): string
    {
        $document = new EmailDocument;
        $document->append($type, $data);

        return app(EmailSlotCompiler::class)->compileDocument($document, ['context' => $context, 'interpolate' => true]);
    }

    private function assertNoScript(string $html): void
    {
        $this->assertDoesNotMatchRegularExpression('/javascript:/i', $html);
    }

    public function test_a_merge_tag_that_resolves_to_a_script_url_is_scrubbed(): void
    {
        $html = $this->compile(SlotType::Button, ['text' => 'Go', 'url' => '{{link}}'], ['link' => 'javascript:alert(1)']);

        $this->assertNoScript($html);
        $this->assertStringContainsString('href="#"', $html);
    }

    public function test_a_merge_tag_default_that_is_a_script_url_is_scrubbed(): void
    {
        $html = $this->compile(SlotType::Button, ['text' => 'Go', 'url' => '{{x|default(javascript:alert(1))}}'], ['name' => 'a']);

        $this->assertNoScript($html);
    }

    public function test_the_button_url_alias_key_is_scrubbed(): void
    {
        $html = $this->compile(SlotType::Button, ['text' => 'Go', 'button_url' => 'javascript:alert(1)']);

        $this->assertNoScript($html);
    }

    public function test_the_footer_unsubscribe_url_is_scrubbed(): void
    {
        $html = $this->compile(SlotType::Footer, ['company_name' => 'Acme', 'address' => '1 St', 'unsubscribe_url' => 'javascript:alert(1)']);

        $this->assertNoScript($html);
    }

    public function test_body_text_links_are_scrubbed_including_entity_encoded_schemes(): void
    {
        $plain = $this->compile(SlotType::BodyText, ['content' => '<a href="javascript:alert(1)">x</a>']);
        $encoded = $this->compile(SlotType::BodyText, ['content' => '<a href="&#106;avascript:alert(1)">x</a>']);

        $this->assertNoScript($plain);
        $this->assertDoesNotMatchRegularExpression('/&#106;avascript/i', $encoded);
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*href/i', $encoded);
        $this->assertStringContainsString('>x</a>', $encoded);
    }

    public function test_html_slot_unquoted_and_single_quoted_attributes_are_scrubbed(): void
    {
        $unquoted = $this->compile(SlotType::Html, ['html' => '<a href=javascript:alert(1)>x</a>']);
        $single = $this->compile(SlotType::Html, ['html' => "<a HREF = 'JaVaScript:alert(1)'>x</a>"]);

        $this->assertNoScript($unquoted);
        $this->assertNoScript($single);
    }

    public function test_amp_urls_are_scrubbed(): void
    {
        $document = new EmailDocument;
        $document->append(SlotType::Hero, ['title' => 'Hi', 'button_text' => 'Go', 'button_url' => 'javascript:alert(1)']);
        $document->append(SlotType::Button, ['text' => 'Go', 'url' => 'javascript:alert(1)']);

        $amp = MailBuilder::amp($document);

        $this->assertNoScript($amp);
        $this->assertSame(2, substr_count($amp, 'href="#"'));
    }

    public function test_vml_and_conditional_comment_attributes_are_scrubbed(): void
    {
        $html = HtmlUrlScrubber::scrub('<!--[if mso]><v:roundrect href="javascript:alert(1)" style="x"><v:fill src="javascript:alert(2)"></v:roundrect><![endif]-->');

        $this->assertNoScript($html);
        $this->assertStringContainsString('<v:roundrect href="#" style="x">', $html);
        $this->assertStringContainsString('<v:fill src="">', $html);
    }

    public function test_image_attributes_use_image_rules_and_other_tags_use_link_rules(): void
    {
        $this->assertSame('<img src="">', HtmlUrlScrubber::scrub('<img src="javascript:alert(1)">'));
        $this->assertSame('<td background="">', HtmlUrlScrubber::scrub('<td background="javascript:alert(1)">'));
        $this->assertSame('<iframe src="#">', HtmlUrlScrubber::scrub('<iframe src="data:image/png;base64,AAAA">'));
        $this->assertSame('<form action="#">', HtmlUrlScrubber::scrub('<form action="javascript:alert(1)">'));
        $this->assertSame('<button formaction="#">', HtmlUrlScrubber::scrub('<button formaction=javascript:alert(1)>'));
        $this->assertSame('<video poster="#">', HtmlUrlScrubber::scrub('<video poster="javascript:alert(1)">'));
    }

    public function test_a_tag_with_thousands_of_attributes_is_still_scrubbed(): void
    {
        $attributes = implode(' ', array_map(fn (int $i): string => "a{$i}=b", range(0, 11_999)));

        $html = HtmlUrlScrubber::scrub("<p {$attributes}>hi</p><a href=\"javascript:alert(1)\">x</a>");

        $this->assertNoScript($html);
        $this->assertStringContainsString('<a href="#">x</a>', $html);
    }

    public function test_the_scrubber_fails_closed_when_pcre_gives_up(): void
    {
        $limit = (string) ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '5');

        try {
            $this->expectException(UnscrubbableHtmlException::class);

            HtmlUrlScrubber::scrub('<a href="javascript:alert(1)" class="aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa">x</a>');
        } finally {
            ini_set('pcre.backtrack_limit', $limit);
        }
    }

    public function test_xlink_href_and_object_data_are_scrubbed(): void
    {
        $this->assertSame('<a xlink:href="#">', HtmlUrlScrubber::scrub('<a xlink:href="javascript:alert(1)">'));
        $this->assertSame('<use XLINK:HREF="#">', HtmlUrlScrubber::scrub("<use XLINK:HREF='javascript:alert(1)'>"));
        $this->assertSame('<object data="#">', HtmlUrlScrubber::scrub('<object data="javascript:alert(1)">'));
        $this->assertSame('<object data="https://example.test/a.swf">', HtmlUrlScrubber::scrub('<object data="https://example.test/a.swf">'));
    }

    public function test_srcset_is_always_dropped(): void
    {
        $this->assertSame('<img src="https://example.test/a.png" >', HtmlUrlScrubber::scrub('<img src="https://example.test/a.png" srcset="javascript:alert(1) 1x, https://example.test/b.png 2x">'));
        $this->assertSame('<img  alt="x">', HtmlUrlScrubber::scrub("<img srcset='https://example.test/b.png 2x' alt=\"x\">"));
    }

    public function test_animation_values_with_an_unsafe_url_are_dropped(): void
    {
        $this->assertSame('<animate attributeName="href" >', HtmlUrlScrubber::scrub('<animate attributeName="href" values="https://example.test/a;javascript:alert(1)">'));
        $this->assertSame('<animate values="0;1;0.5">', HtmlUrlScrubber::scrub('<animate values="0;1;0.5">'));
        $this->assertSame('<animate values="https://example.test/a;#b">', HtmlUrlScrubber::scrub('<animate values="https://example.test/a;#b">'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function preserved(): array
    {
        return [
            'https' => ['<a href="https://example.test/a?b=1&amp;c=2">x</a>'],
            'merge tag' => ['<a href="{{unsubscribe_url}}">x</a>'],
            'mailto' => ['<a href="mailto:a@b.co?subject=x">x</a>'],
            'tel' => ['<a href=\'tel:+1 555\'>x</a>'],
            'relative' => ['<a href=/relative>x</a>'],
            'anchor' => ['<a href="#anchor">x</a>'],
            'cid' => ['<img src="cid:logo" alt="a>b">'],
            'data image' => ['<img src="data:image/png;base64,iVBORw0KGgo=" width="10">'],
            'spacing' => ['<a  href = "https://x.test"  class="a">x</a>'],
            'empty' => ['<a href="">x</a>'],
            'text mentioning href' => ['<p>use href="javascript:x" in text</p>'],
        ];
    }

    #[DataProvider('preserved')]
    public function test_safe_values_are_left_byte_for_byte(string $html): void
    {
        $this->assertSame($html, HtmlUrlScrubber::scrub($html));
    }

    public function test_a_document_of_normal_slots_compiles_unchanged(): void
    {
        $document = new EmailDocument;
        foreach (range(1, 30) as $i) {
            $document->append(SlotType::Button, ['text' => "Go $i", 'url' => "https://example.test/$i?a=1&b=2"]);
            $document->append(SlotType::Gallery, ['images' => [['image_url' => "https://example.test/$i.jpg", 'link_url' => "https://example.test/g$i"]]]);
        }

        $html = app(EmailSlotCompiler::class)->compileDocument($document);

        $this->assertSame($html, HtmlUrlScrubber::scrub($html));
        $this->assertStringContainsString('https://example.test/30?a=1&amp;b=2', $html);
    }
}

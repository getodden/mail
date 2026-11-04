<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
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

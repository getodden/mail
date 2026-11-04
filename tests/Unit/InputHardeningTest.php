<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Schema\SlotRegistry;
use Odden\MailBuilder\Support\SafeColor;
use Odden\MailBuilder\Support\SafeUrl;
use Odden\MailBuilder\Tests\Support\LinkSlot;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InputHardeningTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function unsafeUrls(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'mixed case' => ['JaVaScRiPt:alert(1)'],
            'tab in scheme' => ["java\tscript:alert(1)"],
            'newline in scheme' => ["java\nscript:alert(1)"],
            'leading space' => ['  javascript:alert(1)'],
            'vbscript' => ['vbscript:msgbox(1)'],
            'data html' => ['data:text/html,<script>alert(1)</script>'],
        ];
    }

    private function compile(SlotType|string $type, array $data): string
    {
        $slotType = $type instanceof SlotType ? $type : app(SlotRegistry::class)->get($type);

        return app(EmailSlotCompiler::class)->compileSlot(new EmailSlot($slotType, $data));
    }

    #[DataProvider('unsafeUrls')]
    public function test_unsafe_urls_are_neutralized_on_button_hero_event_and_gallery(string $url): void
    {
        $outputs = [
            $this->compile(SlotType::Button, ['text' => 'Go', 'url' => $url]),
            $this->compile(SlotType::Hero, ['title' => 'Hi', 'button_text' => 'Go', 'button_url' => $url]),
            $this->compile(SlotType::Event, ['title' => 'Party', 'button_text' => 'RSVP', 'button_url' => $url]),
            $this->compile(SlotType::Gallery, ['images' => [['image_url' => 'https://example.test/a.jpg', 'link_url' => $url]]]),
        ];

        foreach ($outputs as $html) {
            $this->assertDoesNotMatchRegularExpression('/(javascript|vbscript|data):/i', $html);
            $this->assertStringContainsString('href="#"', $html);
        }
    }

    public function test_unsafe_image_sources_are_dropped(): void
    {
        $html = $this->compile(SlotType::Gallery, ['images' => [['image_url' => 'javascript:alert(1)']]]);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('stack-column', $html);
    }

    public function test_nested_item_fields_are_neutralized(): void
    {
        $html = $this->compile(SlotType::ThreeColumn, ['columns' => [
            ['title' => 'One', 'button_text' => 'Go', 'button_url' => 'javascript:alert(1)'],
            ['title' => 'Two', 'button_text' => 'Go', 'button_url' => 'https://example.test/two'],
        ]]);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('https://example.test/two', $html);
    }

    public function test_a_custom_slot_is_covered_through_its_schema(): void
    {
        $this->app['view']->addNamespace('test-slots', __DIR__.'/../views');
        app(SlotRegistry::class)->flush();
        app(SlotRegistry::class)->register(new LinkSlot);

        $html = $this->compile('link_card', ['target_url' => 'javascript:alert(1)', 'accent' => 'red;background-image:url(https://x/t)']);

        app(SlotRegistry::class)->flush();

        $this->assertStringContainsString('href="#"', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('color: #111111;', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function safeUrls(): array
    {
        return [
            'https' => ['https://example.test/a?b=1&c=2'],
            'http' => ['http://example.test/'],
            'mailto' => ['mailto:hello@example.test'],
            'tel' => ['tel:+15551234567'],
            'merge tag' => ['{{unsubscribe_url}}'],
            'relative' => ['/feedback/nps/{{nps_token}}'],
            'anchor' => ['#anchor'],
            'protocol relative' => ['//cdn.example.test/a.png'],
            'empty' => [''],
        ];
    }

    #[DataProvider('safeUrls')]
    public function test_safe_urls_are_preserved(string $url): void
    {
        $this->assertSame($url, SafeUrl::for($url));
        $this->assertSame($url, SafeUrl::for($url, true));
    }

    public function test_safe_urls_survive_a_compile(): void
    {
        $html = $this->compile(SlotType::Button, ['text' => 'Go', 'url' => 'mailto:hello@example.test']);

        $this->assertStringContainsString('href="mailto:hello@example.test"', $html);

        $html = $this->compile(SlotType::Button, ['text' => 'Go', 'url' => '{{unsubscribe_url}}']);

        $this->assertStringContainsString('href="{{unsubscribe_url}}"', $html);
    }

    public function test_data_images_are_allowed_for_images_only(): void
    {
        $png = 'data:image/png;base64,iVBORw0KGgo=';

        $this->assertSame($png, SafeUrl::for($png, true));
        $this->assertSame('#', SafeUrl::for($png));
        $this->assertSame('', SafeUrl::for('data:image/svg+xml;base64,PHN2Zz4=', true));
        $this->assertSame('cid:logo', SafeUrl::for('cid:logo', true));
        $this->assertSame('#', SafeUrl::for('cid:logo'));
        $this->assertSame('', SafeUrl::for('javascript:alert(1)', true));
    }

    public function test_an_injected_color_is_dropped_so_the_default_applies(): void
    {
        $html = $this->compile(SlotType::Hero, ['title' => 'Hi', 'bg_color' => '#fff;background-image:url(https://x/t)']);

        $this->assertStringNotContainsString('background-image', $html);
        $this->assertStringContainsString('#0f172a', $html);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function colors(): array
    {
        return [
            'hex' => ['#2563eb', true],
            'short hex' => ['#fff', true],
            'hex alpha' => ['#ffffff80', true],
            'rgb' => ['rgb(1, 2, 3)', true],
            'rgba' => ['rgba(1, 2, 3, 0.5)', true],
            'hsl' => ['hsl(10, 50%, 50%)', true],
            'keyword' => ['red', true],
            'transparent' => ['transparent', true],
            'injection' => ['#fff;background-image:url(https://x/t)', false],
            'url function' => ['url(https://x/t)', false],
            'bad hex length' => ['#ffff0', false],
            'keyword with digits' => ['red1', false],
            'rgb with letters' => ['rgb(1, 2, expression)', false],
            'empty' => ['', false],
        ];
    }

    #[DataProvider('colors')]
    public function test_color_validation(string $value, bool $valid): void
    {
        $this->assertSame($valid ? $value : null, SafeColor::for($value));
    }

    public function test_unknown_keys_and_raw_html_are_untouched(): void
    {
        $html = $this->compile(SlotType::Html, ['html' => '<a href="javascript:void(0)">raw</a>']);

        $this->assertStringContainsString('javascript:void(0)', $html);
    }
}

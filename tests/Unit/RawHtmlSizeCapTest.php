<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Editor\PreviewRenderer;
use Odden\MailBuilder\Exceptions\RawHtmlTooLargeException;
use Odden\MailBuilder\Support\RawHtmlSanitizer;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RawHtmlSizeCapTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function profiles(): array
    {
        return ['html' => ['html'], 'rich' => ['rich']];
    }

    #[DataProvider('profiles')]
    public function test_a_200_kb_body_is_rejected_quickly(string $profile): void
    {
        $html = str_repeat('<p>paragraph of normal text, padded out a little</p>', 4000);
        $this->assertGreaterThan(200_000, strlen($html));

        $started = microtime(true);

        try {
            RawHtmlSanitizer::sanitize($html, $profile);
            $this->fail('The oversized HTML was accepted.');
        } catch (RawHtmlTooLargeException $exception) {
            $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        }

        $this->assertLessThan(0.1, microtime(true) - $started);
    }

    #[DataProvider('profiles')]
    public function test_a_90_kb_body_is_fine(string $profile): void
    {
        $html = str_repeat('<p>paragraph of normal text, padded out a little</p>', 1750);
        $this->assertGreaterThan(85_000, strlen($html));

        $started = microtime(true);
        $output = RawHtmlSanitizer::sanitize($html, $profile);

        $this->assertSame(1750, substr_count($output, '</p>'));
        $this->assertLessThan(1.0, microtime(true) - $started);
    }

    public function test_the_attribute_flood_that_took_seven_seconds_is_rejected_at_once(): void
    {
        $attributes = '';

        for ($index = 1; $index <= 60_000; $index++) {
            $attributes .= " a{$index}=b";
        }

        $html = '<p'.$attributes.'>x</p>';
        $this->assertGreaterThan(500_000, strlen($html));

        $started = microtime(true);

        try {
            RawHtmlSanitizer::sanitize($html);
            $this->fail('The attribute flood was accepted.');
        } catch (RawHtmlTooLargeException) {
        }

        $this->assertLessThan(0.1, microtime(true) - $started);
    }

    public function test_many_short_merge_tags_under_the_cap_are_not_truncated(): void
    {
        $html = '<p>'.str_repeat('{{a}}', 18_000).'</p>';
        $this->assertLessThan(100_000, strlen($html));

        $this->assertSame(18_000, substr_count(RawHtmlSanitizer::sanitize($html), '{{a}}'));
    }

    public function test_the_cap_is_read_from_config_and_is_exact(): void
    {
        config(['mail-builder.raw_html_max_bytes' => 1000]);

        $this->assertSame(1000, RawHtmlSanitizer::maxBytes());
        $this->assertSame(1000, strlen(RawHtmlSanitizer::sanitize(str_repeat('a', 1000))));

        $this->expectException(RawHtmlTooLargeException::class);

        RawHtmlSanitizer::sanitize(str_repeat('a', 1001));
    }

    public function test_the_default_cap_is_100000_bytes(): void
    {
        $this->assertSame(100_000, RawHtmlSanitizer::maxBytes());
        $this->assertSame(100_000, config('mail-builder.raw_html_max_bytes'));
    }

    public function test_compiling_an_oversized_slot_throws(): void
    {
        $this->expectException(RawHtmlTooLargeException::class);

        app(EmailSlotCompiler::class)->compileSlots([['type' => 'body_text', 'data' => ['content' => str_repeat('a', 100_001)]]]);
    }

    public function test_the_preview_reports_the_oversized_slot_as_a_validation_error(): void
    {
        $payload = ['slots' => [
            ['type' => 'body_text', 'data' => ['content' => '<p>fine</p>']],
            ['type' => 'html', 'data' => ['html' => str_repeat('a', 100_001)]],
        ]];

        try {
            app(PreviewRenderer::class)->render($payload);
            $this->fail('The oversized slot was previewed.');
        } catch (ValidationException $exception) {
            $this->assertSame(['slots.1.data'], array_keys($exception->errors()));
            $this->assertStringContainsString('100000', $exception->errors()['slots.1.data'][0]);
        }
    }
}

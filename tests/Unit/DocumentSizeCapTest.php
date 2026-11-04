<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Editor\PreviewRenderer;
use Odden\MailBuilder\Exceptions\DocumentTooLargeException;
use Odden\MailBuilder\Tests\TestCase;
use RuntimeException;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class DocumentSizeCapTest extends TestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    private function richSlots(int $count, int $bytesEach): array
    {
        $paragraph = '<p>paragraph of normal text, padded out a little</p>';
        $content = str_repeat($paragraph, intdiv($bytesEach, strlen($paragraph)));

        return array_fill(0, $count, ['type' => 'body_text', 'data' => ['content' => $content]]);
    }

    public function test_the_default_cap_is_1500000_bytes(): void
    {
        $this->assertSame(1_500_000, config('mail-builder.max_document_bytes'));
    }

    public function test_forty_near_cap_slots_are_rejected_before_the_inliner_runs(): void
    {
        $inliner = new class extends CssToInlineStyles
        {
            public int $calls = 0;

            public function convert($html, $css = null): string
            {
                $this->calls++;

                throw new RuntimeException('The inliner ran.');
            }
        };
        $compiler = new EmailSlotCompiler(app('view'), [], null, $inliner);
        $document = EmailDocument::fromArray(['slots' => $this->richSlots(40, 90_000)]);

        memory_reset_peak_usage();
        $started = microtime(true);

        try {
            $compiler->compileDocument($document);
            $this->fail('The oversized document was compiled.');
        } catch (DocumentTooLargeException $exception) {
            $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        }

        $this->assertSame(0, $inliner->calls);
        $this->assertLessThan(5.0, microtime(true) - $started);
        $this->assertLessThan(96 * 1024 * 1024, memory_get_peak_usage());
    }

    public function test_a_normal_document_is_fine(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlots($this->richSlots(5, 20_000));

        $this->assertSame(5 * intdiv(20_000, strlen('<p>paragraph of normal text, padded out a little</p>')), substr_count($html, 'paragraph of normal text'));
    }

    public function test_the_cap_is_read_from_config(): void
    {
        config(['mail-builder.max_document_bytes' => 2000]);

        $this->expectException(DocumentTooLargeException::class);

        app(EmailSlotCompiler::class)->compileSlots($this->richSlots(1, 5_000));
    }

    public function test_the_preview_reports_the_oversized_document_on_slots(): void
    {
        config(['mail-builder.max_document_bytes' => 2000]);

        try {
            app(PreviewRenderer::class)->render(['slots' => $this->richSlots(2, 5_000)]);
            $this->fail('The oversized document was previewed.');
        } catch (ValidationException $exception) {
            $this->assertSame(['slots'], array_keys($exception->errors()));
            $this->assertStringContainsString('2000', $exception->errors()['slots'][0]);
        }
    }
}

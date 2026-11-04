<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Mail\TemplateMailable;
use Odden\MailBuilder\Tests\TestCase;
use Odden\MailBuilder\Transport\CidImageEmbedder;
use PHPUnit\Framework\Attributes\DataProvider;

class CidImageEmbedderTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function unsafeSources(): array
    {
        return [
            'svg' => ['data:image/svg+xml;base64,PHN2ZyBvbmxvYWQ9ImFsZXJ0KDEpIj48L3N2Zz4='],
            'uppercase svg' => ['DATA:IMAGE/SVG+XML;BASE64,PHN2Zz4='],
            'svg after a space' => [' data:image/svg+xml;base64,PHN2Zz4='],
            'html' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
        ];
    }

    #[DataProvider('unsafeSources')]
    public function test_an_image_that_is_not_a_safe_raster_is_never_embedded(string $src): void
    {
        $result = CidImageEmbedder::embed('<img alt="a" src="'.$src.'" width="10">');

        $this->assertSame([], $result['attachments']);
        $this->assertSame('<img alt="a" src="" width="10">', $result['html']);
    }

    public function test_a_source_that_is_not_a_data_uri_is_left_for_the_url_scrubber(): void
    {
        $html = '<img src="javascript:alert(1)">';

        $this->assertSame(['html' => $html, 'attachments' => []], CidImageEmbedder::embed($html));
    }

    #[DataProvider('rasterTypes')]
    public function test_raster_data_images_are_embedded(string $type, string $extension): void
    {
        $result = CidImageEmbedder::embed('<img src="data:image/'.$type.';base64,'.base64_encode('binary').'">');

        $this->assertCount(1, $result['attachments']);
        $this->assertSame("image/{$type}", $result['attachments'][0]['mime']);
        $this->assertStringEndsWith(".{$extension}", $result['attachments'][0]['name']);
        $this->assertStringContainsString('src="cid:img_', $result['html']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function rasterTypes(): array
    {
        return [
            'png' => ['png', 'png'],
            'jpeg' => ['jpeg', 'jpg'],
            'gif' => ['gif', 'gif'],
            'webp' => ['webp', 'webp'],
        ];
    }

    public function test_the_mailable_does_not_attach_an_svg(): void
    {
        $mailable = new TemplateMailable(
            template: '<img src="data:image/svg+xml;base64,PHN2Zz4=" alt="x"><img src="data:image/png;base64,'.base64_encode('png').'" alt="y">',
            embedCidImages: true,
        );

        $this->assertCount(1, $mailable->customAttachments);
        $this->assertSame('image/png', $mailable->customAttachments[0]['mime']);
        $this->assertStringNotContainsString('svg', $mailable->compiledHtml);
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Support\RawHtmlSanitizer;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SanitizerFidelityTest extends TestCase
{
    private function styleOf(string $html): ?string
    {
        return preg_match('/ style="([^"]*)"/', $html, $match) === 1 ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5) : null;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function profiles(): array
    {
        return ['html' => ['html'], 'rich' => ['rich']];
    }

    #[DataProvider('profiles')]
    public function test_del_ins_and_mark_are_kept(string $profile): void
    {
        $html = '<p>a <del>old</del> <ins>new</ins> <mark>hot</mark></p>';

        $this->assertSame($html, RawHtmlSanitizer::sanitize($html, $profile));
    }

    public function test_nowrap_is_kept_on_cells(): void
    {
        $this->assertStringContainsString('<td nowrap>', RawHtmlSanitizer::sanitize('<table><tr><td nowrap>x</td></tr></table>'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function allowedBackgrounds(): array
    {
        return [
            'unquoted' => ['background:url(https://x.test/y.jpg)', 'background:url(https://x.test/y.jpg)'],
            'single quoted' => ["background-image:url('https://x.test/y.jpg')", 'background-image:url(https://x.test/y.jpg)'],
            'double quoted' => ['background:url("http://x.test/y.jpg?a=1&b=2")', 'background:url(http://x.test/y.jpg?a=1&b=2)'],
            'upper case' => ['background:URL(HTTPS://X.TEST/Y.JPG)', 'background:url(HTTPS://X.TEST/Y.JPG)'],
            'shorthand' => ['background:#000 url(https://x.test/y.jpg) no-repeat center', 'background:#000 url(https://x.test/y.jpg) no-repeat center'],
            'with other declarations' => ['color:red; background:url(https://x.test/y.jpg); width:50%', 'color:red; background:url(https://x.test/y.jpg); width:50%'],
        ];
    }

    #[DataProvider('allowedBackgrounds')]
    public function test_a_single_http_background_url_is_kept(string $style, string $expected): void
    {
        foreach (['html', 'rich'] as $profile) {
            $output = RawHtmlSanitizer::sanitize('<p style="'.htmlspecialchars($style, ENT_QUOTES).'">x</p>', $profile);

            $this->assertSame($expected, $this->styleOf($output));
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refusedBackgrounds(): array
    {
        return [
            'javascript' => ['background:url(javascript:alert(1))'],
            'protocol relative' => ['background:url(//evil.test/x)'],
            'data uri' => ['background:url(data:image/png;base64,AAAA)'],
            'relative' => ['background:url(/x.png)'],
            'ftp' => ['background:url(ftp://x.test/y)'],
            'css escape' => ['background:u\\72l(https://x.test/y)'],
            'escaped scheme' => ['background:url(h\\74tps://x.test/y)'],
            'comment after url' => ['background:url/**/(https://x.test/y)'],
            'comment inside parens' => ['background:url(/**/https://x.test/y)'],
            'comment inside the address' => ['background:url(https://x.test/y/**/z)'],
            'space inside parens' => ['background:url( https://x.test/y )'],
            'newline inside parens' => ["background:url(\nhttps://x.test/y)"],
            'two urls' => ['background:url(https://x.test/a) url(https://x.test/b)'],
            'hidden second url' => ['background:url(https://x.test/a), url(javascript:alert(1))'],
            'merge tag in the address' => ['background:url(https://x.test/{{ id }}.png)'],
            'merge tag as the address' => ['background:url({{ bg }})'],
            'mixed quotes' => ['background:url(\'https://x.test/y")'],
            'other property list style' => ['list-style-image:url(https://x.test/y)'],
            'other property border image' => ['border-image:url(https://x.test/y)'],
            'other property content' => ['content:url(https://x.test/y)'],
            'image set' => ['background:image-set(url(https://x.test/y) 1x)'],
            'prefixed name' => ['background:foo-url(https://x.test/y)'],
            'null byte' => ["background:ur\0l(javascript:alert(1))"],
        ];
    }

    #[DataProvider('refusedBackgrounds')]
    public function test_every_other_url_is_removed_with_its_declaration(string $style): void
    {
        foreach (['html', 'rich'] as $profile) {
            $output = RawHtmlSanitizer::sanitize('<p style="'.htmlspecialchars($style, ENT_QUOTES).'">x</p>', $profile);

            $this->assertStringNotContainsString('url(', strtolower($output));
            $this->assertStringNotContainsString('javascript', $output);
            $this->assertStringNotContainsString('evil.test', $output);
            $this->assertStringNotContainsString('expression', $output);
        }
    }

    public function test_a_declaration_beside_an_allowed_url_is_still_checked(): void
    {
        $output = RawHtmlSanitizer::sanitize('<p style="background:url(https://x.test/y);width:expression(alert(1))">x</p>');

        $this->assertSame('background:url(https://x.test/y)', $this->styleOf($output));
    }

    public function test_a_shorthand_with_a_refused_url_loses_the_whole_declaration_only(): void
    {
        $output = RawHtmlSanitizer::sanitize('<p style="color:red; background:#000 url(javascript:alert(1)) no-repeat; width:10px">x</p>');

        $this->assertSame('<p style="color:red; width:10px">x</p>', $output);
    }

    public function test_a_merge_tag_beside_an_allowed_url_survives(): void
    {
        $output = RawHtmlSanitizer::sanitize("<p style=\"background:{{ brand | default('#fff') }} url(https://x.test/y.png)\">x</p>");

        $this->assertSame("<p style=\"background:{{ brand | default('#fff') }} url(https://x.test/y.png)\">x</p>", $output);
    }

    public function test_the_background_attribute_is_kept_on_table_cells_for_http_images_only(): void
    {
        $kept = RawHtmlSanitizer::sanitize('<table background="https://x.test/bg.png"><tr><td background="http://x.test/c.png">c</td></tr></table>');

        $this->assertStringContainsString('<table background="https://x.test/bg.png">', $kept);
        $this->assertStringContainsString('<td background="http://x.test/c.png">', $kept);

        foreach (['javascript:alert(1)', 'cid:logo', '/relative.png', '//evil.test/x.png', 'data:image/png;base64,AAAA', 'ftp://x.test/a.png', ' javascript:alert(1)', "java\tscript:alert(1)"] as $value) {
            $output = RawHtmlSanitizer::sanitize('<table background="'.$value.'"><tr><td background="'.$value.'">c</td></tr></table>');

            $this->assertStringNotContainsString('background', $output, $value);
        }
    }

    public function test_the_background_attribute_is_not_kept_where_tables_are_unwrapped(): void
    {
        $this->assertSame('c', RawHtmlSanitizer::sanitize('<table background="https://x.test/bg.png"><tr><td background="https://x.test/c.png">c</td></tr></table>', 'rich'));
    }

    public function test_the_background_attribute_is_not_kept_on_other_elements(): void
    {
        $this->assertSame('<p>x</p>', RawHtmlSanitizer::sanitize('<p background="https://x.test/bg.png">x</p>'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function inlineImages(): array
    {
        return [
            'png' => ['image/png'],
            'jpeg' => ['image/jpeg'],
            'jpg' => ['image/jpg'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
        ];
    }

    #[DataProvider('inlineImages')]
    public function test_inline_raster_images_survive_in_both_profiles(string $type): void
    {
        $src = 'data:'.$type.';base64,iVBORw0KGgoAAAANSUhEUg+/==';

        foreach (['html', 'rich'] as $profile) {
            $output = RawHtmlSanitizer::sanitize('<p><img src="'.$src.'" alt="x" width="10"></p>', $profile);

            $this->assertSame('<p><img src="'.$src.'" alt="x" width="10" /></p>', html_entity_decode($output, ENT_QUOTES | ENT_HTML5));
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refusedImages(): array
    {
        return [
            'svg' => ['data:image/svg+xml;base64,PHN2Zz48c2NyaXB0PmFsZXJ0KDEpPC9zY3JpcHQ+PC9zdmc+'],
            'html' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            'avif' => ['data:image/avif;base64,AAAA'],
            'not base64' => ['data:image/png,<script>alert(1)</script>'],
            'junk after the payload' => ['data:image/png;base64,AAAA"onerror="alert(1)'],
            'whitespace in the payload' => ["data:image/png;base64,AAAA\nBBBB"],
            'javascript' => ['javascript:alert(1)'],
            'empty payload' => ['data:image/png;base64,'],
        ];
    }

    #[DataProvider('refusedImages')]
    public function test_other_data_uris_are_still_dropped(string $src): void
    {
        foreach (['html', 'rich'] as $profile) {
            $output = RawHtmlSanitizer::sanitize('<img src="'.htmlspecialchars($src, ENT_QUOTES).'" alt="x">', $profile);

            $this->assertSame('<img alt="x" />', $output);
        }
    }

    public function test_data_uris_are_not_allowed_on_links(): void
    {
        $this->assertSame('<a>x</a>', RawHtmlSanitizer::sanitize('<a href="data:image/png;base64,AAAA">x</a>'));
    }

    public function test_the_kept_images_survive_the_whole_compile(): void
    {
        $html = '<table background="https://x.test/bg.png"><tr><td background="https://x.test/c.png" style="background:#000 url(https://x.test/y.jpg) no-repeat">c<img src="data:image/png;base64,iVBORw0KGgo=" alt="i"></td></tr></table>';

        $output = app(EmailSlotCompiler::class)->compileSlots([['type' => 'html', 'data' => ['html' => $html]]]);

        $this->assertStringContainsString('<table background="https://x.test/bg.png"', $output);
        $this->assertStringContainsString('background="https://x.test/c.png"', $output);
        $this->assertStringContainsString('url(https://x.test/y.jpg)', html_entity_decode($output, ENT_QUOTES | ENT_HTML5));
        $this->assertStringContainsString('data:image/png;base64,iVBORw0KGgo', html_entity_decode($output, ENT_QUOTES | ENT_HTML5));
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Support\RawHtmlSanitizer;
use Odden\MailBuilder\Support\SafeUrl;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SafeUrlSchemesTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function defaultSchemeUrls(): array
    {
        return [
            'http' => ['http://example.test/a'],
            'https' => ['https://example.test/a'],
            'mailto' => ['mailto:hello@example.test'],
            'tel' => ['tel:+15551234567'],
            'sms' => ['sms:+15551234567?body=hi'],
            'webcal' => ['webcal://example.test/cal.ics'],
            'webcals' => ['webcals://example.test/cal.ics'],
            'whatsapp' => ['whatsapp://send?text=hello'],
            'skype' => ['skype:echo123?call'],
            'geo' => ['geo:37.786971,-122.399677'],
            'itms' => ['itms://apps.apple.com/app/id1'],
            'itms-apps' => ['itms-apps://apps.apple.com/app/id1'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function hostPortUrls(): array
    {
        return [
            'localhost with port' => ['localhost:8000/x'],
            'localhost with bare port' => ['localhost:8000'],
            'ip with port' => ['127.0.0.1:8000/x?a=1'],
            'host with port' => ['example.com:8080/x'],
        ];
    }

    #[DataProvider('defaultSchemeUrls')]
    #[DataProvider('hostPortUrls')]
    public function test_the_default_schemes_and_host_port_forms_pass(string $url): void
    {
        $this->assertSame($url, SafeUrl::for($url));
    }

    #[DataProvider('defaultSchemeUrls')]
    public function test_the_default_schemes_survive_the_raw_html_sanitizer(string $url): void
    {
        $html = RawHtmlSanitizer::sanitize('<a href="'.$url.'">x</a>');

        $this->assertSame($url, html_entity_decode((string) preg_replace('/^<a href="([^"]*)">x<\/a>$/', '$1', $html)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refusedUrls(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'javascript that looks like a port' => ['javascript:1/alert(1)'],
            'vbscript' => ['vbscript:msgbox(1)'],
            'data html' => ['data:text/html,<script>alert(1)</script>'],
            'file' => ['file:///etc/passwd'],
            'unlisted scheme' => ['ftp://example.test/a'],
            'localhost lookalike' => ['localhostx:8000/x'],
        ];
    }

    #[DataProvider('refusedUrls')]
    public function test_other_schemes_are_refused(string $url): void
    {
        $this->assertSame('#', SafeUrl::for($url));
    }

    public function test_the_allowed_schemes_come_from_the_app_config(): void
    {
        config(['mail-builder.url_schemes' => ['HTTPS', ' myapp ']]);

        $this->assertSame(['https', 'myapp'], SafeUrl::linkSchemes());
        $this->assertSame('myapp://open/1', SafeUrl::for('myapp://open/1'));
        $this->assertSame('https://example.test', SafeUrl::for('https://example.test'));
        $this->assertSame('#', SafeUrl::for('http://example.test'));
        $this->assertSame('#', SafeUrl::for('mailto:a@b.co'));
        $this->assertSame('#', SafeUrl::for('whatsapp://send?text=hi'));
    }

    public function test_the_app_config_also_decides_the_links_the_html_sanitizer_keeps(): void
    {
        config(['mail-builder.url_schemes' => ['https', 'myapp']]);

        $html = RawHtmlSanitizer::sanitize('<a href="myapp://open/1">a</a><a href="mailto:a@b.co">b</a>');

        $this->assertSame('<a href="myapp://open/1">a</a><a>b</a>', $html);
    }

    public function test_image_urls_ignore_the_link_scheme_config(): void
    {
        config(['mail-builder.url_schemes' => ['myapp']]);

        $this->assertSame('https://example.test/a.png', SafeUrl::for('https://example.test/a.png', true));
        $this->assertSame('', SafeUrl::for('myapp://x', true));
    }

    public function test_a_malformed_config_value_falls_back_to_the_defaults(): void
    {
        config(['mail-builder.url_schemes' => 'https']);

        $this->assertContains('whatsapp', SafeUrl::linkSchemes());
    }
}

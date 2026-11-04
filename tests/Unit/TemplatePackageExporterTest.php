<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Exporters\TemplatePackageExporter;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Tests\TestCase;
use ZipArchive;

class TemplatePackageExporterTest extends TestCase
{
    public function test_can_export_slots_to_standard_mjml(): void
    {
        $slots = [
            [
                'type' => 'header',
                'data' => ['brand_name' => 'Focal Cloud'],
            ],
            [
                'type' => 'hero',
                'data' => [
                    'title' => 'Product Updates Live',
                    'subtitle' => 'Explore the new version',
                    'button_text' => 'View Changelog',
                    'button_url' => 'https://focal.test/changelog',
                ],
            ],
            [
                'type' => 'body_text',
                'data' => ['content' => 'Hello from marketing operations.'],
            ],
            [
                'type' => 'button',
                'data' => ['text' => 'Click Here', 'url' => 'https://focal.test/click'],
            ],
        ];

        $mjml = MailBuilder::exporter()->exportMjml($slots, ['primary_color' => '#10B981'], 'Release Briefing');

        $this->assertStringContainsString('<mjml>', $mjml);
        $this->assertStringContainsString('</mjml>', $mjml);
        $this->assertStringContainsString('<mj-title>Release Briefing</mj-title>', $mjml);
        $this->assertStringContainsString('Focal Cloud', $mjml);
        $this->assertStringContainsString('Product Updates Live', $mjml);
        $this->assertStringContainsString('#10B981', $mjml);
    }

    public function test_can_create_standalone_zip_package(): void
    {
        $exporter = new TemplatePackageExporter;

        $html = '<!DOCTYPE html><html><body><h1>Launch</h1></body></html>';
        $plainText = 'Launch text';
        $metadata = ['name' => 'Launch Email', 'category' => 'announcement'];

        $zipBytes = $exporter->createZipPackage('Launch Email', $html, $plainText, $metadata);

        $this->assertNotEmpty($zipBytes);

        // Verify it can be read by ZipArchive
        $tempFile = (string) tempnam(sys_get_temp_dir(), 'test_zip_');
        file_put_contents($tempFile, $zipBytes);

        $zip = new ZipArchive;
        $res = $zip->open($tempFile);
        $this->assertTrue($res === true);

        $this->assertSame($html, $zip->getFromName('index.html'));
        $this->assertSame($plainText, $zip->getFromName('plain_text.txt'));
        $this->assertStringContainsString('Launch Email', (string) $zip->getFromName('metadata.json'));
        $readmeName = $zip->getNameIndex(3);
        $this->assertIsString($readmeName);
        $this->assertStringContainsString('README.md', $readmeName);

        $zip->close();
        @unlink($tempFile);
    }

    public function test_mjml_urls_use_the_safe_url_rules(): void
    {
        $mjml = MailBuilder::exporter()->exportMjml([
            ['type' => 'hero', 'data' => ['title' => 'T', 'button_url' => 'javascript:alert(1)']],
            ['type' => 'button', 'data' => ['text' => 'Go', 'url' => ' JaVaScRiPt:alert(2)']],
            ['type' => 'button', 'data' => ['text' => 'Ok', 'url' => 'https://focal.test/a?b=1&c=2']],
            ['type' => 'button', 'data' => ['text' => 'Tag', 'url' => '{{ unsubscribe_url }}']],
        ]);

        $this->assertStringNotContainsString('javascript:', strtolower($mjml));
        $this->assertSame(2, substr_count($mjml, 'href="#"'));
        $this->assertStringContainsString('href="https://focal.test/a?b=1&amp;c=2"', $mjml);
        $this->assertStringContainsString('href="{{ unsubscribe_url }}"', $mjml);
    }

    public function test_mjml_theme_colours_must_be_colours(): void
    {
        $mjml = MailBuilder::exporter()->exportMjml(
            [['type' => 'button', 'data' => ['text' => 'Go', 'url' => 'https://focal.test']]],
            ['primary_color' => '#ff0000" onload="alert(1)', 'background_color' => 'red;background:url(https://evil.test/x)', 'content_background_color' => '#ABCDEF'],
        );

        $this->assertStringNotContainsString('onload', $mjml);
        $this->assertStringNotContainsString('evil.test', $mjml);
        $this->assertStringContainsString('background-color="#2563EB"', $mjml);
        $this->assertStringContainsString('background-color="#F8FAFC"', $mjml);
        $this->assertStringContainsString('background-color="#ABCDEF"', $mjml);
    }

    public function test_mjml_escapes_the_font_family_subject_and_slot_type(): void
    {
        $mjml = MailBuilder::exporter()->exportMjml(
            [['type' => 'x --><script>alert(1)</script><!--', 'data' => []]],
            ['font_family' => 'Arial" onload="alert(1)'],
            'Hi</mj-title><mj-raw><script>alert(1)</script>',
        );

        $this->assertStringNotContainsString('<script>', $mjml);
        $this->assertStringNotContainsString('" onload="', $mjml);
        $this->assertStringContainsString('<mj-title>Hi&lt;/mj-title&gt;', $mjml);
        $this->assertStringContainsString('<!-- Slot: x-scriptalert1script- -->', $mjml);
    }

    public function test_mjml_body_text_goes_through_the_html_sanitizer(): void
    {
        $mjml = MailBuilder::exporter()->exportMjml([
            ['type' => 'body_text', 'data' => ['content' => '<p x\'> y=\'<a href=javascript:alert(1) \'>click</a><script>alert(1)</script><img src=x onerror=alert(1)> Hi {{ contact.first_name }}']],
            ['type' => 'body_text', 'data' => ['content' => ['<script>alert(2)</script>']]],
        ]);

        $this->assertStringNotContainsString('javascript:', $mjml);
        $this->assertStringNotContainsString('<script', $mjml);
        $this->assertStringNotContainsString('onerror', $mjml);
        $this->assertStringContainsString('click</a>', $mjml);
        $this->assertStringContainsString('Hi {{ contact.first_name }}', $mjml);
    }

    public function test_the_package_html_is_scrubbed_of_script_urls(): void
    {
        $zipBytes = (new TemplatePackageExporter)->createZipPackage('Launch', '<a href="javascript:alert(1)">x</a><a href="https://focal.test">y</a>', 'text');

        $tempFile = (string) tempnam(sys_get_temp_dir(), 'test_zip_');
        file_put_contents($tempFile, $zipBytes);
        $zip = new ZipArchive;
        $zip->open($tempFile);

        $this->assertSame('<a href="#">x</a><a href="https://focal.test">y</a>', $zip->getFromName('index.html'));

        $zip->close();
        @unlink($tempFile);
    }
}

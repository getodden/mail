<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Unit;

use DoPHP\MailBuilder\Exporters\TemplatePackageExporter;
use DoPHP\MailBuilder\MailBuilder;
use DoPHP\MailBuilder\Tests\TestCase;
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
}

<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Exporters;

use ZipArchive;

class TemplatePackageExporter
{
    /**
     * Generate standard MJML markup from slot array or HTML string.
     *
     * @param  list<array<string, mixed>>  $slots
     * @param  array<string, mixed>  $theme
     */
    public function exportMjml(array $slots, array $theme = [], string $subject = 'Email'): string
    {
        $primaryColor = $theme['primary_color'] ?? '#2563EB';
        $bgColor = $theme['background_color'] ?? '#F8FAFC';
        $contentBg = $theme['content_background_color'] ?? '#FFFFFF';
        $fontFamily = $theme['font_family'] ?? '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif';

        $sections = [];

        foreach ($slots as $slot) {
            $type = (string) ($slot['type'] ?? 'body_text');
            $data = is_array($slot['data'] ?? null) ? $slot['data'] : [];

            switch ($type) {
                case 'header':
                    $brand = htmlspecialchars((string) ($data['brand_name'] ?? 'Brand'));
                    $sections[] = <<<MJML
    <mj-section background-color="{$contentBg}" padding="16px 24px">
      <mj-column>
        <mj-text font-size="18px" font-weight="700" color="{$primaryColor}">{$brand}</mj-text>
      </mj-column>
    </mj-section>
MJML;
                    break;

                case 'hero':
                    $title = htmlspecialchars((string) ($data['title'] ?? 'Headline'));
                    $subtitle = htmlspecialchars((string) ($data['subtitle'] ?? ''));
                    $btnText = htmlspecialchars((string) ($data['button_text'] ?? 'Get Started'));
                    $btnUrl = htmlspecialchars((string) ($data['button_url'] ?? '#'));
                    $sections[] = <<<MJML
    <mj-section background-color="{$contentBg}" padding="32px 24px" text-align="center">
      <mj-column>
        <mj-text font-size="26px" font-weight="800" line-height="1.3" align="center">{$title}</mj-text>
        <mj-text font-size="15px" color="#64748B" line-height="1.5" align="center">{$subtitle}</mj-text>
        <mj-button background-color="{$primaryColor}" href="{$btnUrl}" border-radius="6px">{$btnText}</mj-button>
      </mj-column>
    </mj-section>
MJML;
                    break;

                case 'body_text':
                    $content = (string) ($data['content'] ?? '');
                    $sections[] = <<<MJML
    <mj-section background-color="{$contentBg}" padding="16px 24px">
      <mj-column>
        <mj-text font-size="15px" line-height="1.6" color="#334155">{$content}</mj-text>
      </mj-column>
    </mj-section>
MJML;
                    break;

                case 'button':
                    $btnText = htmlspecialchars((string) ($data['text'] ?? 'Click Here'));
                    $btnUrl = htmlspecialchars((string) ($data['url'] ?? '#'));
                    $sections[] = <<<MJML
    <mj-section background-color="{$contentBg}" padding="16px 24px">
      <mj-column>
        <mj-button background-color="{$primaryColor}" href="{$btnUrl}" border-radius="6px">{$btnText}</mj-button>
      </mj-column>
    </mj-section>
MJML;
                    break;

                default:
                    $sections[] = <<<MJML
    <mj-section background-color="{$contentBg}" padding="16px 24px">
      <mj-column>
        <mj-text font-size="14px" color="#64748B"><!-- Slot: {$type} --></mj-text>
      </mj-column>
    </mj-section>
MJML;
                    break;
            }
        }

        $bodySections = implode("\n", $sections);

        return <<<MJML
<mjml>
  <mj-head>
    <mj-title>{$subject}</mj-title>
    <mj-attributes>
      <mj-all font-family="{$fontFamily}" />
      <mj-text padding="0" />
    </mj-attributes>
  </mj-head>
  <mj-body background-color="{$bgColor}">
{$bodySections}
  </mj-body>
</mjml>
MJML;
    }

    /**
     * Create a self-contained ZIP archive string containing HTML, text, and metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function createZipPackage(
        string $templateName,
        string $html,
        string $plainText,
        array $metadata = []
    ): string {
        $tempFile = (string) tempnam(sys_get_temp_dir(), 'mail_builder_template_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('index.html', $html);
        $zip->addFromString('plain_text.txt', $plainText);
        $zip->addFromString('metadata.json', (string) json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $readme = <<<README
# {$templateName} - Email Export Package

This standalone package was compiled and exported with doPHP Laravel Mail Builder.

## Contents
- `index.html`: Fully inlined, Outlook VML-compatible, responsive HTML email.
- `plain_text.txt`: Accessible plain-text fallback string with link references.
- `metadata.json`: Original configuration, slot schema, and typography theme tokens.

## Sending Instructions
Upload `index.html` directly into your ESP (SendGrid, Mailgun, Amazon SES, Postmark, or custom SMTP).
README;

        $zip->addFromString('README.md', $readme);
        $zip->close();

        $content = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }
}

<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Compilers;

use DoPHP\MailBuilder\Data\EmailDocument;
use DoPHP\MailBuilder\Data\EmailSlot;
use DoPHP\MailBuilder\Enums\SlotType;

class AmpEmailCompiler
{
    /**
     * Compile an EmailDocument or slots array into valid AMP for Email (⚡4email) markup.
     *
     * @param  list<array<string, mixed>>|EmailDocument  $documentOrSlots
     * @param  array<string, mixed>  $options
     */
    public function compile(EmailDocument|array $documentOrSlots, array $options = []): string
    {
        $document = $documentOrSlots instanceof EmailDocument
            ? $documentOrSlots
            : EmailDocument::fromArray(['slots' => $documentOrSlots]);

        $theme = array_merge($document->theme, $options['theme'] ?? []);
        $primaryColor = (string) ($theme['primary_color'] ?? '#2563EB');
        $bgColor = (string) ($theme['background_color'] ?? '#F8FAFC');
        $contentBg = (string) ($theme['content_background_color'] ?? '#FFFFFF');

        $bodyParts = [];
        $requiresAccordion = false;

        foreach ($document->slots as $slot) {
            if ($slot->type === SlotType::Accordion) {
                $requiresAccordion = true;
                $bodyParts[] = $this->compileAmpAccordion($slot, $primaryColor);
            } elseif ($slot->type === SlotType::Hero) {
                $bodyParts[] = $this->compileAmpHero($slot, $primaryColor);
            } elseif ($slot->type === SlotType::Button) {
                $bodyParts[] = $this->compileAmpButton($slot, $primaryColor);
            } else {
                // Standard block fallback
                $bodyParts[] = $this->compileAmpGeneralSlot($slot);
            }
        }

        $accordionScript = $requiresAccordion
            ? '<script async custom-element="amp-accordion" src="https://cdn.ampproject.org/v0/amp-accordion-0.1.js"></script>'
            : '';

        $bodyContent = implode("\n", $bodyParts);

        return <<<HTML
<!doctype html>
<html ⚡4email data-css-strict>
<head>
  <meta charset="utf-8">
  <script async src="https://cdn.ampproject.org/v0.js"></script>
  {$accordionScript}
  <style amp-boilerplate>body{visibility:hidden}</style>
  <style amp-custom>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: {$bgColor}; margin: 0; padding: 20px; color: #1E293B; }
    .amp-container { max-width: 600px; margin: 0 auto; background-color: {$contentBg}; border-radius: 8px; padding: 24px; }
    .amp-btn { display: inline-block; background-color: {$primaryColor}; color: #FFFFFF; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 14px; }
    .amp-card { border: 1px solid #E2E8F0; border-radius: 6px; padding: 16px; margin-bottom: 12px; }
    section[expanded] header { font-weight: 700; color: {$primaryColor}; }
  </style>
</head>
<body>
  <div class="amp-container">
    {$bodyContent}
  </div>
</body>
</html>
HTML;
    }

    protected function compileAmpHero(EmailSlot $slot, string $primaryColor): string
    {
        $title = htmlspecialchars((string) $slot->get('title', 'Hero Title'));
        $subtitle = htmlspecialchars((string) $slot->get('subtitle', ''));
        $btnText = htmlspecialchars((string) $slot->get('button_text', 'Action'));
        $btnUrl = htmlspecialchars((string) $slot->get('button_url', '#'));

        return <<<HTML
<div style="text-align: center; padding: 24px 0;">
  <h1 style="font-size: 26px; font-weight: 800; margin: 0 0 10px 0;">{$title}</h1>
  <p style="font-size: 15px; color: #64748B; margin: 0 0 20px 0;">{$subtitle}</p>
  <a href="{$btnUrl}" class="amp-btn">{$btnText}</a>
</div>
HTML;
    }

    protected function compileAmpButton(EmailSlot $slot, string $primaryColor): string
    {
        $text = htmlspecialchars((string) $slot->get('text', 'Click Here'));
        $url = htmlspecialchars((string) $slot->get('url', '#'));

        return <<<HTML
<div style="text-align: center; margin: 16px 0;">
  <a href="{$url}" class="amp-btn">{$text}</a>
</div>
HTML;
    }

    protected function compileAmpAccordion(EmailSlot $slot, string $primaryColor): string
    {
        $heading = htmlspecialchars((string) $slot->get('heading', 'Frequently Asked Questions'));
        /** @var list<array{question?: string, answer?: string}> $items */
        $items = $slot->get('items', []);

        $sections = [];
        foreach ($items as $item) {
            $q = htmlspecialchars((string) ($item['question'] ?? 'Question'));
            $a = htmlspecialchars((string) ($item['answer'] ?? ''));

            $sections[] = <<<HTML
    <section>
      <header style="padding: 12px 16px; background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">{$q}</header>
      <div style="padding: 12px 16px; font-size: 13px; line-height: 1.5; color: #475569;">{$a}</div>
    </section>
HTML;
        }

        $sectionsHtml = implode("\n", $sections);

        return <<<HTML
<div style="margin: 20px 0;">
  <h3 style="font-size: 18px; margin-bottom: 12px;">{$heading}</h3>
  <amp-accordion animate>
{$sectionsHtml}
  </amp-accordion>
</div>
HTML;
    }

    protected function compileAmpGeneralSlot(EmailSlot $slot): string
    {
        $content = (string) $slot->get('content', '');
        if ($content !== '') {
            return "<div class=\"amp-card\">{$content}</div>";
        }

        return '';
    }
}

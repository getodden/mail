<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Parsers;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;

class HtmlToSlotsParser
{
    /**
     * Parse raw HTML string into an EmailDocument with modular slots.
     */
    public static function parse(string $html, ?string $subject = null): EmailDocument
    {
        $slots = [];

        if (trim($html) === '') {
            return new EmailDocument(subject: $subject, slots: []);
        }

        // Suppress libxml HTML warnings
        $previousEntityLoader = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousEntityLoader);

        $xpath = new DOMXPath($dom);

        // Find main body content or document root
        $bodyNodes = $xpath->query('//body');
        $candidateNode = ($bodyNodes && $bodyNodes->length > 0) ? $bodyNodes->item(0) : $dom->documentElement;

        if (! $candidateNode instanceof DOMElement) {
            return new EmailDocument(
                subject: $subject,
                slots: [
                    new EmailSlot(SlotType::Html, ['html' => $html]),
                ]
            );
        }

        $rootNode = $candidateNode;

        /** @var DOMNode $child */
        foreach ($rootNode->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->nodeValue ?? '');
                if ($text !== '') {
                    $slots[] = new EmailSlot(SlotType::BodyText, [
                        'content' => '<p>'.htmlspecialchars($text).'</p>',
                    ]);
                }

                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            /** @var DOMElement $element */
            $element = $child;
            $tagName = strtolower($element->tagName);

            switch ($tagName) {
                case 'h1':
                    $slots[] = new EmailSlot(SlotType::Hero, [
                        'title' => trim($element->textContent),
                        'subtitle' => '',
                    ]);
                    break;

                case 'h2':
                case 'h3':
                case 'h4':
                case 'p':
                    $innerHTML = self::getInnerHTML($element);
                    if (trim($innerHTML) !== '') {
                        $slots[] = new EmailSlot(SlotType::BodyText, [
                            'content' => "<{$tagName}>{$innerHTML}</{$tagName}>",
                        ]);
                    }
                    break;

                case 'a':
                    $btnText = trim($element->textContent);
                    $btnUrl = $element->getAttribute('href');
                    $slots[] = new EmailSlot(SlotType::Button, [
                        'text' => $btnText !== '' ? $btnText : 'Click Here',
                        'url' => $btnUrl !== '' ? $btnUrl : '#',
                    ]);
                    break;

                case 'img':
                    $src = $element->getAttribute('src');
                    $alt = $element->getAttribute('alt');
                    if ($src !== '') {
                        $slots[] = new EmailSlot(SlotType::ImageBanner, [
                            'image_url' => $src,
                            'alt_text' => $alt !== '' ? $alt : 'Image',
                        ]);
                    }
                    break;

                case 'hr':
                    $slots[] = new EmailSlot(SlotType::Divider, [
                        'height' => 24,
                        'show_line' => true,
                    ]);
                    break;

                case 'table':
                    $tableSlot = self::parseTableElement($element);
                    $slots[] = $tableSlot;
                    break;

                default:
                    $outerHtml = $dom->saveHTML($element);
                    if ($outerHtml !== false && trim($outerHtml) !== '') {
                        $slots[] = new EmailSlot(SlotType::Html, [
                            'html' => trim($outerHtml),
                        ]);
                    }
                    break;
            }
        }

        if (empty($slots)) {
            $slots[] = new EmailSlot(SlotType::Html, ['html' => $html]);
        }

        return new EmailDocument(
            subject: $subject,
            slots: $slots
        );
    }

    /**
     * Inspect a table element and parse into structured DataTable slot or Html fallback.
     */
    protected static function parseTableElement(DOMElement $table): EmailSlot
    {
        $headers = [];
        $rows = [];

        // Check for <th> tags
        $thNodes = $table->getElementsByTagName('th');
        if ($thNodes->length > 0) {
            foreach ($thNodes as $th) {
                $headers[] = trim($th->textContent);
            }
        }

        $trNodes = $table->getElementsByTagName('tr');
        foreach ($trNodes as $tr) {
            $tdNodes = $tr->getElementsByTagName('td');
            if ($tdNodes->length > 0) {
                $cells = [];
                foreach ($tdNodes as $td) {
                    $cells[] = trim($td->textContent);
                }
                $rows[] = ['cells' => $cells];
            }
        }

        if (! empty($headers) && ! empty($rows)) {
            return new EmailSlot(SlotType::DataTable, [
                'headers' => $headers,
                'rows' => $rows,
            ]);
        }

        $dom = $table->ownerDocument;
        $rawTable = $dom !== null ? $dom->saveHTML($table) : '';

        return new EmailSlot(SlotType::Html, [
            'html' => $rawTable !== false ? trim($rawTable) : '',
        ]);
    }

    /**
     * Helper to retrieve inner HTML of a DOMElement.
     */
    protected static function getInnerHTML(DOMElement $element): string
    {
        $innerHTML = '';
        $doc = $element->ownerDocument;
        if ($doc === null) {
            return $element->textContent;
        }

        foreach ($element->childNodes as $child) {
            $chunk = $doc->saveHTML($child);
            if ($chunk !== false) {
                $innerHTML .= $chunk;
            }
        }

        return trim($innerHTML);
    }
}

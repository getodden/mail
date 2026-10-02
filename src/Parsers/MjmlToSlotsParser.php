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

class MjmlToSlotsParser
{
    /**
     * Parse standard MJML XML markup into an EmailDocument with modular slots.
     */
    public static function parse(string $mjml, ?string $subject = null): EmailDocument
    {
        $slots = [];

        if (trim($mjml) === '') {
            return new EmailDocument(subject: $subject, slots: []);
        }

        $prev = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($mjml, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new DOMXPath($dom);

        // Check if subject is specified in <mj-title>
        if ($subject === null) {
            $titleNodes = $xpath->query('//mj-title');
            if ($titleNodes && $titleNodes->length > 0) {
                $sub = self::getNodeText($titleNodes->item(0));
                if ($sub !== '') {
                    $subject = $sub;
                }
            }
        }

        // Preview text from <mj-preview>
        $previewText = null;
        $previewNodes = $xpath->query('//mj-preview');
        if ($previewNodes && $previewNodes->length > 0) {
            $prevText = self::getNodeText($previewNodes->item(0));
            if ($prevText !== '') {
                $previewText = $prevText;
            }
        }

        // Iterate over sections in <mj-body>
        $sections = $xpath->query('//mj-body/mj-section');
        if (! $sections || $sections->length === 0) {
            // Check top-level elements if no mj-body
            $sections = $xpath->query('//mj-section');
        }

        if ($sections) {
            foreach ($sections as $section) {
                if (! $section instanceof DOMElement) {
                    continue;
                }

                $columns = $xpath->query('./mj-column', $section);
                if (! $columns instanceof \DOMNodeList) {
                    continue;
                }

                $columnCount = $columns->length;

                if ($columnCount === 2) {
                    $slots[] = self::parseTwoColumnSection($columns, $xpath);
                } elseif ($columnCount === 3) {
                    $slots[] = self::parseThreeColumnSection($columns, $xpath);
                } elseif ($columnCount > 0) {
                    // Single or multi column: parse inner components
                    foreach ($columns as $column) {
                        if (! $column instanceof DOMElement) {
                            continue;
                        }
                        self::parseColumnChildren($column, $slots);
                    }
                }
            }
        }

        // Also parse any <mj-hero> outside or inside sections
        $heroes = $xpath->query('//mj-hero');
        if ($heroes) {
            foreach ($heroes as $hero) {
                if ($hero instanceof DOMElement) {
                    $titleNode = $xpath->query('.//mj-text', $hero);
                    $btnNode = $xpath->query('.//mj-button', $hero);

                    $titleText = ($titleNode && $titleNode->length > 0) ? self::getNodeText($titleNode->item(0)) : '';
                    $title = $titleText !== '' ? $titleText : 'Hero Title';

                    $btnTextStr = ($btnNode && $btnNode->length > 0) ? self::getNodeText($btnNode->item(0)) : '';
                    $btnText = $btnTextStr !== '' ? $btnTextStr : null;

                    $firstBtn = ($btnNode && $btnNode->length > 0) ? $btnNode->item(0) : null;
                    $btnUrl = ($firstBtn instanceof DOMElement) ? $firstBtn->getAttribute('href') : '#';

                    $slots[] = new EmailSlot(SlotType::Hero, [
                        'title' => $title,
                        'button_text' => $btnText,
                        'button_url' => $btnUrl !== '' ? $btnUrl : '#',
                    ]);
                }
            }
        }

        return new EmailDocument(
            subject: $subject,
            previewText: $previewText,
            slots: $slots
        );
    }

    /**
     * Parse child elements inside a single mj-column.
     *
     * @param  list<EmailSlot>  $slots
     */
    protected static function parseColumnChildren(DOMElement $column, array &$slots): void
    {
        /** @var DOMNode $child */
        foreach ($column->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            switch ($tag) {
                case 'mj-text':
                    $slots[] = new EmailSlot(SlotType::BodyText, [
                        'content' => trim($child->textContent),
                    ]);
                    break;

                case 'mj-button':
                    $slots[] = new EmailSlot(SlotType::Button, [
                        'text' => trim($child->textContent),
                        'url' => $child->getAttribute('href') ?: '#',
                        'style' => 'primary',
                    ]);
                    break;

                case 'mj-image':
                    $slots[] = new EmailSlot(SlotType::ImageBanner, [
                        'image_url' => $child->getAttribute('src'),
                        'alt_text' => $child->getAttribute('alt') ?: 'Image',
                        'link_url' => $child->getAttribute('href') ?: null,
                    ]);
                    break;

                case 'mj-divider':
                    $slots[] = new EmailSlot(SlotType::Divider, [
                        'height' => (int) ($child->getAttribute('border-width') ?: 24),
                        'show_line' => true,
                    ]);
                    break;

                case 'mj-table':
                    $slots[] = new EmailSlot(SlotType::Html, [
                        'html' => trim($child->textContent),
                    ]);
                    break;

                case 'mj-social':
                    $socialLinks = [];
                    foreach ($child->getElementsByTagName('mj-social-element') as $element) {
                        $socialLinks[] = [
                            'network' => ucfirst($element->getAttribute('name') ?: 'Website'),
                            'url' => $element->getAttribute('href') ?: '#',
                        ];
                    }
                    $slots[] = new EmailSlot(SlotType::SocialLinks, [
                        'links' => $socialLinks,
                    ]);
                    break;
            }
        }
    }

    /**
     * Safely extract trimmed text from a DOM element.
     */
    protected static function getNodeText(mixed $node): string
    {
        return $node instanceof DOMElement ? trim($node->textContent) : '';
    }

    /**
     * Parse two-column layout into a native TwoColumn slot.
     *
     * @param  \DOMNodeList<DOMNode|\DOMNameSpaceNode>  $columns
     */
    protected static function parseTwoColumnSection(\DOMNodeList $columns, DOMXPath $xpath): EmailSlot
    {
        $col1 = $columns->item(0);
        $col2 = $columns->item(1);

        $leftTitle = '';
        $leftBody = '';
        $rightTitle = '';
        $rightBody = '';

        if ($col1 instanceof DOMElement) {
            $texts = $xpath->query('.//mj-text', $col1);
            if ($texts && $texts->length > 0) {
                $leftTitle = self::getNodeText($texts->item(0));
            }
            if ($texts && $texts->length > 1) {
                $leftBody = self::getNodeText($texts->item(1));
            }
        }

        if ($col2 instanceof DOMElement) {
            $texts = $xpath->query('.//mj-text', $col2);
            if ($texts && $texts->length > 0) {
                $rightTitle = self::getNodeText($texts->item(0));
            }
            if ($texts && $texts->length > 1) {
                $rightBody = self::getNodeText($texts->item(1));
            }
        }

        return new EmailSlot(SlotType::TwoColumn, [
            'left_title' => $leftTitle !== '' ? $leftTitle : 'Column 1',
            'left_body' => $leftBody !== '' ? $leftBody : '',
            'right_title' => $rightTitle !== '' ? $rightTitle : 'Column 2',
            'right_body' => $rightBody !== '' ? $rightBody : '',
        ]);
    }

    /**
     * Parse three-column layout into a native ThreeColumn slot.
     *
     * @param  \DOMNodeList<DOMNode|\DOMNameSpaceNode>  $columns
     */
    protected static function parseThreeColumnSection(\DOMNodeList $columns, DOMXPath $xpath): EmailSlot
    {
        $colData = [];
        foreach ($columns as $col) {
            if ($col instanceof DOMElement) {
                $texts = $xpath->query('.//mj-text', $col);
                $titleText = ($texts && $texts->length > 0) ? self::getNodeText($texts->item(0)) : '';
                $title = $titleText !== '' ? $titleText : 'Feature';
                $desc = ($texts && $texts->length > 1) ? self::getNodeText($texts->item(1)) : '';
                $imgs = $xpath->query('.//mj-image', $col);
                $firstImg = ($imgs && $imgs->length > 0) ? $imgs->item(0) : null;
                $imgUrl = ($firstImg instanceof DOMElement) ? $firstImg->getAttribute('src') : null;

                $colData[] = [
                    'title' => $title,
                    'description' => $desc,
                    'image_url' => $imgUrl,
                ];
            }
        }

        return new EmailSlot(SlotType::ThreeColumn, [
            'columns' => $colData,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Tests\TestCase;

class PlainTextExtractorTest extends TestCase
{
    public function test_extracts_plain_text_from_document(): void
    {
        $doc = new EmailDocument(
            subject: 'Announcement',
            previewText: 'Sneak preview snippet',
        );

        $doc->append(SlotType::Header, ['brand_name' => 'Focal HQ']);
        $doc->append(SlotType::Hero, [
            'title' => 'Big Launch',
            'subtitle' => 'Our new release is live.',
            'button_text' => 'Read More',
            'button_url' => 'https://focal.test/launch',
        ]);
        $doc->append(SlotType::Footer, [
            'company_name' => 'Focal Inc.',
            'address' => 'San Francisco, CA',
            'unsubscribe_url' => 'https://focal.test/unsubscribe',
        ]);

        $text = MailBuilder::plainText($doc);

        $this->assertStringContainsString('Sneak preview snippet', $text);
        $this->assertStringContainsString('Focal HQ', $text);
        $this->assertStringContainsString('Big Launch', $text);
        $this->assertStringContainsString('Our new release is live.', $text);
        $this->assertStringContainsString('>> Read More: https://focal.test/launch', $text);
        $this->assertStringContainsString('Focal Inc.', $text);
        $this->assertStringContainsString('Unsubscribe: https://focal.test/unsubscribe', $text);
    }

    public function test_extracts_clean_plain_text_from_raw_html(): void
    {
        $extractor = app(PlainTextExtractor::class);

        $rawHtml = <<<'HTML'
            <h2>Monthly Briefing</h2>
            <p>Hello <strong>Alex</strong>, check out <a href="https://focal.test/docs">our documentation</a> for updates.</p>
            <br>
            <ul>
                <li>Point 1</li>
                <li>Point 2</li>
            </ul>
HTML;

        $text = $extractor->extractFromHtml($rawHtml);

        $this->assertStringContainsString('Monthly Briefing', $text);
        $this->assertStringContainsString('our documentation (https://focal.test/docs)', $text);
        $this->assertStringContainsString('* Point 1', $text);
        $this->assertStringContainsString('* Point 2', $text);
        $this->assertStringNotContainsString('<p>', $text);
        $this->assertStringNotContainsString('<strong>', $text);
    }

    public function test_extracts_plain_text_for_dynamic_feed_and_order_receipt_and_rss(): void
    {
        $doc = new EmailDocument;
        $doc->append(SlotType::DynamicFeed, [
            'heading' => 'Curated Picks',
            'items' => [
                [
                    'title' => 'Focal Enterprise',
                    'price' => '$499',
                    'description' => 'Advanced marketing platform',
                    'button_url' => 'https://focal.test/enterprise',
                ],
            ],
        ]);
        $doc->append(SlotType::OrderReceipt, [
            'order_number' => 'FC-8821',
            'status' => 'Paid',
            'currency' => '$',
            'items' => [
                ['name' => 'License Seat', 'quantity' => 2, 'price' => '250.00'],
            ],
            'total' => '500.00',
        ]);
        $doc->append(SlotType::RssFeed, [
            'heading' => 'Developer Blog',
            'items' => [
                [
                    'title' => 'PHP 8.5 Performance Wins',
                    'published_at' => 'Nov 1, 2026',
                    'url' => 'https://focal.test/blog/php-85',
                ],
            ],
        ]);

        $text = MailBuilder::plainText($doc);

        $this->assertStringContainsString('=== Curated Picks ===', $text);
        $this->assertStringContainsString('Focal Enterprise - $499', $text);
        $this->assertStringContainsString('=== ORDER RECEIPT #FC-8821 ===', $text);
        $this->assertStringContainsString('License Seat (x2) - $250.00', $text);
        $this->assertStringContainsString('Total: $500.00', $text);
        $this->assertStringContainsString('=== Developer Blog ===', $text);
        $this->assertStringContainsString('PHP 8.5 Performance Wins (Nov 1, 2026)', $text);
    }
}

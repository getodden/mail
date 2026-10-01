<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Feature;

use DoPHP\MailBuilder\Data\EmailDocument;
use DoPHP\MailBuilder\Enums\SlotType;
use DoPHP\MailBuilder\MailBuilder;
use DoPHP\MailBuilder\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class DynamicFeedRuntimeTest extends TestCase
{
    public function test_dynamic_feed_fetches_runtime_json_items(): void
    {
        Http::fake([
            'https://api.example.com/recommended' => Http::response([
                'items' => [
                    [
                        'title' => 'Fetched Product A',
                        'price' => '$99',
                        'description' => 'Real-time fetched recommendation.',
                        'button_url' => 'https://example.com/product-a',
                    ],
                ],
            ], 200),
        ]);

        $doc = new EmailDocument;
        $doc->append(SlotType::DynamicFeed, [
            'heading' => 'Personalized Picks',
            'feed_url' => 'https://api.example.com/recommended',
            'items' => [],
        ]);

        $html = MailBuilder::compile($doc);

        $this->assertStringContainsString('Personalized Picks', $html);
        $this->assertStringContainsString('Fetched Product A', $html);
        $this->assertStringContainsString('$99', $html);
    }

    public function test_dynamic_feed_falls_back_gracefully_on_network_error(): void
    {
        Http::fake([
            'https://api.example.com/failed' => Http::response(null, 500),
        ]);

        $doc = new EmailDocument;
        $doc->append(SlotType::DynamicFeed, [
            'heading' => 'Fallback Section',
            'feed_url' => 'https://api.example.com/failed',
            'items' => [
                [
                    'title' => 'Fallback Default Item',
                    'price' => '$19',
                    'button_url' => 'https://example.com/fallback',
                ],
            ],
        ]);

        $html = MailBuilder::compile($doc);

        $this->assertStringContainsString('Fallback Section', $html);
        $this->assertStringContainsString('Fallback Default Item', $html);
    }
}

<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Unit;

use DoPHP\MailBuilder\MailBuilder;
use DoPHP\MailBuilder\Tests\TestCase;
use DoPHP\MailBuilder\Tracking\EmailTrackingPipeline;

class EmailTrackingPipelineTest extends TestCase
{
    public function test_injects_transparent_tracking_pixel_before_body(): void
    {
        $pipeline = new EmailTrackingPipeline;

        $html = '<!DOCTYPE html><html><head></head><body><p>Hello world</p></body></html>';
        $trackingUrl = 'https://focal.test/marketing/track/open/campaign-123';

        $output = $pipeline->injectTrackingPixel($html, $trackingUrl);

        $this->assertStringContainsString('<img src="https://focal.test/marketing/track/open/campaign-123"', $output);
        $this->assertStringContainsString('width="1" height="1"', $output);
        $this->assertStringContainsString('</body>', $output);
    }

    public function test_rewrites_outbound_links_through_url_signer(): void
    {
        $pipeline = new EmailTrackingPipeline;

        $html = '<div><p>Check our <a href="https://acme.com/pricing">pricing</a> or <a href="mailto:support@acme.com">email us</a> or <a href="{{unsubscribe_url}}">unsubscribe</a></p></div>';

        $rewritten = $pipeline->rewriteLinks($html, function (string $url, string $anchor): string {
            return 'https://focal.test/marketing/track/click?url='.urlencode($url).'&anchor='.urlencode($anchor);
        });

        // Outbound URL should be rewritten
        $this->assertStringContainsString('https://focal.test/marketing/track/click?url=https%3A%2F%2Facme.com%2Fpricing&anchor=pricing', $rewritten);

        // mailto and unsubscribe should be skipped
        $this->assertStringContainsString('href="mailto:support@acme.com"', $rewritten);
        $this->assertStringContainsString('href="{{unsubscribe_url}}"', $rewritten);
    }

    public function test_prepare_for_delivery_runs_both_pixel_and_link_signer(): void
    {
        $html = '<html><body><a href="https://focal.test/store">Store</a></body></html>';

        $output = MailBuilder::tracking()->prepareForDelivery(
            $html,
            'https://focal.test/open/1',
            fn ($url) => 'https://focal.test/track?dest='.urlencode($url)
        );

        $this->assertStringContainsString('https://focal.test/track?dest=https%3A%2F%2Ffocal.test%2Fstore', $output);
        $this->assertStringContainsString('<img src="https://focal.test/open/1"', $output);
    }
}

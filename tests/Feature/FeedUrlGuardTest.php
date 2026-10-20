<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Support\OutboundUrlGuard;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FeedUrlGuardTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function refusedUrls(): array
    {
        return [
            'loopback' => ['http://127.0.0.1/admin'],
            'localhost name' => ['http://localhost/admin'],
            'private range' => ['http://10.1.2.3/feed.json'],
            'private range 192.168' => ['http://192.168.0.10/feed.json'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'ipv6 loopback' => ['http://[::1]/feed.json'],
            'ipv6 unique local' => ['http://[fd00::1]/feed.json'],
            'carrier-grade NAT' => ['http://100.64.0.1/feed.json'],
            'name that resolves to a private address' => ['https://internal.example.com/feed.json'],
            'user info naming a public host' => ['http://example.com@127.0.0.1/feed.json'],
        ];
    }

    #[DataProvider('refusedUrls')]
    public function test_a_feed_url_that_points_inside_is_never_fetched(string $url): void
    {
        Http::fake();

        $html = MailBuilder::compile($this->feedDocument($url));

        Http::assertNothingSent();
        $this->assertStringContainsString('Static item', $html);
    }

    public function test_a_public_feed_is_fetched_without_following_redirects(): void
    {
        Http::fake(['https://api.example.com/*' => Http::response(['items' => [['title' => 'Live item']]], 200)]);

        $html = MailBuilder::compile($this->feedDocument('https://api.example.com/feed.json'));

        $this->assertStringContainsString('Live item', $html);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.example.com/feed.json');
    }

    public function test_a_redirect_is_not_followed_and_the_static_items_stay(): void
    {
        Http::fake(['https://api.example.com/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/'])]);

        $html = MailBuilder::compile($this->feedDocument('https://api.example.com/feed.json'));

        $this->assertStringContainsString('Static item', $html);
        Http::assertSentCount(1);
    }

    public function test_a_response_over_the_size_limit_is_ignored(): void
    {
        config(['mail-builder.feeds.max_bytes' => 50]);
        Http::fake(['https://api.example.com/*' => Http::response(['items' => [['title' => str_repeat('Large ', 40)]]], 200)]);

        $html = MailBuilder::compile($this->feedDocument('https://api.example.com/feed.json'));

        $this->assertStringContainsString('Static item', $html);
        $this->assertStringNotContainsString('Large Large', $html);
    }

    public function test_an_allowed_host_skips_the_address_check(): void
    {
        config(['mail-builder.feeds.allowed_hosts' => ['internal.example.com']]);
        Http::fake(['https://internal.example.com/*' => Http::response(['items' => [['title' => 'Own network item']]], 200)]);

        $html = MailBuilder::compile($this->feedDocument('https://internal.example.com/feed.json'));

        $this->assertStringContainsString('Own network item', $html);
    }

    public function test_only_http_and_https_urls_pass_the_guard(): void
    {
        $guard = new OutboundUrlGuard;

        $this->assertFalse($guard->isSafe('file:///etc/passwd'));
        $this->assertFalse($guard->isSafe('gopher://93.184.216.34/'));
        $this->assertFalse($guard->isSafe('//93.184.216.34/feed'));
        $this->assertTrue($guard->isSafe('https://93.184.216.34/feed'));
    }

    public function test_public_and_reserved_addresses_are_told_apart(): void
    {
        foreach (['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946'] as $public) {
            $this->assertTrue(OutboundUrlGuard::isPublicAddress($public), $public);
        }

        foreach (['127.0.0.1', '10.0.0.1', '172.16.0.1', '192.168.1.1', '169.254.169.254', '100.64.0.1', '0.0.0.0', '::1', 'fe80::1', 'fd00::1', '::ffff:127.0.0.1'] as $private) {
            $this->assertFalse(OutboundUrlGuard::isPublicAddress($private), $private);
        }
    }

    private function feedDocument(string $feedUrl): EmailDocument
    {
        $doc = new EmailDocument;
        $doc->append(SlotType::DynamicFeed, [
            'heading' => 'Feed',
            'feed_url' => $feedUrl,
            'items' => [['title' => 'Static item', 'price' => '$1', 'button_url' => 'https://example.com/static']],
        ]);

        return $doc;
    }
}

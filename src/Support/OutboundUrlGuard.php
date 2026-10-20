<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Odden\MailBuilder\Exceptions\UnsafeOutboundUrlException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Guards the server-side fetches whose URL comes from an email's content (the feed blocks) against server-side request forgery.
 *
 * The host is resolved, and every address it resolves to must be a public (globally routable) one: private, loopback,
 * link-local (including the 169.254.169.254 cloud metadata endpoint), carrier-grade NAT, reserved and documentation
 * ranges are refused, for IPv4 and IPv6. The request is then pinned to the checked address (so a second DNS answer
 * cannot point it elsewhere), follows no redirects, has short timeouts, and is dropped if the response is too large.
 *
 * Hosts listed in `mail-builder.feeds.allowed_hosts` skip the address check, for feeds you run on your own network.
 */
class OutboundUrlGuard
{
    public const CONNECT_TIMEOUT = 3;

    public const TIMEOUT = 5;

    /**
     * A pending request for the URL, pinned to its checked address.
     *
     * @throws UnsafeOutboundUrlException
     */
    public function request(string $url): PendingRequest
    {
        [$host, $port, $ip] = $this->check($url);

        $maxBytes = $this->maxBytes();

        $options = [
            'allow_redirects' => false,
            // Stop before the body is read when the server announces more than we accept.
            'on_headers' => function (ResponseInterface $response) use ($maxBytes): void {
                if ((int) $response->getHeaderLine('Content-Length') > $maxBytes) {
                    throw new RuntimeException('The feed response is larger than the allowed size.');
                }
            },
        ];

        if ($ip !== null && defined('CURLOPT_RESOLVE')) {
            $options['curl'] = [CURLOPT_RESOLVE => ["{$host}:{$port}:".(str_contains($ip, ':') ? "[{$ip}]" : $ip)]];
        }

        return Http::withOptions($options)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::TIMEOUT);
    }

    /**
     * The largest response body a feed may have, in bytes.
     */
    public function maxBytes(): int
    {
        return max(1, (int) config('mail-builder.feeds.max_bytes', 1_048_576));
    }

    /**
     * Check the URL and return its host, port and the address to connect to (null for an allow-listed host).
     *
     * @return array{0: string, 1: int, 2: string|null}
     *
     * @throws UnsafeOutboundUrlException
     */
    public function check(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new UnsafeOutboundUrlException('Only http and https URLs with a host can be fetched.');
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if (in_array($host, $this->allowedHosts(), true)) {
            return [$host, $port, null];
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : $this->resolveHost($host);

        if ($addresses === []) {
            throw new UnsafeOutboundUrlException("The host [{$host}] does not resolve.");
        }

        foreach ($addresses as $address) {
            if (! self::isPublicAddress($address)) {
                throw new UnsafeOutboundUrlException("The host [{$host}] resolves to a private or reserved address, which a feed may not fetch.");
            }
        }

        return [$host, $port, $addresses[0]];
    }

    public function isSafe(string $url): bool
    {
        try {
            $this->check($url);

            return true;
        } catch (UnsafeOutboundUrlException) {
            return false;
        }
    }

    public static function isPublicAddress(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE,
        ) !== false;
    }

    /**
     * Every IPv4 and IPv6 address the host resolves to.
     *
     * @return list<string>
     */
    protected function resolveHost(string $host): array
    {
        $addresses = [];

        foreach ([DNS_A => 'ip', DNS_AAAA => 'ipv6'] as $type => $key) {
            $records = @dns_get_record($host, $type);

            foreach (is_array($records) ? $records : [] as $record) {
                if (isset($record[$key]) && is_string($record[$key])) {
                    $addresses[] = $record[$key];
                }
            }
        }

        if ($addresses === []) {
            $fallback = @gethostbynamel($host);
            $addresses = is_array($fallback) ? $fallback : [];
        }

        return array_values(array_unique($addresses));
    }

    /**
     * @return list<string>
     */
    protected function allowedHosts(): array
    {
        $hosts = config('mail-builder.feeds.allowed_hosts', []);

        return array_values(array_filter(array_map(
            fn (mixed $host): string => strtolower(trim((string) $host)),
            is_array($hosts) ? $hosts : [],
        )));
    }
}

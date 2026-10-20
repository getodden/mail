<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Support;

use Odden\MailBuilder\Support\OutboundUrlGuard;

/**
 * Resolves names without touching DNS: "localhost" to loopback, hosts starting with "internal." to a private address, everything else to a public one.
 */
class FakeOutboundUrlGuard extends OutboundUrlGuard
{
    protected function resolveHost(string $host): array
    {
        return match (true) {
            $host === 'localhost' => ['127.0.0.1', '::1'],
            str_starts_with($host, 'internal.') => ['10.0.0.5'],
            default => ['93.184.216.34'],
        };
    }
}

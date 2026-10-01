<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Audit;

interface ScreenshotProviderInterface
{
    /**
     * Submit HTML and return screenshot preview status and URLs for requested device identifiers.
     *
     * @param  list<string>  $devices
     * @return array<string, array{status: 'ready'|'pending'|'failed', url: ?string, device_name: string}>
     */
    public function render(string $html, array $devices = []): array;
}

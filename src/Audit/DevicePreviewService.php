<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Audit;

class DevicePreviewService
{
    /**
     * Standard email client device profiles.
     *
     * @var array<string, string>
     */
    public const SUPPORTED_DEVICES = [
        'outlook_windows' => 'Outlook 2019/2021 (Windows 120 DPI)',
        'outlook_office365' => 'Outlook Office 365 (Web)',
        'apple_mail_ios' => 'Apple Mail (iOS 17 Dark Mode)',
        'apple_mail_macos' => 'Apple Mail (macOS Sonoma)',
        'gmail_web' => 'Gmail Web (Desktop)',
        'gmail_android' => 'Gmail App (Android 14)',
    ];

    protected static ?ScreenshotProviderInterface $defaultProvider = null;

    /**
     * Set a custom or mock screenshot provider driver.
     */
    public static function setProvider(?ScreenshotProviderInterface $provider): void
    {
        self::$defaultProvider = $provider;
    }

    /**
     * Generate or fetch multi-device client preview renders for email HTML.
     *
     * @param  list<string>  $requestedDevices
     * @return array<string, array{status: 'ready'|'pending'|'failed', url: ?string, device_name: string}>
     */
    public static function render(string $html, array $requestedDevices = []): array
    {
        $devices = empty($requestedDevices) ? array_keys(self::SUPPORTED_DEVICES) : $requestedDevices;

        if (self::$defaultProvider !== null) {
            return self::$defaultProvider->render($html, $devices);
        }

        // Default built-in simulated rendering driver
        $results = [];
        $hash = substr(md5($html), 0, 12);

        foreach ($devices as $deviceKey) {
            $deviceName = self::SUPPORTED_DEVICES[$deviceKey] ?? ucfirst(str_replace('_', ' ', $deviceKey));

            $results[$deviceKey] = [
                'status' => 'ready',
                'url' => (string) config('mail-builder.preview_url_prefix', '/mail-builder/preview-device')."/{$deviceKey}-{$hash}.png",
                'device_name' => $deviceName,
            ];
        }

        return $results;
    }
}

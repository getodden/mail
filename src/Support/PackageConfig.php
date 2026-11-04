<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

final class PackageConfig
{
    public static function get(string $key, mixed $default = null): mixed
    {
        if (! function_exists('app') || ! app()->bound('config')) {
            return $default;
        }

        return config($key, $default);
    }
}

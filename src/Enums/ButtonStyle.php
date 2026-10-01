<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Enums;

enum ButtonStyle: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Success = 'success';
    case Danger = 'danger';
    case Dark = 'dark';
    case Outline = 'outline';

    public function backgroundColor(): string
    {
        return match ($this) {
            self::Primary => '#2563eb',
            self::Secondary => '#64748b',
            self::Success => '#16a34a',
            self::Danger => '#dc2626',
            self::Dark => '#0f172a',
            self::Outline => 'transparent',
        };
    }

    public function textColor(): string
    {
        return match ($this) {
            self::Primary, self::Secondary, self::Success, self::Danger, self::Dark => '#ffffff',
            self::Outline => '#0f172a',
        };
    }

    public function borderColor(): string
    {
        return match ($this) {
            self::Primary => '#2563eb',
            self::Secondary => '#64748b',
            self::Success => '#16a34a',
            self::Danger => '#dc2626',
            self::Dark => '#0f172a',
            self::Outline => '#cbd5e1',
        };
    }
}

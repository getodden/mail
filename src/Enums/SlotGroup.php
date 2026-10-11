<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Enums;

/**
 * How an editor groups the slot types in its block palette. The order of the cases is the order they are shown in.
 */
enum SlotGroup: string
{
    case Structure = 'structure';
    case Content = 'content';
    case Layout = 'layout';
    case Commerce = 'commerce';
    case Engagement = 'engagement';
    case Data = 'data';

    public function label(): string
    {
        return match ($this) {
            self::Structure => 'Structure',
            self::Content => 'Content',
            self::Layout => 'Columns & layout',
            self::Commerce => 'Commerce',
            self::Engagement => 'Engagement',
            self::Data => 'Data & feeds',
        };
    }
}

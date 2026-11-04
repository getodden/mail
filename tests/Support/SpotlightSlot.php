<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Support;

use Odden\MailBuilder\Enums\SlotGroup;
use Odden\MailBuilder\Schema\CustomSlotType;
use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchema;

class SpotlightSlot implements CustomSlotType
{
    public function key(): string
    {
        return 'spotlight';
    }

    public function label(): string
    {
        return 'Product Spotlight';
    }

    public function shortLabel(): string
    {
        return 'Spotlight';
    }

    public function description(): string
    {
        return 'Highlights one product.';
    }

    public function group(): SlotGroup
    {
        return SlotGroup::Commerce;
    }

    public function view(): string
    {
        return 'test-slots::spotlight';
    }

    public function schema(): SlotSchema
    {
        return new SlotSchema($this, 'star', [
            new SlotField('headline', FieldType::Text, 'Headline', required: true),
            new SlotField('blurb', FieldType::Textarea, 'Blurb'),
        ], supportsVisibility: true);
    }
}

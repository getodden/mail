<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Support;

use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchema;

class LinkSlot extends SpotlightSlot
{
    public function key(): string
    {
        return 'link_card';
    }

    public function view(): string
    {
        return 'test-slots::link-card';
    }

    public function schema(): SlotSchema
    {
        return new SlotSchema($this, 'star', [
            new SlotField('target_url', FieldType::Url, 'Target'),
            new SlotField('accent', FieldType::Color, 'Accent'),
        ]);
    }
}

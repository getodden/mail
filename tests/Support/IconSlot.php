<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Support;

use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchema;

class IconSlot extends SpotlightSlot
{
    public function __construct(private readonly string $slotKey, private readonly string $icon) {}

    public function key(): string
    {
        return $this->slotKey;
    }

    public function schema(): SlotSchema
    {
        return new SlotSchema($this, $this->icon, [
            new SlotField('headline', FieldType::Text, 'Headline'),
        ]);
    }
}

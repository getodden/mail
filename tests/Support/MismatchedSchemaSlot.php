<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Support;

use Odden\MailBuilder\Schema\SlotSchema;

class MismatchedSchemaSlot extends SpotlightSlot
{
    public function key(): string
    {
        return 'mismatched';
    }

    public function schema(): SlotSchema
    {
        return (new SpotlightSlot)->schema();
    }
}

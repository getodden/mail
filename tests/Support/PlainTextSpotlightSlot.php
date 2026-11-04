<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Support;

use Odden\MailBuilder\Schema\ProvidesPlainText;

class PlainTextSpotlightSlot extends SpotlightSlot implements ProvidesPlainText
{
    public function key(): string
    {
        return 'plain_spotlight';
    }

    public function plainText(array $data): string
    {
        return 'SPOTLIGHT: '.($data['headline'] ?? '');
    }
}

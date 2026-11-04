<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Schema;

use Odden\MailBuilder\Enums\SlotGroup;

interface CustomSlotType
{
    public function key(): string;

    public function label(): string;

    public function shortLabel(): string;

    public function description(): string;

    public function group(): SlotGroup;

    public function view(): string;

    public function schema(): SlotSchema;
}

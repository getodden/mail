<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Schema;

interface ProvidesPlainText
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function plainText(array $data): string;
}

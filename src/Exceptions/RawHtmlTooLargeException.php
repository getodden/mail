<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Exceptions;

use InvalidArgumentException;

final class RawHtmlTooLargeException extends InvalidArgumentException
{
    public static function exceeding(int $bytes, int $limit): self
    {
        return new self("The HTML is {$bytes} bytes, over the {$limit} byte limit (mail-builder.raw_html_max_bytes). Build large content from slots instead of pasting it into one field.");
    }
}

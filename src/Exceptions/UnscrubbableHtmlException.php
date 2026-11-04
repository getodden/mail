<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Exceptions;

use RuntimeException;

final class UnscrubbableHtmlException extends RuntimeException
{
    public static function afterPcreError(string $message): self
    {
        return new self("The HTML could not be scrubbed of unsafe URLs ({$message}), so it was not sent on.");
    }
}

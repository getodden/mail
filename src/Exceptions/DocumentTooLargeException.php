<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Exceptions;

use InvalidArgumentException;

final class DocumentTooLargeException extends InvalidArgumentException
{
    public static function exceeding(int $limit): self
    {
        return new self("The email is over the {$limit} byte limit (mail-builder.max_document_bytes). Remove some blocks or shorten their content.");
    }
}

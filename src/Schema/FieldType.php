<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Schema;

/**
 * The kinds of field an editor has to be able to show for a slot.
 */
enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case RichText = 'rich_text';
    case Url = 'url';
    case Image = 'image';
    case Color = 'color';
    case Select = 'select';
    case Toggle = 'toggle';
    case Number = 'number';

    /** A list of items, each described by the field's own `items` fields. */
    case Items = 'items';
}

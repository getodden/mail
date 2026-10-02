<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Presets;

use Odden\MailBuilder\Data\EmailDocument;

interface PresetContract
{
    /**
     * Unique key identifier for this preset.
     */
    public function key(): string;

    /**
     * Human-friendly title of the preset.
     */
    public function label(): string;

    /**
     * Concise explanation of what this preset is best used for.
     */
    public function description(): string;

    /**
     * Category grouping (e.g. 'marketing', 'sales', 'transactional', 'newsletter').
     */
    public function category(): string;

    /**
     * Return the structured EmailDocument representation of this preset.
     */
    public function document(): EmailDocument;
}

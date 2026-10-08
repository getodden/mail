<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Editor;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;

/**
 * Renders the editor's preview: validates a document the browser sent, and compiles it with a marker on each slot.
 *
 * The result is only for the editor's sandboxed preview, never for sending. The HTTP route and the Filament field
 * both use this, so they accept and reject the same input.
 */
final class PreviewRenderer
{
    public function __construct(private readonly EmailSlotCompiler $compiler) {}

    /**
     * @param  array<string, mixed>  $payload  `{slots: [{type, data, visibility?}], subject?, preview_text?, theme?}`
     *
     * @throws ValidationException
     */
    public function render(array $payload): string
    {
        /** @var array<string, mixed> $data */
        $data = Validator::make($payload, [
            'slots' => ['present', 'array', 'max:'.(int) config('mail-builder.editor.max_slots', 200)],
            'slots.*' => ['array'],
            'slots.*.type' => ['required', 'string', Rule::in(array_map(fn (SlotType $type): string => $type->value, SlotType::cases()))],
            'slots.*.data' => ['nullable', 'array'],
            'slots.*.visibility' => ['nullable', 'array'],
            'subject' => ['nullable', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'array'],
        ])->validate();

        return $this->compiler->compileDocument(EmailDocument::fromArray($data), ['editor_markers' => true]);
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Editor;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Exceptions\InvalidMergeTagStructureException;
use Odden\MailBuilder\Exceptions\RawHtmlTooLargeException;
use Odden\MailBuilder\Schema\SlotRegistry;
use Odden\MailBuilder\Support\BlockMergeTags;
use Odden\MailBuilder\Support\SlotDataSanitizer;

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
            'slots.*.type' => ['required', 'string', Rule::in([...array_map(fn (SlotType $type): string => $type->value, SlotType::cases()), ...app(SlotRegistry::class)->keys()])],
            'slots.*.data' => ['nullable', 'array'],
            'slots.*.visibility' => ['nullable', 'array'],
            'subject' => ['nullable', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'array'],
        ])->validate();

        $document = EmailDocument::fromArray($data);

        try {
            return $this->compiler->compileDocument($document, ['editor_markers' => true, 'fetch_feeds' => false]);
        } catch (RawHtmlTooLargeException $exception) {
            throw ValidationException::withMessages([$this->offendingSlot($document) => [$exception->getMessage()]]);
        } catch (InvalidMergeTagStructureException $exception) {
            throw ValidationException::withMessages([$this->offendingField($document, $exception) => $exception->descriptions()]);
        }
    }

    private function offendingSlot(EmailDocument $document): string
    {
        foreach ($document->slots as $index => $slot) {
            try {
                SlotDataSanitizer::slot($slot);
            } catch (RawHtmlTooLargeException) {
                return "slots.{$index}.data";
            } catch (InvalidMergeTagStructureException) {
                continue;
            }
        }

        return 'slots';
    }

    private function offendingField(EmailDocument $document, InvalidMergeTagStructureException $exception): string
    {
        foreach (['subject' => $document->subject, 'preview_text' => $document->previewText] as $field => $text) {
            if ($exception->field === $field && $text !== null && BlockMergeTags::validate($text) !== []) {
                return $field;
            }
        }

        foreach ($document->slots as $index => $slot) {
            try {
                SlotDataSanitizer::slot($slot);
            } catch (InvalidMergeTagStructureException $found) {
                return "slots.{$index}.data.{$found->field}";
            } catch (RawHtmlTooLargeException) {
                continue;
            }
        }

        return 'slots';
    }
}

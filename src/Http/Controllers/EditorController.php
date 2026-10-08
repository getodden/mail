<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;

/**
 * The two endpoints the drag-and-drop editor needs: the slot schema, and a preview of a document.
 *
 * They are routed only when `mail-builder.editor.routes.enabled` is on, behind the middleware the host app sets.
 */
class EditorController extends Controller
{
    public function schema(): JsonResponse
    {
        return response()->json(SlotSchemaRegistry::toArray());
    }

    /**
     * Render the slots as the full email, with each slot wrapped in a marker the editor uses to find it.
     * The result is for the editor's sandboxed preview, never for sending.
     */
    public function preview(Request $request, EmailSlotCompiler $compiler): JsonResponse
    {
        $data = $request->validate([
            'slots' => ['present', 'array', 'max:'.(int) config('mail-builder.editor.max_slots', 200)],
            'slots.*' => ['array'],
            'slots.*.type' => ['required', 'string', Rule::in(array_map(fn (SlotType $type): string => $type->value, SlotType::cases()))],
            'slots.*.data' => ['nullable', 'array'],
            'slots.*.visibility' => ['nullable', 'array'],
            'subject' => ['nullable', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'array'],
        ]);

        $document = EmailDocument::fromArray($data);

        return response()->json([
            'html' => $compiler->compileDocument($document, ['editor_markers' => true]),
        ]);
    }
}

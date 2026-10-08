<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Odden\MailBuilder\Editor\PreviewRenderer;
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
    public function preview(Request $request, PreviewRenderer $renderer): JsonResponse
    {
        return response()->json(['html' => $renderer->render($request->all())]);
    }
}

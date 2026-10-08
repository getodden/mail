<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Odden\MailBuilder\Tests\TestCase;

class EditorRoutesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('mail-builder.editor.routes.enabled', true);
        $app['config']->set('mail-builder.editor.max_slots', 3);
    }

    public function test_the_schema_route_returns_the_slot_schema(): void
    {
        $response = $this->getJson('/mail-builder/editor/schema')->assertOk();

        $this->assertSame(1, $response->json('version'));
        $this->assertCount(8, $response->json('slots'));
        $this->assertSame('hero', $response->json('slots.1.type'));
    }

    public function test_the_preview_route_renders_the_email_with_a_marker_on_each_slot(): void
    {
        $html = $this->postJson('/mail-builder/editor/preview', [
            'slots' => [
                ['type' => 'hero', 'data' => ['title' => 'Hello preview']],
                ['type' => 'divider', 'data' => []],
            ],
        ])->assertOk()->json('html');

        $this->assertStringContainsString('Hello preview', $html);
        $this->assertStringContainsString('data-odden-slot="0"', $html);
        $this->assertStringContainsString('data-odden-slot="1"', $html);
        $this->assertStringNotContainsString('data-odden-slot="2"', $html);
    }

    public function test_an_empty_document_renders(): void
    {
        $this->postJson('/mail-builder/editor/preview', ['slots' => []])->assertOk()->assertJsonStructure(['html']);
    }

    public function test_the_preview_rejects_bad_input(): void
    {
        $this->postJson('/mail-builder/editor/preview', [])->assertUnprocessable();
        $this->postJson('/mail-builder/editor/preview', ['slots' => [['type' => 'not_a_slot', 'data' => []]]])->assertUnprocessable();
        $this->postJson('/mail-builder/editor/preview', ['slots' => [['data' => []]]])->assertUnprocessable();
        $this->postJson('/mail-builder/editor/preview', ['slots' => [['type' => 'hero', 'data' => 'text']]])->assertUnprocessable();
    }

    public function test_the_preview_limits_how_many_slots_it_renders(): void
    {
        $slots = array_fill(0, 4, ['type' => 'divider', 'data' => []]);

        $this->postJson('/mail-builder/editor/preview', ['slots' => $slots])->assertUnprocessable();
        $this->postJson('/mail-builder/editor/preview', ['slots' => array_slice($slots, 0, 3)])->assertOk();
    }

    public function test_user_content_in_a_preview_is_escaped(): void
    {
        $html = $this->postJson('/mail-builder/editor/preview', [
            'slots' => [['type' => 'hero', 'data' => ['title' => '<script>alert(1)</script>']]],
        ])->assertOk()->json('html');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }
}

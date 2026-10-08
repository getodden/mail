<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Odden\MailBuilder\Tests\TestCase;

class EditorRoutesDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('mail-builder.editor.routes.enabled', false);
    }

    public function test_the_editor_routes_do_not_exist_unless_they_are_switched_on(): void
    {
        $this->getJson('/mail-builder/editor/schema')->assertNotFound();
        $this->postJson('/mail-builder/editor/preview', ['slots' => []])->assertNotFound();
    }
}

<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Odden\MailBuilder\Tests\TestCase;

class EditorRoutesMiddlewareTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        // The `web` middleware group encrypts cookies, which needs a key; CI's skeleton has none.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('mail-builder.editor.routes.enabled', true);
        $app['config']->set('mail-builder.editor.routes.prefix', 'admin/mail-editor');
        $app['config']->set('mail-builder.editor.routes.middleware', ['web', 'auth']);
    }

    public function test_the_host_apps_middleware_and_prefix_apply(): void
    {
        $this->getJson('/admin/mail-editor/schema')->assertUnauthorized();
        $this->postJson('/admin/mail-editor/preview', ['slots' => []])->assertUnauthorized();
        $this->getJson('/mail-builder/editor/schema')->assertNotFound();
    }
}

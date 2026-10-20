<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests;

use Odden\MailBuilder\MailBuilderServiceProvider;
use Odden\MailBuilder\Support\OutboundUrlGuard;
use Odden\MailBuilder\Tests\Support\FakeOutboundUrlGuard;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests never use real DNS: see FakeOutboundUrlGuard.
        $this->app->bind(OutboundUrlGuard::class, FakeOutboundUrlGuard::class);
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [MailBuilderServiceProvider::class];
    }
}

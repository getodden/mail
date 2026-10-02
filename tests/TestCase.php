<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests;

use Odden\MailBuilder\MailBuilderServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [MailBuilderServiceProvider::class];
    }
}

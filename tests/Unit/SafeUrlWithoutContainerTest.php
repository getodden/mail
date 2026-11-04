<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Illuminate\Container\Container;
use Odden\MailBuilder\Support\SafeUrl;
use PHPUnit\Framework\TestCase;

class SafeUrlWithoutContainerTest extends TestCase
{
    protected function setUp(): void
    {
        Container::setInstance(new Container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function test_the_default_schemes_apply_when_no_config_is_bound(): void
    {
        $this->assertFalse(Container::getInstance()->bound('config'));
        $this->assertSame('https://example.com/a', SafeUrl::for('https://example.com/a'));
        $this->assertSame('#', SafeUrl::for('javascript:alert(1)'));
        $this->assertSame('mailto:a@b.co', SafeUrl::for('mailto:a@b.co'));
        $this->assertSame('', SafeUrl::for('data:text/html,x', true));
        $this->assertContains('https', SafeUrl::linkSchemes());
        $this->assertNotContains('javascript', SafeUrl::linkSchemes());
    }
}

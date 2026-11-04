<?php

declare(strict_types=1);

use Odden\MailBuilder\Compilers\GmailActionCompiler;

it('cannot be broken out of by a script closing tag in a value', function (): void {
    $script = GmailActionCompiler::make()
        ->viewAction('</script><script>alert(1)</script>', 'https://example.com/o?a=1&b=2', "it's \"quoted\"")
        ->toScript();

    expect(substr_count($script, '<script'))->toBe(1)
        ->and(substr_count($script, '</script>'))->toBe(1)
        ->and($script)->not->toContain('alert(1)</script>')
        ->and($script)->toContain('\\u003C/script\\u003E');
});

it('still decodes to the original values', function (): void {
    $script = GmailActionCompiler::make()
        ->viewAction('</script><script>alert(1)</script>', 'https://example.com/o?a=1&b=2', "it's \"quoted\"")
        ->toScript();

    $json = trim((string) preg_replace('#^<script[^>]*>|</script>$#', '', $script));
    $decoded = json_decode($json, true);

    expect($decoded['potentialAction']['name'])->toBe('</script><script>alert(1)</script>')
        ->and($decoded['potentialAction']['url'])->toBe('https://example.com/o?a=1&b=2')
        ->and($decoded['potentialAction']['description'])->toBe("it's \"quoted\"");
});

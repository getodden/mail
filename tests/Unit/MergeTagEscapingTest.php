<?php

declare(strict_types=1);

use Odden\MailBuilder\Mail\TemplateMailable;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\MergeTags\MergeTagInterpolator;

it('leaves values as they are unless asked to escape', function (): void {
    $interpolator = new MergeTagInterpolator;

    expect($interpolator->interpolate('Hi {{ name }}', ['name' => '<b>Pat</b>']))->toBe('Hi <b>Pat</b>');
});

it('escapes values from the context where asked, but not the template author\'s own text', function (): void {
    $interpolator = new MergeTagInterpolator;
    $html = '<p>Hi {{ name }}, {{ missing | default:"<em>friend</em>" }}</p><a href="{{ url }}">go</a>';

    $out = $interpolator->interpolate($html, ['name' => '<script>alert(1)</script>', 'url' => 'https://x.test/?a=1&b="2"'], escape: true);

    expect($out)->toContain('Hi &lt;script&gt;alert(1)&lt;/script&gt;')
        ->and($out)->toContain('<em>friend</em>')
        ->and($out)->toContain('href="https://x.test/?a=1&amp;b=&quot;2&quot;"')
        ->and($out)->not->toContain('<script>');
});

it('puts markup in through the raw filter, and only there', function (): void {
    $interpolator = new MergeTagInterpolator;

    $out = $interpolator->interpolate('{{ table | raw }} / {{ note }}', ['table' => '<table></table>', 'note' => '<i>x</i>'], escape: true);

    expect($out)->toBe('<table></table> / &lt;i&gt;x&lt;/i&gt;');
});

it('applies the other filters before escaping', function (): void {
    $out = (new MergeTagInterpolator)->interpolate('{{ name | upper }}', ['name' => 'a&b'], escape: true);

    expect($out)->toBe('A&amp;B');
});

it('escapes in the HTML of a TemplateMailable, not in the subject or the plain text', function (): void {
    $mail = new TemplateMailable(
        template: '<p>Hello {{ name }}</p>',
        data: ['name' => "O'Brien <x>\r\nBcc: evil@example.com"],
        subjectLine: 'Receipt for {{ name }}',
    );

    expect($mail->compiledHtml)->toContain('O&#039;Brien &lt;x&gt;')
        ->and($mail->subjectLine)->toBe("Receipt for O'Brien <x> Bcc: evil@example.com")
        ->and($mail->subjectLine)->not->toContain("\n")
        ->and($mail->compiledPlainText)->toContain("O'Brien");
});

it('only sends a List-Unsubscribe header for an http(s) or mailto address with nothing that can end the value', function (string $url, bool $sent): void {
    $mail = new TemplateMailable(template: '<p>Hi</p>', data: ['unsubscribe_url' => $url], subjectLine: 'S');

    $headers = $mail->headers()->text;

    expect(isset($headers['List-Unsubscribe']))->toBe($sent);
})->with([
    'https' => ['https://example.com/u/1?x=1&y=2', true],
    'mailto' => ['mailto:unsubscribe@example.com?subject=stop', true],
    'javascript' => ['javascript:alert(1)', false],
    'data' => ['data:text/html,x', false],
    'closing bracket' => ['https://example.com/u>, <https://evil.example/x', false],
    'newline' => ["https://example.com/u\r\nBcc: x@example.com", false],
    'space' => ['https://example.com/a b', false],
]);

it('keeps MailBuilder::interpolate unescaped by default for the campaign code that escapes its own values', function (): void {
    expect(MailBuilder::interpolate('{{ a }}', ['a' => '<b>']))->toBe('<b>')
        ->and(MailBuilder::interpolate('{{ a }}', ['a' => '<b>'], escape: true))->toBe('&lt;b&gt;');
});

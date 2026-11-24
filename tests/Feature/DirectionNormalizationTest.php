<?php

declare(strict_types=1);

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;

it('normalizes a crafted theme direction to ltr instead of injecting CSS', function (): void {
    $doc = new EmailDocument(subject: 'Fixture', previewText: 'Preview', theme: ['direction' => 'rtl; } body { display:none']);
    $doc->append(SlotType::Header, ['brand_name' => 'Focal']);

    $html = app(EmailSlotCompiler::class)->compileDocument($doc);

    expect($html)->toContain('dir="ltr"')
        ->and($html)->not->toContain('dir="rtl"')
        ->and($html)->not->toContain('display:none');
});

it('accepts only the exact lowercase rtl, so RTL falls back to ltr', function (): void {
    // Any spelling other than exact 'rtl' (here 'RTL') normalizes to 'ltr'.
    $doc = new EmailDocument(subject: 'Fixture', previewText: 'Preview', theme: ['direction' => 'RTL']);

    $html = app(EmailSlotCompiler::class)->compileDocument($doc);

    expect($html)->toContain('dir="ltr"')->not->toContain('dir="rtl"');
});

it('normalizes the direction of a single slot', function (): void {
    $slot = new EmailSlot(type: SlotType::TwoColumn, data: ['left_title' => 'Left', 'right_title' => 'Right']);
    $compiler = app(EmailSlotCompiler::class);

    $crafted = $compiler->compileSlot($slot, [], ['direction' => 'rtl; } body { display:none']);

    expect($crafted)->toContain('dir="ltr"')->not->toContain('dir="rtl"');
    expect($compiler->compileSlot($slot, ['direction' => 'rtl']))->toContain('dir="rtl"');
});

it('normalizes direction when another caller renders the layout view', function (): void {
    $html = view('mail-builder::layout', [
        'content' => '',
        'previewText' => '',
        'subject' => 'Fixture',
        'theme' => [],
        'direction' => 'rtl; } body { display:none',
    ])->render();

    expect($html)->toContain('dir="ltr"')->not->toContain('display:none');
});

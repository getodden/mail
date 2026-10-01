<?php

declare(strict_types=1);

// Scans source text rather than using arch()->not->toUse(): Focal is not installed
// here, so the arch plugin cannot resolve (and would silently ignore) its classes.
it('is standalone and does not reference Focal CRM', function (): void {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__).'/src'));

    foreach ($files as $file) {
        if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
            expect((string) file_get_contents($file->getPathname()))
                ->not->toMatch('/\bFocal\\\\/', "{$file->getPathname()} references the Focal namespace");
        }
    }
});

arch('no debugging statements are left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

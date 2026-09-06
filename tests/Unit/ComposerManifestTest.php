<?php

declare(strict_types=1);

/**
 * Guards composer.json against the undeclared dependency the package used to
 * have: Illuminate\Foundation (AboutCommand, PendingDispatch, the queue
 * Queueable trait and the config()/app()/event()/dispatch() helpers) ships
 * only inside laravel/framework, never as a standalone illuminate/* package.
 */
function composerRequire(): array
{
    $manifest = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    return $manifest['require'];
}

function srcUsesFoundation(): bool
{
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../src')) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $source = (string) file_get_contents($file->getPathname());

        if (str_contains($source, 'Illuminate\\Foundation\\')
            || preg_match('/\b(config|app|event|dispatch|report|base_path|config_path)\(/', $source) === 1) {
            return true;
        }
    }

    return false;
}

it('requires laravel/framework', function () {
    expect(composerRequire())->toHaveKey('laravel/framework');
});

it('needs the framework because src uses Illuminate\Foundation classes or helpers', function () {
    // If this ever turns false the package could move back to illuminate/* components.
    expect(srcUsesFoundation())->toBeTrue();
});

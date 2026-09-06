<?php

declare(strict_types=1);

/**
 * Guards composer.json against undeclared dependencies: every Illuminate
 * namespace the package imports must come from a package it requires.
 *
 * Illuminate\Foundation (AboutCommand, PendingDispatch, Foundation\Queue\Queueable
 * and the config()/app()/event()/dispatch() helpers) is not published as a
 * standalone illuminate/* package, so using it means requiring laravel/framework.
 */
function composerManifest(): array
{
    return json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);
}

/** @return list<string> */
function illuminateNamespacesUsedInSrc(): array
{
    $namespaces = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../src')) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all('/^use Illuminate\\\\([A-Za-z]+)\\\\/m', (string) file_get_contents($file->getPathname()), $matches);
        $namespaces = [...$namespaces, ...$matches[1]];
    }

    return array_values(array_unique($namespaces));
}

it('requires laravel/framework because the package uses Illuminate\Foundation directly', function () {
    $require = composerManifest()['require'];

    expect($require)->toHaveKey('laravel/framework')
        ->and($require['laravel/framework'])->toBe('^13.0');
});

it('declares every Illuminate namespace it imports', function () {
    $require = composerManifest()['require'];
    $namespaces = illuminateNamespacesUsedInSrc();

    expect($namespaces)->toContain('Foundation');

    foreach ($namespaces as $namespace) {
        $component = 'illuminate/'.strtolower($namespace);
        $declared = array_key_exists($component, $require) || array_key_exists('laravel/framework', $require);

        expect($declared)->toBeTrue("Illuminate\\{$namespace} is imported in src/ but neither {$component} nor laravel/framework is required.");
    }
});

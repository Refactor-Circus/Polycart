<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Atrium\Testing\ResetsScreenExtensions;
use RefactorCircus\Polycart\Tests\TestCase;

/*
 * The test case resets the screen extensions registry after every test, so
 * what one test registers never reaches the next. These two run in order:
 * the first registers, the second finds nothing left.
 */

it('resets the screen extensions after every test', function (): void {
    expect(class_uses_recursive(TestCase::class))->toContain(ResetsScreenExtensions::class);
});

it('registers an extension for this test only', function (): void {
    Atrium::screens()
        ->tab('polycart.cart', TabDefinition::make('acme.isolated')->view('polycart-tests::marker')->public())
        ->section('polycart.cart', 'before', SectionDefinition::make('acme.isolated')->view('polycart-tests::marker')->public());

    expect(Atrium::screens()->tabs('polycart.cart'))->toHaveCount(1)
        ->and(Atrium::screens()->sections('polycart.cart', 'before'))->toHaveCount(1);
});

it('finds the registry empty in the next test', function (): void {
    expect(Atrium::screens()->tabs('polycart.cart'))->toBe([])
        ->and(Atrium::screens()->sections('polycart.cart', 'before'))->toBe([]);
});

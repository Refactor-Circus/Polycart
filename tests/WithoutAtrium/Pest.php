<?php

declare(strict_types=1);

use RefactorCircus\Polycart\Tests\WithoutAtrium\TestCase;

/*
 * Loaded instead of tests/Pest.php: `composer test:without-atrium` runs Pest
 * with `--test-directory=tests/WithoutAtrium`, so nothing here names a test
 * case that needs Atrium.
 */

uses(TestCase::class)->in(__DIR__);

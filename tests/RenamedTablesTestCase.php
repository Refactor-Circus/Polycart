<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Tests;

use RefactorCircus\Polycart\Support\Tables;

/**
 * Every Polycart table renamed through `polycart.tables` before migrating.
 */
abstract class RenamedTablesTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        foreach (array_keys(Tables::DEFAULTS) as $key) {
            $app['config']->set('polycart.tables.'.$key, 'crm_'.$key);
        }
    }
}

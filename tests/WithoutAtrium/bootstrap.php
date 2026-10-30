<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

/*
 * Bootstrap for phpunit.without-atrium.xml: the package as an application
 * without refactor-circus/atrium sees it. Atrium stays installed as a dev
 * dependency, so its PSR-4 prefix is taken out of every Composer loader;
 * any class under RefactorCircus\Atrium\ then fails to load, as it would
 * were the package not installed. The CI lane `without-atrium` runs the
 * same suite with Atrium uninstalled, where this is a no-op.
 */

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

foreach (ClassLoader::getRegisteredLoaders() as $loader) {
    $loader->setPsr4('RefactorCircus\\Atrium\\', []);
}

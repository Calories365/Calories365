<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->ensureTestingDatabase($app);

        return $app;
    }

    /**
     * Refuse to run against anything but a *_testing database.
     *
     * The container exports the working .env, so a phpunit.xml variable that
     * fails to apply leaves the tests on the development database — and
     * RefreshDatabase would wipe it. This runs before any test trait.
     */
    private function ensureTestingDatabase(Application $app): void
    {
        $connection = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if (! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                "Tests must run against a *_testing database, got [{$connection}:{$database}]. Check phpunit.xml."
            );
        }
    }
}

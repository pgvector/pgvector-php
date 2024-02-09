<?php

declare(strict_types=1);

namespace Pgvector\Laravel;

use Illuminate\Support\ServiceProvider;

class PgvectorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');

        $this->publishes([
            __DIR__.'/migrations' => database_path('migrations')
        ], 'pgvector-migrations');

        Schema::register();
    }
}

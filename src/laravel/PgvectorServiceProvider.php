<?php

namespace Pgvector\Laravel;

use Illuminate\Support\ServiceProvider;

class PgvectorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');

        $this->publishes([
            __DIR__.'/migrations' => database_path('migrations')
        ], 'pgvector-migrations');

        Schema::register();
    }
}

<?php

namespace Pgvector\Laravel;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
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

        PostgresGrammar::macro('typeVector', function (ColumnDefinition $column) {
            if ($column->get('dimensions')) {
                return 'vector(' . intval($column->get('dimensions')) . ')';
            } else {
                return 'vector';
            }
        });

        Blueprint::macro('vector', function($column, $dimensions = NULL) {
            return $this->addColumn('vector', $column, compact('dimensions'));
        });
    }
}

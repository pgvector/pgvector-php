<?php

namespace Pgvector\Laravel;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;

class Schema
{
    public static function register()
    {
        PostgresGrammar::macro('typeVector', function (ColumnDefinition $column) {
            if ($column->get('dimensions')) {
                return 'vector(' . intval($column->get('dimensions')) . ')';
            } else {
                return 'vector';
            }
        });

        Blueprint::macro('vector', function ($column, $dimensions = null) {
            return $this->addColumn('vector', $column, compact('dimensions'));
        });
    }
}

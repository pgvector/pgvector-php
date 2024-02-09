<?php

declare(strict_types=1);

namespace Pgvector\Laravel;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;

class Schema
{
    public static function register(): void
    {
        PostgresGrammar::macro(
            'typeVector',
            fn (ColumnDefinition $column) => $column->get('dimensions') === null
                ? 'vector'
                : 'vector(' . intval($column->get('dimensions')) . ')',
        );

        Blueprint::macro(
            'vector',
            fn ($column, $dimensions = null) => $this->addColumn('vector', $column, compact('dimensions')),
        );
    }
}

<?php

namespace Pgvector\Laravel;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;

class Schema
{
    public static function register(): void
    {
        PostgresGrammar::macro('typeVector', function (ColumnDefinition $column): string {
            if ($column->get('dimensions')) {
                return 'vector(' . intval($column->get('dimensions')) . ')';
            } else {
                return 'vector';
            }
        });

        PostgresGrammar::macro('typeHalfvec', function (ColumnDefinition $column): string {
            if ($column->get('dimensions')) {
                return 'halfvec(' . intval($column->get('dimensions')) . ')';
            } else {
                return 'halfvec';
            }
        });

        PostgresGrammar::macro('typeBit', function (ColumnDefinition $column): string {
            if ($column->get('length')) {
                return 'bit(' . intval($column->get('length')) . ')';
            } else {
                return 'bit';
            }
        });

        PostgresGrammar::macro('typeSparsevec', function (ColumnDefinition $column): string {
            if ($column->get('dimensions')) {
                return 'sparsevec(' . intval($column->get('dimensions')) . ')';
            } else {
                return 'sparsevec';
            }
        });

        Blueprint::macro('vector', function (string $column, mixed $dimensions = null): ColumnDefinition {
            return $this->addColumn('vector', $column, compact('dimensions'));
        });

        Blueprint::macro('halfvec', function (string $column, mixed $dimensions = null): ColumnDefinition {
            return $this->addColumn('halfvec', $column, compact('dimensions'));
        });

        Blueprint::macro('bit', function (string $column, mixed $length = null): ColumnDefinition {
            return $this->addColumn('bit', $column, compact('length'));
        });

        Blueprint::macro('sparsevec', function (string $column, mixed $dimensions = null): ColumnDefinition {
            return $this->addColumn('sparsevec', $column, compact('dimensions'));
        });
    }
}

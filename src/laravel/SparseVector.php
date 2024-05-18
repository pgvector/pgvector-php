<?php

namespace Pgvector\Laravel;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SparseVector extends \Pgvector\SparseVector implements Castable
{
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class ($arguments) implements CastsAttributes {
            public function __construct(array $arguments)
            {
                // no need for dimensions
            }

            public function get(mixed $model, string $key, mixed $value, array $attributes): ?\Pgvector\SparseVector
            {
                if (is_null($value)) {
                    return null;
                }

                return SparseVector::fromString($value);
            }

            public function set(mixed $model, string $key, mixed $value, array $attributes): ?string
            {
                if (is_null($value)) {
                    return null;
                }

                if (is_array($value)) {
                    $value = SparseVector::fromDense($value);
                }

                return (string) $value;
            }
        };
    }
}

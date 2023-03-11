<?php

namespace Pgvector\Laravel;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Vector extends \Pgvector\Vector implements Castable
{
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class ($arguments) implements CastsAttributes {
            public function __construct(array $arguments)
            {
                // no need for dimensions
            }

            public function get(Model $model, string $key, mixed $value, array $attributes): ?array
            {
                if (is_null($value)) {
                    return null;
                }

                // TODO return Vector?
                return (new Vector($value))->toArray();
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): ?string
            {
                if (is_null($value)) {
                    return null;
                }

                if (!($value instanceof Vector)) {
                    $value = new Vector($value);
                }

                return (string) $value;
            }
        };
    }
}

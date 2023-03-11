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
            protected $dimensions;

            public function __construct(array $arguments)
            {
                $this->dimensions = count($arguments) > 0 ? $arguments[0] : null;
            }

            // TODO return Vector?
            public function get(Model $model, string $key, mixed $value, array $attributes): ?array
            {
                if (!is_null($value)) {
                    return (new Vector($value))->toArray();
                }
                return null;
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): ?string
            {
                if (is_array($value)) {
                    if (!is_null($this->dimensions) && count($value) != $this->dimensions) {
                        // TODO throw error?
                        return null;
                    }
                    return (string) new Vector($value);
                }
                // TODO throw error?
                return null;
            }
        };
    }
}

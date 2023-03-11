<?php

namespace Pgvector\Laravel;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Vector implements Castable
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return json_encode($this->value, JSON_THROW_ON_ERROR, 1);
    }

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
                    $decoded = json_decode($value, true, 2, JSON_THROW_ON_ERROR);
                    return is_array($decoded) ? $decoded : null;
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

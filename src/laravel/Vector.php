<?php

namespace Pgvector\Laravel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Vector implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (isset($value)) {
            return json_decode($value, true);
        }
        return null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        return $value;
    }
}

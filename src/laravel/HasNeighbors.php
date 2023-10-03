<?php

namespace Pgvector\Laravel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MissingAttributeException;

trait HasNeighbors
{
    public function scopeNearestNeighbors(Builder $query, string $column, mixed $value, int $distance): void
    {
        switch ($distance) {
            case Distance::L2:
                $op = '<->';
                break;
            case Distance::InnerProduct:
                $op = '<#>';
                break;
            case Distance::Cosine:
                $op = '<=>';
                break;
            default:
                throw new \InvalidArgumentException("Invalid distance");
        }
        $wrapped = $query->getGrammar()->wrap($column);
        $order = "$wrapped $op ?";
        $neighborDistance = $distance == Distance::InnerProduct ? "($order) * -1" : $order;
        $vector = $value instanceof Vector ? $value : new Vector($value);

        $query->select()
            ->selectRaw("$neighborDistance AS neighbor_distance", [$vector])
            ->withCasts(['neighbor_distance' => 'double'])
            ->whereNotNull($column)
            ->orderByRaw($order, $vector);
    }

    public function nearestNeighbors(string $column, int $distance): Builder
    {
        $id = $this->getKey();
        if (!array_key_exists($column, $this->attributes)) {
            // TODO use MissingAttributeException when Laravel 9 no longer supported
            throw new \OutOfBoundsException('Missing attribute');
        }
        $value = $this->getAttributeValue($column);
        return static::whereKeyNot($id)->nearestNeighbors($column, $value, $distance);
    }
}

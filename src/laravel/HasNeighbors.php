<?php

namespace Pgvector\Laravel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MissingAttributeException;

trait HasNeighbors
{
    public function scopeNearestNeighbors(Builder $query, string $column, mixed $value, Distance $distance): void
    {
        $op = $distance->operator();
        $wrapped = $query->getGrammar()->wrap($column);
        $order = "$wrapped $op ?";
        $neighborDistance = $distance == Distance::InnerProduct ? "($order) * -1" : $order;
        $vector = is_array($value) ? new Vector($value) : $value;

        // ideally preserve existing select, but does not appear to be a way to get columns
        $query->select()
            ->selectRaw("$neighborDistance AS neighbor_distance", [$vector])
            ->withCasts(['neighbor_distance' => 'double'])
            ->whereNotNull($column)
            ->orderByRaw($order, [$vector]);
    }

    public function nearestNeighbors(string $column, Distance $distance): Builder
    {
        $id = $this->getKey();
        if (!array_key_exists($column, $this->attributes)) {
            throw new MissingAttributeException($this, $column);
        }
        $value = $this->getAttributeValue($column);
        return static::whereKeyNot($id)->nearestNeighbors($column, $value, $distance);
    }
}

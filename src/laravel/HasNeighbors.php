<?php

namespace Pgvector\Laravel;

use Illuminate\Database\Eloquent\Builder;

trait HasNeighbors
{
    public function scopeNearestNeighbors(Builder $query, string $column, mixed $value, int $distance): void
    {
        switch ($distance) {
            case Distance::L2Distance:
                $op = '<->';
                break;
            case Distance::MaxInnerProduct:
                $op = '<#>';
                break;
            case Distance::CosineDistance:
                $op = '<=>';
                break;
            default:
                throw new \InvalidArgumentException("Invalid distance");
        }
        $wrapped = $query->getGrammar()->wrap($column);
        $order = "$wrapped $op ?";
        $neighborDistance = $distance == Distance::MaxInnerProduct ? "($order) * -1" : $order;
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
        $value = $this->getAttributeValue($column);
        return static::whereKeyNot($id)->nearestNeighbors($column, $value, $distance);
    }
}

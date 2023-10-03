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
        $vector = new Vector($value);

        $neighborDistance = $order;
        if ($distance == Distance::MaxInnerProduct) {
            $neighborDistance = "($order) * -1";
        }

        $query->select()
            ->selectRaw("$neighborDistance AS neighbor_distance", [$vector])
            ->withCasts(['neighbor_distance' => 'double'])
            ->whereNotNull($column)
            ->orderByRaw($order, $vector);
    }
}

<?php

namespace Pgvector\Laravel;

enum Distance
{
    case L2;
    case InnerProduct;
    case Cosine;
    case L1;
    case Hamming;
    case Jaccard;

    /**
     * Get the operator for the distance.
     * 
     * @see https://github.com/pgvector/pgvector?tab=readme-ov-file#vector-operators
     */
    public function operator(): string
    {
        return match ($this) {
            Distance::L2 => '<->',
            Distance::InnerProduct => '<#>',
            Distance::Cosine => '<=>',
            Distance::L1 => '<+>',
            Distance::Hamming => '<~>',
            Distance::Jaccard => '<%>',
            default => throw new \InvalidArgumentException("Invalid distance")
        };
    }
}

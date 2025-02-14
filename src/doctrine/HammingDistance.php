<?php

namespace Pgvector\Doctrine;

class HammingDistance extends DistanceNode
{
    protected function getOp(): string
    {
        return '<~>';
    }
}

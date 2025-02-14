<?php

namespace Pgvector\Doctrine;

class L2Distance extends DistanceNode
{
    protected function getOp(): string
    {
        return '<->';
    }
}

<?php

namespace Pgvector\Doctrine;

class MaxInnerProduct extends DistanceNode
{
    protected function getOp(): string
    {
        return '<#>';
    }
}

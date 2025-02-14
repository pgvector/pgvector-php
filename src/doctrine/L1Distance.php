<?php

namespace Pgvector\Doctrine;

class L1Distance extends DistanceNode
{
    protected function getOp(): string
    {
        return '<+>';
    }
}

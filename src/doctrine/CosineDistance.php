<?php

namespace Pgvector\Doctrine;

class CosineDistance extends DistanceNode
{
    protected function getOp(): string
    {
        return '<=>';
    }
}

<?php

namespace Pgvector\Doctrine;

class JaccardDistance extends DistanceNode
{
    protected function getOp(): string
    {
        return '<%>';
    }
}

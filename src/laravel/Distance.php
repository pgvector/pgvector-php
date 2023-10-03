<?php

namespace Pgvector\Laravel;

// TODO use enum when PHP 8.0 reaches EOL
class Distance
{
    public const L2Distance = 0;
    public const MaxInnerProduct = 1;
    public const CosineDistance = 2;
}

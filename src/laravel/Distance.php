<?php

namespace Pgvector\Laravel;

// TODO use enum when PHP 8.0 reaches EOL
class Distance
{
    public const L2 = 0;
    public const InnerProduct = 1;
    public const Cosine = 2;
}

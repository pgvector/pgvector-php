<?php

declare(strict_types=1);

namespace Pgvector\Laravel;

enum Distance: int
{
    case L2 = 0;
    case InnerProduct = 1;
    case Cosine = 2;
}

<?php

namespace Pgvector\Laravel;

enum Distance
{
    case L2;
    case InnerProduct;
    case Cosine;
}

<?php

use PHPUnit\Framework\TestCase;

use Pgvector\SparseVector;

final class SparseVectorTest extends TestCase
{
    public function testToString()
    {
        $embedding = SparseVector::fromDense([1, 0, 2, 0, 3, 0]);
        $this->assertEquals('{1:1,3:2,5:3}/6', (string) $embedding);
    }

    public function testToArray()
    {
        $embedding = SparseVector::fromDense([1, 0, 2, 0, 3, 0]);
        $this->assertEquals([1, 0, 2, 0, 3, 0], $embedding->toArray());
    }
}

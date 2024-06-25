<?php

use PHPUnit\Framework\TestCase;

use Pgvector\SparseVector;

final class SparseVectorTest extends TestCase
{
    public function testFromDense()
    {
        $embedding = SparseVector::fromDense([1, 0, 2, 0, 3, 0]);
        $this->assertEquals(6, $embedding->dimensions());
        $this->assertEquals([0, 2, 4], $embedding->indices());
        $this->assertEquals([1, 2, 3], $embedding->values());
    }

    public function testFromPairs()
    {
        $pairs = [2 => 2, 4 => 3, 0 => 1, 3 => 0];
        $embedding = SparseVector::fromPairs($pairs, 6);
        $this->assertEquals([1, 0, 2, 0, 3, 0], $embedding->toArray());
        $this->assertEquals([0, 2, 4], $embedding->indices());
        $this->assertEquals([2, 4, 0, 3], array_keys($pairs));
    }

    public function testFromString()
    {
        $embedding = SparseVector::fromString('{1:1,3:2,5:3}/6');
        $this->assertEquals(6, $embedding->dimensions());
        $this->assertEquals([0, 2, 4], $embedding->indices());
        $this->assertEquals([1, 2, 3], $embedding->values());
    }

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

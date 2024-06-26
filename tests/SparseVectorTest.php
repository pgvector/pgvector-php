<?php

use PHPUnit\Framework\TestCase;

use Pgvector\SparseVector;

final class SparseVectorTest extends TestCase
{
    public function testFromDense()
    {
        $embedding = new SparseVector([1, 0, 2, 0, 3, 0]);
        $this->assertEquals(6, $embedding->dimensions());
        $this->assertEquals([0, 2, 4], $embedding->indices());
        $this->assertEquals([1, 2, 3], $embedding->values());
    }

    public function testFromMap()
    {
        $map = [2 => 2, 4 => 3, 0 => 1, 3 => 0];
        $embedding = new SparseVector($map, 6);
        $this->assertEquals([1, 0, 2, 0, 3, 0], $embedding->toArray());
        $this->assertEquals([0, 2, 4], $embedding->indices());
        $this->assertEquals([2, 4, 0, 3], array_keys($map));
    }

    public function testFromString()
    {
        $embedding = new SparseVector('{1:1,3:2,5:3}/6');
        $this->assertEquals(6, $embedding->dimensions());
        $this->assertEquals([0, 2, 4], $embedding->indices());
        $this->assertEquals([1, 2, 3], $embedding->values());
    }

    public function testToString()
    {
        $embedding = new SparseVector([1, 0, 2, 0, 3, 0]);
        $this->assertEquals('{1:1,3:2,5:3}/6', (string) $embedding);
    }

    public function testToArray()
    {
        $embedding = new SparseVector([1, 0, 2, 0, 3, 0]);
        $this->assertEquals([1, 0, 2, 0, 3, 0], $embedding->toArray());
    }
}

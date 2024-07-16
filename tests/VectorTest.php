<?php

use PHPUnit\Framework\TestCase;

use Pgvector\Vector;

final class VectorTest extends TestCase
{
    public function testToString()
    {
        $embedding = new Vector([1, 1, 1]);
        $this->assertEquals('[1,1,1]', (string) $embedding);
    }

    public function testToArray()
    {
        $embedding = new Vector([1, 1, 1]);
        $this->assertEquals([1, 1, 1], $embedding->toArray());
    }

    public function testInvalidInteger()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array');

        new Vector(1);
    }

    public function testInvalidArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array to be a list');

        new Vector(['a' => 1]);
    }

    public function testInvalidString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid text representation');

        new Vector('{"a": 1}');
    }

    public function testInvalidJson()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid text representation');

        new Vector("tru");
    }

    public function testEmptyArray()
    {
        $embedding = new Vector([]);
        $this->assertEquals('[]', (string) $embedding);
    }

    public function testSplFixedArray()
    {
        $embedding = new Vector(SplFixedArray::fromArray([1, 2, 3]));
        $this->assertEquals('[1,2,3]', (string) $embedding);
    }
}

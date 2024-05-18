<?php

use PHPUnit\Framework\TestCase;

use Pgvector\HalfVector;

final class HalfVectorTest extends TestCase
{
    public function testToString()
    {
        $embedding = new HalfVector([1, 1, 1]);
        $this->assertEquals('[1,1,1]', (string) $embedding);
    }

    public function testToArray()
    {
        $embedding = new HalfVector([1, 1, 1]);
        $this->assertEquals([1, 1, 1], $embedding->toArray());
    }

    public function testInvalidInteger()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array');

        new HalfVector(1);
    }

    public function testInvalidArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array to be a list');

        new HalfVector(['a' => 1]);
    }

    public function testInvalidString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid text representation');

        new HalfVector('{"a": 1}');
    }

    public function testInvalidJson()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid text representation');

        new HalfVector("tru");
    }

    public function testEmptyArray()
    {
        $embedding = new HalfVector([]);
        $this->assertEquals('[]', (string) $embedding);
    }
}

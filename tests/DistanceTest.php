<?php

use PHPUnit\Framework\TestCase;

use Pgvector\Laravel\Distance;

final class DistanceTest extends TestCase
{
    public function testEveryCaseHasAnOperator()
    {
        foreach (Distance::cases() as $distance) {
            $this->assertIsString($distance->operator());
        }
    }
}
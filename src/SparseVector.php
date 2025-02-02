<?php

namespace Pgvector;

class SparseVector
{
    protected int $dimensions;
    protected array $indices;
    protected array $values;

    public function __construct(mixed $value, mixed $dimensions = null)
    {
        $numArgs = func_num_args();

        if (is_string($value)) {
            if ($numArgs > 1) {
                throw new \InvalidArgumentException("Extra argument");
            }

            $this->fromString($value);
        } else {
            if ($value instanceof \SplFixedArray) {
                $value = $value->toArray();
            }

            if (!is_array($value)) {
                throw new \InvalidArgumentException("Expected array");
            }

            if ($numArgs > 1) {
                $this->fromMap($value, $dimensions);
            } else {
                $this->fromDense($value);
            }
        }
    }

    private function fromDense(array $value): void
    {
        $this->dimensions = count($value);
        $this->indices = [];
        $this->values = [];

        foreach ($value as $i => $v) {
            if ($v != 0) {
                $this->indices[] = intval($i);
                $this->values[] = floatval($v);
            }
        }
    }

    private function fromMap(array $map, mixed $dimensions): void
    {
        $this->dimensions = intval($dimensions);
        $this->indices = [];
        $this->values = [];

        // okay to update in-place since parameter is not a reference
        ksort($map);

        foreach ($map as $i => $v) {
            if ($v != 0) {
                $this->indices[] = intval($i);
                $this->values[] = floatval($v);
            }
        }
    }

    private function fromString(string $value): void
    {
        $parts = explode('/', $value, 2);

        $this->dimensions = intval($parts[1]);
        $this->indices = [];
        $this->values = [];

        $elements = explode(',', substr($parts[0], 1, -1));
        foreach ($elements as $e) {
            $ep = explode(':', $e, 2);
            $this->indices[] = intval($ep[0]) - 1;
            $this->values[] = floatval($ep[1]);
        }
    }

    public function __toString(): string
    {
        $elements = [];
        for ($i = 0; $i < count($this->indices); $i++) {
            $elements[] = ($this->indices[$i] + 1) . ':' . $this->values[$i];
        }
        return '{' . implode(',', $elements) . '}/' . $this->dimensions;
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    public function indices(): array
    {
        return $this->indices;
    }

    public function values(): array
    {
        return $this->values;
    }

    public function toArray(): array
    {
        $result = array_fill(0, $this->dimensions, 0.0);
        for ($i = 0; $i < count($this->indices); $i++) {
            $result[$this->indices[$i]] = $this->values[$i];
        }
        return $result;
    }
}

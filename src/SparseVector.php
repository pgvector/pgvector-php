<?php

namespace Pgvector;

class SparseVector
{
    protected $dimensions;
    protected $indices;
    protected $values;

    public function __construct($value, $dimensions = null)
    {
        if (is_string($value)) {
            $this->fromString($value);
        } elseif (!is_null($dimensions)) {
            $this->fromMap($value, $dimensions);
        } else {
            $this->fromDense($value);
        }
    }

    private function fromDense($value)
    {
        $this->dimensions = count($value);
        $this->indices = [];
        $this->values = [];

        foreach ($value as $i => $v) {
            if ($v != 0) {
                $this->indices[] = $i;
                $this->values[] = floatval($v);
            }
        }
    }

    private function fromMap($map, $dimensions)
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

    private function fromString($value)
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

    public function __toString()
    {
        $elements = [];
        for ($i = 0; $i < count($this->indices); $i++) {
            $elements[] = ($this->indices[$i] + 1) . ':' . $this->values[$i];
        }
        return '{' . implode(',', $elements) . '}/' . $this->dimensions;
    }

    public function dimensions()
    {
        return $this->dimensions;
    }

    public function indices()
    {
        return $this->indices;
    }

    public function values()
    {
        return $this->values;
    }

    public function toArray()
    {
        $result = array_fill(0, $this->dimensions, 0.0);
        for ($i = 0; $i < count($this->indices); $i++) {
            $result[$this->indices[$i]] = $this->values[$i];
        }
        return $result;
    }
}

<?php

namespace Pgvector;

class Vector
{
    protected $value;

    public function __construct($value)
    {
        if (is_string($value)) {
            try {
                $value = json_decode($value, true, 2, JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                // do nothing
            }

            if (!is_array($value)) {
                throw new \InvalidArgumentException("Invalid text representation");
            }
        }
        $this->value = $value;
    }

    public function __toString()
    {
        return json_encode($this->value, JSON_THROW_ON_ERROR, 1);
    }

    public function toArray()
    {
        return $this->value;
    }
}

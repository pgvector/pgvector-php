<?php

namespace Pgvector;

class HalfVector
{
    protected array $value;

    public function __construct(mixed $value)
    {
        if (is_string($value)) {
            try {
                $value = json_decode($value, true, 2, JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                // do nothing
            }

            if (!is_array($value) || !array_is_list($value)) {
                throw new \InvalidArgumentException("Invalid text representation");
            }
        } else {
            if ($value instanceof \SplFixedArray) {
                $value = $value->toArray();
            }

            if (!is_array($value)) {
                throw new \InvalidArgumentException("Expected array");
            }

            if (!array_is_list($value)) {
                throw new \InvalidArgumentException("Expected array to be a list");
            }
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return json_encode($this->value, JSON_THROW_ON_ERROR, 1);
    }

    public function toArray(): array
    {
        return $this->value;
    }
}

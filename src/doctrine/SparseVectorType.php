<?php

namespace Pgvector\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Pgvector\SparseVector;

class SparseVectorType extends Type
{
    public function getName(): string
    {
        return 'sparsevec';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $length = $fieldDeclaration['length'];
        return is_null($length) ? 'sparsevec' : sprintf('sparsevec(%d)', $length);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?SparseVector
    {
        if (is_null($value)) {
            return null;
        }

        return new SparseVector($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!($value instanceof SparseVector)) {
            $value = new SparseVector($value);
        }

        return (string) $value;
    }
}

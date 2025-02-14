<?php

namespace Pgvector\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Pgvector\Vector;

class VectorType extends Type
{
    public function getName(): string
    {
        return 'vector';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $length = $fieldDeclaration['length'];
        return is_null($length) ? 'vector' : sprintf('vector(%d)', $length);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Vector
    {
        if (is_null($value)) {
            return null;
        }

        return new Vector($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!($value instanceof Vector)) {
            $value = new Vector($value);
        }

        return (string) $value;
    }
}

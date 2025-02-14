<?php

namespace Pgvector\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Pgvector\HalfVector;

class HalfVectorType extends Type
{
    public function getName(): string
    {
        return 'halfvec';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $length = $fieldDeclaration['length'];
        return is_null($length) ? 'halfvec' : sprintf('halfvec(%d)', $length);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HalfVector
    {
        if (is_null($value)) {
            return null;
        }

        return new HalfVector($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!($value instanceof HalfVector)) {
            $value = new HalfVector($value);
        }

        return (string) $value;
    }
}

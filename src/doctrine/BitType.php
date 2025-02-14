<?php

namespace Pgvector\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class BitType extends Type
{
    public function getName(): string
    {
        return 'bit';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $length = $fieldDeclaration['length'];
        return is_null($length) ? 'bit' : sprintf('bit(%d)', $length);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return (string) $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return (string) $value;
    }
}

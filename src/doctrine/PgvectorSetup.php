<?php

namespace Pgvector\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

abstract class PgvectorSetup
{
    public static function register(EntityManager $entityManager): void
    {
        self::registerTypes();
        self::registerPlatformTypes($entityManager->getConnection()->getDatabasePlatform());
        self::registerFunctions($entityManager->getConfiguration());
    }

    private static function registerTypes(): void
    {
        Type::addType('vector', 'Pgvector\Doctrine\VectorType');
        Type::addType('halfvec', 'Pgvector\Doctrine\HalfVectorType');
        Type::addType('bit', 'Pgvector\Doctrine\BitType');
        Type::addType('sparsevec', 'Pgvector\Doctrine\SparseVectorType');
    }

    private static function registerPlatformTypes(AbstractPlatform $platform): void
    {
        $platform->registerDoctrineTypeMapping('vector', 'vector');
        $platform->registerDoctrineTypeMapping('halfvec', 'halfvec');
        $platform->registerDoctrineTypeMapping('bit', 'bit');
        $platform->registerDoctrineTypeMapping('sparsevec', 'sparsevec');
    }

    private static function registerFunctions(Configuration $config): void
    {
        $config->addCustomNumericFunction('l2_distance', 'Pgvector\Doctrine\L2Distance');
        $config->addCustomNumericFunction('max_inner_product', 'Pgvector\Doctrine\MaxInnerProduct');
        $config->addCustomNumericFunction('cosine_distance', 'Pgvector\Doctrine\CosineDistance');
        $config->addCustomNumericFunction('l1_distance', 'Pgvector\Doctrine\L1Distance');
        $config->addCustomNumericFunction('hamming_distance', 'Pgvector\Doctrine\HammingDistance');
        $config->addCustomNumericFunction('jaccard_distance', 'Pgvector\Doctrine\JaccardDistance');
    }
}

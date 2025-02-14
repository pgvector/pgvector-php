<?php

use PHPUnit\Framework\TestCase;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Pgvector\HalfVector;
use Pgvector\SparseVector;
use Pgvector\Vector;

require_once __DIR__ . '/models/DoctrineItem.php';

final class DoctrineTest extends TestCase
{
    private static $em;

    public static function setUpBeforeClass(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/models'],
            isDevMode: true
        );

        $connection = DriverManager::getConnection([
            'driver' => 'pgsql',
            'dbname' => 'pgvector_php_test'
        ], $config);

        $entityManager = new EntityManager($connection, $config);

        Type::addType('vector', 'Pgvector\Doctrine\VectorType');
        Type::addType('halfvec', 'Pgvector\Doctrine\HalfVectorType');
        Type::addType('bit', 'Pgvector\Doctrine\BitType');
        Type::addType('sparsevec', 'Pgvector\Doctrine\SparseVectorType');

        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('vector', 'vector');
        $platform->registerDoctrineTypeMapping('halfvec', 'halfvec');
        $platform->registerDoctrineTypeMapping('bit', 'bit');
        $platform->registerDoctrineTypeMapping('sparsevec', 'sparsevec');

        $schemaManager = $entityManager->getConnection()->createSchemaManager();
        try {
            $schemaManager->dropTable('doctrine_items');
        } catch (TableNotFoundException $e) {
            // do nothing
        }

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([$entityManager->getClassMetadata('DoctrineItem')]);

        self::$em = $entityManager;
    }

    public function setUp(): void
    {
        self::$em->getConnection()->executeStatement('TRUNCATE doctrine_items RESTART IDENTITY');
        self::$em->clear();
    }

    public function testTypes()
    {
        $item = new DoctrineItem();
        $item->setEmbedding(new Vector([1, 2, 3]));
        $item->setHalfEmbedding(new HalfVector([4, 5, 6]));
        $item->setBinaryEmbedding('101');
        $item->setSparseEmbedding(new SparseVector([7, 8, 9]));
        self::$em->persist($item);
        self::$em->flush();

        $itemRepository = self::$em->getRepository('DoctrineItem');
        $item = $itemRepository->find(1);
        $this->assertEquals([1, 2, 3], $item->getEmbedding()->toArray());
        $this->assertEquals([4, 5, 6], $item->getHalfEmbedding()->toArray());
        $this->assertEquals('101', $item->getBinaryEmbedding());
        $this->assertEquals([7, 8, 9], $item->getSparseEmbedding()->toArray());
    }

    public function testVectorL2Distance()
    {
        $this->createItems();
        $rsm = new ResultSetMappingBuilder(self::$em);
        $rsm->addRootEntityFromClassMetadata('DoctrineItem', 'i');
        $neighbors = self::$em->createNativeQuery('SELECT * FROM doctrine_items i ORDER BY embedding <-> ? LIMIT 5', $rsm)
            ->setParameter(1, new Vector([1, 1, 1]))
            ->getResult();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v->getId(), $neighbors));
        $this->assertEquals([[1, 1, 1], [1, 1, 2], [2, 2, 2]], array_map(fn ($v) => $v->getEmbedding()->toArray(), $neighbors));
    }

    public function testVectorMaxInnerProduct()
    {
        $this->createItems();
        $rsm = new ResultSetMappingBuilder(self::$em);
        $rsm->addRootEntityFromClassMetadata('DoctrineItem', 'i');
        $neighbors = self::$em->createNativeQuery('SELECT * FROM doctrine_items i ORDER BY embedding <#> ? LIMIT 5', $rsm)
            ->setParameter(1, new Vector([1, 1, 1]))
            ->getResult();
        $this->assertEquals([2, 3, 1], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testVectorCosineDistance()
    {
        $this->createItems();
        $rsm = new ResultSetMappingBuilder(self::$em);
        $rsm->addRootEntityFromClassMetadata('DoctrineItem', 'i');
        $neighbors = self::$em->createNativeQuery('SELECT * FROM doctrine_items i ORDER BY embedding <=> ? LIMIT 5', $rsm)
            ->setParameter(1, new Vector([1, 1, 1]))
            ->getResult();
        $this->assertEquals([1, 2, 3], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testVectorL1Distance()
    {
        $this->createItems();
        $rsm = new ResultSetMappingBuilder(self::$em);
        $rsm->addRootEntityFromClassMetadata('DoctrineItem', 'i');
        $neighbors = self::$em->createNativeQuery('SELECT * FROM doctrine_items i ORDER BY embedding <+> ? LIMIT 5', $rsm)
            ->setParameter(1, new Vector([1, 1, 1]))
            ->getResult();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    private function createItems()
    {
        foreach ([[1, 1, 1], [2, 2, 2], [1, 1, 2]] as $i => $v) {
            $item = new DoctrineItem();
            $item->setEmbedding(new Vector($v));
            self::$em->persist($item);
        }
        self::$em->flush();
    }
}

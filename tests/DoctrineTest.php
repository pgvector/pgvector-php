<?php

use PHPUnit\Framework\TestCase;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Pgvector\Doctrine\PgvectorSetup;
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
        PgvectorSetup::registerFunctions($config);

        $connection = DriverManager::getConnection([
            'driver' => 'pgsql',
            'dbname' => 'pgvector_php_test'
        ], $config);

        $entityManager = new EntityManager($connection, $config);
        $entityManager->getConnection()->executeStatement('CREATE EXTENSION IF NOT EXISTS vector');
        PgvectorSetup::registerTypes($entityManager);

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
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY l2_distance(i.embedding, ?1)')
            ->setParameter(1, new Vector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v->getId(), $neighbors));
        $this->assertEquals([[1, 1, 1], [1, 1, 2], [2, 2, 2]], array_map(fn ($v) => $v->getEmbedding()->toArray(), $neighbors));
    }

    public function testVectorMaxInnerProduct()
    {
        $this->createItems();
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY max_inner_product(i.embedding, ?1)')
            ->setParameter(1, new Vector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([2, 3, 1], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testVectorCosineDistance()
    {
        $this->createItems();
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY cosine_distance(i.embedding, ?1)')
            ->setParameter(1, new Vector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 2, 3], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testVectorL1Distance()
    {
        $this->createItems();
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY l1_distance(i.embedding, ?1)')
            ->setParameter(1, new Vector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testHalfvecL2Distance()
    {
        $this->createItems('halfEmbedding');
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY l2_distance(i.halfEmbedding, ?1)')
            ->setParameter(1, new HalfVector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v->getId(), $neighbors));
        $this->assertEquals([[1, 1, 1], [1, 1, 2], [2, 2, 2]], array_map(fn ($v) => $v->getHalfEmbedding()->toArray(), $neighbors));
    }

    public function testHalfvecMaxInnerProduct()
    {
        $this->createItems('halfEmbedding');
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY max_inner_product(i.halfEmbedding, ?1)')
            ->setParameter(1, new HalfVector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([2, 3, 1], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testHalfvecCosineDistance()
    {
        $this->createItems('halfEmbedding');
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY cosine_distance(i.halfEmbedding, ?1)')
            ->setParameter(1, new HalfVector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 2, 3], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testHalfvecL1Distance()
    {
        $this->createItems('halfEmbedding');
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY l1_distance(i.halfEmbedding, ?1)')
            ->setParameter(1, new HalfVector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testBitHammingDistance()
    {
        $this->createBitItems();
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY hamming_distance(i.binaryEmbedding, ?1)')
            ->setParameter(1, '101')
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([2, 3, 1], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testBitJaccardDistance()
    {
        $this->createBitItems();
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY jaccard_distance(i.binaryEmbedding, ?1)')
            ->setParameter(1, '101')
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([2, 3, 1], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testSparsevecL2Distance()
    {
        $this->createItems('sparseEmbedding');
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY l2_distance(i.sparseEmbedding, ?1)')
            ->setParameter(1, new SparseVector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v->getId(), $neighbors));
        $this->assertEquals([[1, 1, 1], [1, 1, 2], [2, 2, 2]], array_map(fn ($v) => $v->getSparseEmbedding()->toArray(), $neighbors));
    }

    public function testSparsevecMaxInnerProduct()
    {
        $this->createItems('sparseEmbedding');
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY max_inner_product(i.sparseEmbedding, ?1)')
            ->setParameter(1, new SparseVector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([2, 3, 1], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testSparsevecCosineDistance()
    {
        $this->createItems('sparseEmbedding');
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY cosine_distance(i.sparseEmbedding, ?1)')
            ->setParameter(1, new SparseVector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 2, 3], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    public function testSparsevecL1Distance()
    {
        $this->createItems('sparseEmbedding');
        $neighbors = self::$em->createQuery('SELECT i FROM DoctrineItem i ORDER BY l1_distance(i.sparseEmbedding, ?1)')
            ->setParameter(1, new SparseVector([1, 1, 1]))
            ->setMaxResults(5)
            ->getResult();
        $this->assertEquals([1, 3, 2], array_map(fn ($v) => $v->getId(), $neighbors));
    }

    private function createItems($attribute = 'embedding')
    {
        foreach ([[1, 1, 1], [2, 2, 2], [1, 1, 2]] as $v) {
            $item = new DoctrineItem();
            if ($attribute == 'halfEmbedding') {
                $item->setHalfEmbedding(new HalfVector($v));
            } else if ($attribute == 'sparseEmbedding') {
                $item->setSparseEmbedding(new SparseVector($v));
            } else {
                $item->setEmbedding(new Vector($v));
            }
            self::$em->persist($item);
        }
        self::$em->flush();
    }

    private function createBitItems()
    {
        foreach (['000', '101', '111'] as $v) {
            $item = new DoctrineItem();
            $item->setBinaryEmbedding($v);
            self::$em->persist($item);
        }
        self::$em->flush();
    }
}

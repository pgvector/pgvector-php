<?php

use PHPUnit\Framework\TestCase;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
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
}

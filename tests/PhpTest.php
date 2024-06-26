<?php

use PHPUnit\Framework\TestCase;

use Pgvector\HalfVector;
use Pgvector\SparseVector;
use Pgvector\Vector;

final class PhpTest extends TestCase
{
    public function testWorks()
    {
        $db = pg_connect('postgres://localhost/pgvector_php_test');

        pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
        pg_query($db, 'DROP TABLE IF EXISTS items');
        pg_query($db, 'CREATE TABLE items (id bigserial primary key, embedding vector(3), half_embedding halfvec(3), binary_embedding bit(3), sparse_embedding sparsevec(3))');

        $embedding1 = new Vector([1, 1, 1]);
        $embedding2 = new Vector([2, 2, 2]);
        $embedding3 = new Vector([1, 1, 2]);
        $halfEmbedding1 = new HalfVector([1, 1, 1]);
        $halfEmbedding2 = new HalfVector([2, 2, 2]);
        $halfEmbedding3 = new HalfVector([1, 1, 2]);
        $binaryEmbedding1 = '000';
        $binaryEmbedding2 = '101';
        $binaryEmbedding3 = '111';
        $sparseEmbedding1 = new SparseVector([1, 1, 1]);
        $sparseEmbedding2 = new SparseVector([2, 2, 2]);
        $sparseEmbedding3 = new SparseVector([1, 1, 2]);
        pg_query_params($db, 'INSERT INTO items (embedding, half_embedding, binary_embedding, sparse_embedding) VALUES ($1, $2, $3, $4), ($5, $6, $7, $8), ($9, $10, $11, $12)', [$embedding1, $halfEmbedding1, $binaryEmbedding1, $sparseEmbedding1, $embedding2, $halfEmbedding2, $binaryEmbedding2, $sparseEmbedding2, $embedding3, $halfEmbedding3, $binaryEmbedding3, $sparseEmbedding3]);

        $ids = [];
        $embeddings = [];
        $halfEmbeddings = [];
        $binaryEmbeddings = [];
        $sparseEmbeddings = [];
        $embedding = new Vector([1, 1, 1]);
        $result = pg_query_params($db, 'SELECT * FROM items ORDER BY embedding <-> $1 LIMIT 5', [$embedding]);
        while ($row = pg_fetch_array($result)) {
            $ids[] = $row['id'];
            $embeddings[] = $row['embedding'];
            $halfEmbeddings[] = $row['half_embedding'];
            $binaryEmbeddings[] = $row['binary_embedding'];
            $sparseEmbeddings[] = $row['sparse_embedding'];
        }
        pg_free_result($result);

        $this->assertEquals([1, 3, 2], $ids);
        $this->assertEquals(['[1,1,1]', '[1,1,2]', '[2,2,2]'], $embeddings);
        $this->assertEquals([1, 1, 1], (new Vector($embeddings[0]))->toArray());
        $this->assertEquals([1, 1, 1], (new HalfVector($halfEmbeddings[0]))->toArray());
        $this->assertEquals('000', $binaryEmbeddings[0]);
        $this->assertEquals([1, 1, 1], (new SparseVector($sparseEmbeddings[0]))->toArray());

        $rows = [$embedding1, $embedding2, $embedding3];
        pg_copy_from($db, 'items (embedding)', $rows);

        pg_close($db);
    }
}

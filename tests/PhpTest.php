<?php

use PHPUnit\Framework\TestCase;

use Pgvector\Vector;

final class PhpTest extends TestCase
{
    public function testWorks()
    {
        $db = pg_connect('postgres://localhost/pgvector_php_test');

        pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
        pg_query($db, 'DROP TABLE IF EXISTS items');
        pg_query($db, 'CREATE TABLE items (id bigserial primary key, embedding vector(3))');

        $embedding1 = new Vector([1, 1, 1]);
        $embedding2 = new Vector([2, 2, 2]);
        $embedding3 = new Vector([1, 1, 2]);
        pg_query_params($db, 'INSERT INTO items (embedding) VALUES ($1), ($2), ($3)', [$embedding1, $embedding2, $embedding3]);

        $ids = [];
        $embeddings = [];
        $embedding = new Vector([1, 1, 1]);
        $result = pg_query_params($db, 'SELECT * FROM items ORDER BY embedding <-> $1 LIMIT 5', [$embedding]);
        while ($row = pg_fetch_array($result)) {
            $ids[] = $row['id'];
            $embeddings[] = $row['embedding'];
        }
        pg_free_result($result);

        $this->assertEquals([1, 3, 2], $ids);
        $this->assertEquals(['[1,1,1]', '[1,1,2]', '[2,2,2]'], $embeddings);
        $this->assertEquals([1, 1, 1], (new Vector($embeddings[0]))->toArray());

        pg_close($db);
    }
}

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pgvector\Vector;

$db = pg_connect('postgres://localhost/pgvector_php_test');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS items');
pg_query($db, 'CREATE TABLE items (id bigserial primary key, embedding vector(3))');

$embedding1 = new Vector([1, 1, 1]);
$embedding2 = new Vector([2, 2, 2]);
$embedding3 = new Vector([1, 1, 2]);
pg_query_params($db, 'INSERT INTO items (embedding) VALUES ($1), ($2), ($3)', [$embedding1, $embedding2, $embedding3]);

$embedding = new Vector([1, 1, 1]);
$result = pg_query_params($db, 'SELECT * FROM items ORDER BY embedding <-> $1 LIMIT 5', [$embedding]);
while ($row = pg_fetch_array($result)) {
    echo $row['id'] . ': ' . new Vector($row['embedding']) . "\n";
}
pg_free_result($result);

pg_close($db);

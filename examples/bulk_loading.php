<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pgvector\Vector;

ini_set('memory_limit', '1G');

// generate random data
$rows = 100000;
$dimensions = 128;
$embeddings = [];
for ($i = 0; $i < $rows; $i++) {
    $embedding = [];
    for ($j = 0; $j < $dimensions; $j++) {
        $embedding[] = rand() / getrandmax();
    }
    $embeddings[] = $embedding;
}

// enable extension
$db = pg_connect('postgres://localhost/pgvector_example');
pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');

// create table
pg_query($db, 'DROP TABLE IF EXISTS items');
pg_query($db, 'CREATE TABLE items (id bigserial, embedding vector(128))');

// load data
echo "Loading $rows rows\n";
$rows = array_map(fn ($e) => new Vector($e), $embeddings);
pg_copy_from($db, 'items (embedding)', $rows);
echo "Success!\n";

// create any indexes *after* loading initial data (skipping for this example)
$createIndex = false;
if ($createIndex) {
    echo "Creating index\n";
    pg_query($db, "SET maintenance_work_mem = '8GB'");
    pg_query($db, 'SET max_parallel_maintenance_workers = 7');
    pg_query($db, 'CREATE INDEX ON items USING hnsw (embedding vector_cosine_ops)');
}

// update planner statistics for good measure
pg_query($db, 'ANALYZE items');

pg_close($db);

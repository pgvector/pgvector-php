<?php

require_once __DIR__ . '/vendor/autoload.php';

use Pgvector\Vector;

ini_set('memory_limit', '512M');

// generate random data
$rows = 100000;
$dimensions = 128;
$embeddings = [];
$categories = [];
for ($i = 0; $i < $rows; $i++) {
    $embedding = [];
    for ($j = 0; $j < $dimensions; $j++) {
        $embedding[] = rand() / getrandmax();
    }
    $embeddings[] = $embedding;
    $categories[] = rand(1, 100);
}

// enable extensions
$db = pg_connect('postgres://localhost/pgvector_citus');
pg_query($db, 'CREATE EXTENSION IF NOT EXISTS citus');
pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');

// GUC variables set on the session do not propagate to Citus workers
// https://github.com/citusdata/citus/issues/462
// you can either:
// 1. set them on the system, user, or database and reconnect
// 2. set them for a transaction with SET LOCAL
pg_query($db, "ALTER DATABASE pgvector_citus SET maintenance_work_mem = '512MB'");
pg_query($db, 'ALTER DATABASE pgvector_citus SET hnsw.ef_search = 20');
pg_close($db);

// reconnect for updated GUC variables to take effect
$db = pg_connect('postgres://localhost/pgvector_citus');

echo "Creating distributed table\n";
pg_query($db, 'DROP TABLE IF EXISTS items');
pg_query($db, 'CREATE TABLE items (id bigserial, embedding vector(128), category_id bigint, PRIMARY KEY (id, category_id))');
pg_query($db, 'SET citus.shard_count = 4');
pg_query($db, "SELECT create_distributed_table('items', 'category_id')");

echo "Loading data in parallel\n";

pg_query($db, 'COPY items (embedding, category_id) FROM STDIN');
foreach ($embeddings as $i => $e) {
    $row = [new Vector($e), $categories[$i]];
    $line = join("\t", array_map(fn ($v) => pg_escape_string($db, $v), $row)) . "\n";
    pg_put_line($db, $line);
}
pg_put_line($db, "\\.\n");
pg_end_copy($db);

echo "Creating index in parallel\n";
pg_query($db, 'CREATE INDEX ON items USING hnsw (embedding vector_l2_ops)');

echo "Running distributed queries\n";
for ($i = 0; $i < 10; $i++) {
    $result = pg_query_params($db, 'SELECT id FROM items ORDER BY embedding <-> $1 LIMIT 10', [new Vector($embeddings[rand(0, $rows - 1)])]);
    $ids = [];
    while ($row = pg_fetch_array($result)) {
        $ids[] = $row['id'];
    }
    echo join(', ', $ids) . "\n";
    pg_free_result($result);
}

pg_close($db);

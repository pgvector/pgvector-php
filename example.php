<?php

$db = pg_connect('postgres://localhost/pgvector_php_test');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS items');
pg_query($db, 'CREATE TABLE items (id bigserial primary key, factors vector(3))');

pg_query_params($db, 'INSERT INTO items (factors) VALUES ($1), ($2), ($3)', array('[1,1,1]', '[2,2,2]', '[1,1,2]'));

$result = pg_query_params($db, 'SELECT * FROM items ORDER BY factors <-> $1 LIMIT 5', array('[1,1,1]'));
while ($row = pg_fetch_array($result)) {
    echo $row['id'] . ': ' . $row['factors'] . "\n";
}
pg_free_result($result);

pg_close($db);

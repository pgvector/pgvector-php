<?php

require_once __DIR__ . '/vendor/autoload.php';

use Pgvector\Vector;

$db = pg_connect('postgres://localhost/pgvector_php_test');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS users');
pg_query($db, 'DROP TABLE IF EXISTS movies');
pg_query($db, 'CREATE TABLE users (id integer PRIMARY KEY, factors vector(20))');
pg_query($db, 'CREATE TABLE movies (name text PRIMARY KEY, factors vector(20))');

$data = Disco\Data::loadMovieLens();
$recommender = new Disco\Recommender(factors: 20);
$recommender->fit($data);

foreach ($recommender->userIds() as $userId) {
    pg_query_params($db, 'INSERT INTO users (id, factors) VALUES ($1, $2)', [$userId, new Vector($recommender->userFactors($userId))]);
}

foreach ($recommender->itemIds() as $itemId) {
    $name = mb_convert_encoding($itemId, 'UTF-8', 'ISO-8859-1'); // fix encoding
    pg_query_params($db, 'INSERT INTO movies (name, factors) VALUES ($1, $2)', [$name, new Vector($recommender->itemFactors($itemId))]);
}

$movie = 'Star Wars (1977)';
echo "Item-based recommendations for $movie\n";
$result = pg_query_params($db, 'SELECT name FROM movies WHERE name != $1 ORDER BY factors <=> (SELECT factors FROM movies WHERE name = $1) LIMIT 5', [$movie]);
while ($row = pg_fetch_array($result)) {
    echo $row['name'] . "\n";
}

$userId = 123;
echo "\nUser-based recommendations for user $userId\n";
$result = pg_query_params($db, 'SELECT name FROM movies ORDER BY factors <#> (SELECT factors FROM users WHERE id = $1) LIMIT 5', [$userId]);
while ($row = pg_fetch_array($result)) {
    echo $row['name'] . "\n";
}

pg_free_result($result);
pg_close($db);

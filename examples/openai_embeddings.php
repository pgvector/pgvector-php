<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pgvector\Vector;

$db = pg_connect('postgres://localhost/pgvector_php_test');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS articles');
pg_query($db, 'CREATE TABLE articles (id bigserial primary key, content text, embedding vector(1536))');

function fetchEmbeddings($input)
{
    $url = 'https://api.openai.com/v1/embeddings';
    $data = [
        'input' => $input,
        'model' => 'text-embedding-ada-002'
    ];
    $apiKey = getenv('OPENAI_API_KEY');
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $apiKey\r\nContent-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    return array_map(fn ($v) => $v['embedding'], json_decode($response, true)['data']);
}

$input = [
  'The dog is barking',
  'The cat is purring',
  'The bear is growling'
];
$embeddings = fetchEmbeddings($input);

foreach ($input as $i => $content) {
    pg_query_params($db, 'INSERT INTO articles (content, embedding) VALUES ($1, $2)', [$content, new Vector($embeddings[$i])]);
}

$articleId = 2;
$result = pg_query_params($db, 'SELECT * FROM articles WHERE id != $1 ORDER BY embedding <=> (SELECT embedding FROM articles WHERE id = $1) LIMIT 5', [$articleId]);
while ($row = pg_fetch_array($result)) {
    echo $row['content'] . "\n";
}

pg_free_result($result);
pg_close($db);

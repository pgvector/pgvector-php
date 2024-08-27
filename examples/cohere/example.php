<?php

require_once __DIR__ . '/vendor/autoload.php';

$db = pg_connect('postgres://localhost/pgvector_example');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS documents');
pg_query($db, 'CREATE TABLE documents (id bigserial PRIMARY KEY, content text, embedding bit(1024))');

// https://docs.cohere.com/reference/embed
function fetchEmbeddings($texts, $inputType)
{
    $apiKey = getenv('CO_API_KEY');
    $url = 'https://api.cohere.com/v1/embed';
    $data = [
        'texts' => $texts,
        'model' => 'embed-english-v3.0',
        'input_type' => $inputType,
        'embedding_types' => ['ubinary']
    ];
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $apiKey\r\nContent-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    return array_map(fn ($e) => implode(array_map(fn ($v) => str_pad(decbin($v), 8, '0', STR_PAD_LEFT), $e)), json_decode($response, true)['embeddings']['ubinary']);
}

$input = [
  'The dog is barking',
  'The cat is purring',
  'The bear is growling'
];
$embeddings = fetchEmbeddings($input, 'search_document');
foreach ($input as $i => $content) {
    pg_query_params($db, 'INSERT INTO documents (content, embedding) VALUES ($1, $2)', [$content, $embeddings[$i]]);
}

$query = 'forest';
$queryEmbedding = fetchEmbeddings([$query], 'search_query')[0];
$result = pg_query_params($db, 'SELECT * FROM documents ORDER BY embedding <~> $1 LIMIT 5', [$queryEmbedding]);
while ($row = pg_fetch_array($result)) {
    echo $row['content'] . "\n";
}

pg_free_result($result);
pg_close($db);

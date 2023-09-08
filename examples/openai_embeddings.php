<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pgvector\Vector;

$db = pg_connect('postgres://localhost/pgvector_example');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS documents');
pg_query($db, 'CREATE TABLE documents (id bigserial PRIMARY KEY, content text, embedding vector(1536))');

function fetchEmbeddings($input)
{
    $apiKey = getenv('OPENAI_API_KEY');
    $url = 'https://api.openai.com/v1/embeddings';
    $data = [
        'input' => $input,
        'model' => 'text-embedding-ada-002'
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
    return array_map(fn ($v) => $v['embedding'], json_decode($response, true)['data']);
}

$input = [
  'The dog is barking',
  'The cat is purring',
  'The bear is growling'
];
$embeddings = fetchEmbeddings($input);

foreach ($input as $i => $content) {
    pg_query_params($db, 'INSERT INTO documents (content, embedding) VALUES ($1, $2)', [$content, new Vector($embeddings[$i])]);
}

$documentId = 2;
$result = pg_query_params($db, 'SELECT * FROM documents WHERE id != $1 ORDER BY embedding <=> (SELECT embedding FROM documents WHERE id = $1) LIMIT 5', [$documentId]);
while ($row = pg_fetch_array($result)) {
    echo $row['content'] . "\n";
}

pg_free_result($result);
pg_close($db);

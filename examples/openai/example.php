<?php

require_once __DIR__ . '/vendor/autoload.php';

use Pgvector\Vector;

$db = pg_connect('postgres://localhost/pgvector_example');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS documents');
pg_query($db, 'CREATE TABLE documents (id bigserial PRIMARY KEY, content text, embedding vector(1536))');

function embed($input)
{
    $apiKey = getenv('OPENAI_API_KEY') or die("Set OPENAI_API_KEY\n");
    $url = 'https://api.openai.com/v1/embeddings';
    $data = [
        'input' => $input,
        'model' => 'text-embedding-3-small'
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
$embeddings = embed($input);
foreach ($input as $i => $content) {
    pg_query_params($db, 'INSERT INTO documents (content, embedding) VALUES ($1, $2)', [$content, new Vector($embeddings[$i])]);
}

$query = 'forest';
$queryEmbedding = embed([$query])[0];
$result = pg_query_params($db, 'SELECT * FROM documents ORDER BY embedding <=> $1 LIMIT 5', [new Vector($queryEmbedding)]);
while ($row = pg_fetch_array($result)) {
    echo $row['content'] . "\n";
}

pg_free_result($result);
pg_close($db);

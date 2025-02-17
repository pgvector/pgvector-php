<?php

// good resources
// https://opensearch.org/blog/improving-document-retrieval-with-sparse-semantic-encoders/
// https://huggingface.co/opensearch-project/opensearch-neural-sparse-encoding-v1
//
// run with
// text-embeddings-router --model-id opensearch-project/opensearch-neural-sparse-encoding-v1 --pooling splade

require_once __DIR__ . '/vendor/autoload.php';

use Pgvector\SparseVector;

$db = pg_connect('postgres://localhost/pgvector_example');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS documents');
pg_query($db, 'CREATE TABLE documents (id bigserial PRIMARY KEY, content text, embedding sparsevec(30522))');

function embed($inputs)
{
    $url = 'http://localhost:3000/embed_sparse';
    $data = [
        'inputs' => $inputs
    ];
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    $embeddings = [];
    foreach (json_decode($response, true) as $item) {
        $embedding = [];
        foreach ($item as $e) {
            $embedding[$e['index']] = $e['value'];
        }
        $embeddings[] = $embedding;
    }
    return $embeddings;
}

$input = [
  'The dog is barking',
  'The cat is purring',
  'The bear is growling'
];
$embeddings = embed($input);
foreach ($input as $i => $content) {
    pg_query_params($db, 'INSERT INTO documents (content, embedding) VALUES ($1, $2)', [$content, new SparseVector($embeddings[$i], 30522)]);
}

$query = 'forest';
$queryEmbedding = embed([$query])[0];
$result = pg_query_params($db, 'SELECT content FROM documents ORDER BY embedding <#> $1 LIMIT 5', [new SparseVector($queryEmbedding, 30522)]);
while ($row = pg_fetch_array($result)) {
    echo $row['content'] . "\n";
}

pg_free_result($result);
pg_close($db);

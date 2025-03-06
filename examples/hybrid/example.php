<?php

require_once __DIR__ . '/vendor/autoload.php';

use Pgvector\Vector;

$db = pg_connect('postgres://localhost/pgvector_example');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS documents');
pg_query($db, 'CREATE TABLE documents (id bigserial PRIMARY KEY, content text, embedding vector(768))');
pg_query($db, "CREATE INDEX ON documents USING GIN (to_tsvector('english', content))");

function embed($input, $taskType)
{
    // nomic-embed-text uses a task prefix
    // https://huggingface.co/nomic-ai/nomic-embed-text-v1.5
    $input = array_map(fn ($v) => $taskType . ': ' . $v, $input);

    $url = 'http://localhost:11434/api/embed';
    $data = [
        'input' => $input,
        'model' => 'nomic-embed-text'
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
    return json_decode($response, true)['embeddings'];
}

$input = [
    'The dog is barking',
    'The cat is purring',
    'The bear is growling'
];
$embeddings = embed($input, 'search_document');

foreach ($input as $i => $content) {
    pg_query_params($db, 'INSERT INTO documents (content, embedding) VALUES ($1, $2)', [$content, new Vector($embeddings[$i])]);
}

$sql = <<<SQL
WITH semantic_search AS (
    SELECT id, RANK () OVER (ORDER BY embedding <=> $2) AS rank
    FROM documents
    ORDER BY embedding <=> $2
    LIMIT 20
),
keyword_search AS (
    SELECT id, RANK () OVER (ORDER BY ts_rank_cd(to_tsvector('english', content), query) DESC)
    FROM documents, plainto_tsquery('english', $1) query
    WHERE to_tsvector('english', content) @@ query
    ORDER BY ts_rank_cd(to_tsvector('english', content), query) DESC
    LIMIT 20
)
SELECT
    COALESCE(semantic_search.id, keyword_search.id) AS id,
    COALESCE(1.0 / ($3 + semantic_search.rank), 0.0) +
    COALESCE(1.0 / ($3 + keyword_search.rank), 0.0) AS score
FROM semantic_search
FULL OUTER JOIN keyword_search ON semantic_search.id = keyword_search.id
ORDER BY score DESC
LIMIT 5
SQL;
$query = 'growling bear';
$queryEmbedding = embed([$query], 'search_query')[0];
$k = 60;
$result = pg_query_params($db, $sql, [$query, new Vector($queryEmbedding), $k]);
while ($row = pg_fetch_array($result)) {
    echo 'document: ' . $row['id'] . ', RRF score: ' . $row['score'] . "\n";
}

pg_free_result($result);
pg_close($db);

<?php

require_once __DIR__ . '/vendor/autoload.php';

function generateFingerprint($molecule)
{
    return RDKit\Molecule::fromSmiles($molecule)->morganFingerprint();
}

$db = pg_connect('postgres://localhost/pgvector_example');

pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
pg_query($db, 'DROP TABLE IF EXISTS molecules');
pg_query($db, 'CREATE TABLE molecules (id text PRIMARY KEY, fingerprint bit(2048))');

$molecules = ['Cc1ccccc1', 'Cc1ncccc1', 'c1ccccn1'];
foreach ($molecules as $molecule) {
    $fingerprint = generateFingerprint($molecule);
    pg_query_params($db, 'INSERT INTO molecules (id, fingerprint) VALUES ($1, $2)', [$molecule, $fingerprint]);
}

$queryMolecule = 'c1ccco1';
$queryFingerprint = generateFingerprint($queryMolecule);
$result = pg_query_params($db, 'SELECT id, fingerprint <%> $1 AS distance FROM molecules ORDER BY distance LIMIT 5', [$queryFingerprint]);
while ($row = pg_fetch_array($result)) {
    echo $row['id'] . ': ' . $row['distance'] . "\n";
}

pg_free_result($result);
pg_close($db);

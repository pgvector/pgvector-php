# pgvector-php

[pgvector](https://github.com/pgvector/pgvector) support for PHP

Supports [Laravel](https://github.com/laravel/laravel), [Doctrine](https://github.com/doctrine/orm), and [PgSql](https://www.php.net/manual/en/book.pgsql.php)

[![Build Status](https://github.com/pgvector/pgvector-php/actions/workflows/build.yml/badge.svg)](https://github.com/pgvector/pgvector-php/actions)

## Getting Started

Follow the instructions for your database library:

- [Laravel](#laravel)
- [Doctrine](#doctrine)
- [PgSql](#pgsql)

Or check out some examples:

- [Embeddings](examples/openai/example.php) with OpenAI
- [Binary embeddings](examples/cohere/example.php) with Cohere
- [Hybrid search](examples/hybrid/example.php) with Ollama (Reciprocal Rank Fusion)
- [Sparse search](examples/sparse/example.php) with Text Embeddings Inference
- [Morgan fingerprints](examples/rdkit/example.php) with RDKit
- [Recommendations](examples/disco/example.php) with Disco
- [Horizontal scaling](examples/citus/example.php) with Citus
- [Bulk loading](examples/loading/example.php) with `COPY`

### Laravel

Install the package

```sh
composer require pgvector/pgvector
```

Enable the extension

```sh
php artisan vendor:publish --tag="pgvector-migrations"
php artisan migrate
```

You can now use the `vector` type in future migrations

```php
Schema::create('items', function (Blueprint $table) {
    $table->vector('embedding', 3);
});
```

Update your model

```php
use Pgvector\Laravel\Vector;

class Item extends Model
{
    use HasNeighbors;

    protected $casts = ['embedding' => Vector::class];
}
```

Insert a vector

```php
$item = new Item();
$item->embedding = [1, 2, 3];
$item->save();
```

Get the nearest neighbors to a record

```php
use Pgvector\Laravel\Distance;

$neighbors = $item->nearestNeighbors('embedding', Distance::L2)->take(5)->get();
```

Also supports `InnerProduct`, `Cosine`, `L1`, `Hamming`, and `Jaccard` distance

Get the nearest neighbors to a vector

```php
$neighbors = Item::query()->nearestNeighbors('embedding', [1, 2, 3], Distance::L2)->take(5)->get();
```

Get the distances

```php
$neighbors->pluck('neighbor_distance');
```

Add an approximate index in a migration

```php
public function up()
{
    DB::statement('CREATE INDEX my_index ON items USING hnsw (embedding vector_l2_ops)');
    // or
    DB::statement('CREATE INDEX my_index ON items USING ivfflat (embedding vector_l2_ops) WITH (lists = 100)');
}

public function down()
{
    DB::statement('DROP INDEX my_index');
}
```

Use `vector_ip_ops` for inner product and `vector_cosine_ops` for cosine distance

### Doctrine

Install the package

```sh
composer require pgvector/pgvector
```

Register the types and distance functions

```php
use Pgvector\Doctrine\PgvectorSetup;

PgvectorSetup::registerTypes($entityManager);
```

Enable the extension

```php
$entityManager->getConnection()->executeStatement('CREATE EXTENSION IF NOT EXISTS vector');
```

Update your model

```php
use Pgvector\Vector;

#[ORM\Entity]
class Item
{
    #[ORM\Column(type: 'vector', length: 3)]
    private Vector $embedding;

    public function setEmbedding(Vector $embedding): void
    {
        $this->embedding = $embedding;
    }
}
```

Insert a vector

```php
$item = new Item();
$item->setEmbedding(new Vector([1, 2, 3]));
$entityManager->persist($item);
$entityManager->flush();
```

Get the nearest neighbors to a vector

```php
$neighbors = $entityManager->createQuery('SELECT i FROM Item i ORDER BY l2_distance(i.embedding, ?1)')
    ->setParameter(1, new Vector([1, 2, 3]))
    ->setMaxResults(5)
    ->getResult();
```

Also supports `max_inner_product`, `cosine_distance`, `l1_distance`, `hamming_distance`, and `jaccard_distance`

### PgSql

Enable the extension

```php
pg_query($db, 'CREATE EXTENSION IF NOT EXISTS vector');
```

Create a table

```php
pg_query($db, 'CREATE TABLE items (embedding vector(3))');
```

Insert a vector

```php
use Pgvector\Vector;

$embedding = new Vector([1, 2, 3]);
pg_query_params($db, 'INSERT INTO items (embedding) VALUES ($1)', [$embedding]);
```

Get the nearest neighbors to a vector

```php
$embedding = new Vector([1, 2, 3]);
$result = pg_query_params($db, 'SELECT * FROM items ORDER BY embedding <-> $1 LIMIT 5', [$embedding]);
```

Add an approximate index

```php
pg_query($db, 'CREATE INDEX ON items USING hnsw (embedding vector_l2_ops)');
// or
pg_query($db, 'CREATE INDEX ON items USING ivfflat (embedding vector_l2_ops) WITH (lists = 100)');
```

See a [full example](examples/pgsql/example.php)

## Reference

### Vectors

Create a vector from an array

```php
$vec = new Vector([1, 2, 3]);
```

Get an array

```php
$arr = $vec->toArray();
```

### Half Vectors

Create a half vector from an array

```php
$vec = new HalfVector([1, 2, 3]);
```

Get an array

```php
$arr = $vec->toArray();
```

### Sparse Vectors

Create a sparse vector from an indexed array

```php
$vec = new SparseVector([1, 0, 2, 0, 3, 0]);
```

Or an associative array of non-zero elements

```php
$elements = [0 => 1, 2 => 2, 4 => 3];
$vec = new SparseVector($elements, 6);
```

Note: Indices start at 0

Get the number of dimensions

```php
$dim = $vec->dimensions();
```

Get the indices of non-zero elements

```php
$indices = $vec->indices();
```

Get the values of non-zero elements

```php
$values = $vec->values();
```

Get an array

```php
$arr = $vec->toArray();
```

## Upgrading

### 0.1.4

The package name was changed from `ankane/pgvector` to `pgvector/pgvector`. Update it in `composer.json` to remove the message.

## History

View the [changelog](https://github.com/pgvector/pgvector-php/blob/master/CHANGELOG.md)

## Contributing

Everyone is encouraged to help improve this project. Here are a few ways you can help:

- [Report bugs](https://github.com/pgvector/pgvector-php/issues)
- Fix bugs and [submit pull requests](https://github.com/pgvector/pgvector-php/pulls)
- Write, clarify, or fix documentation
- Suggest or add new features

To get started with development:

```sh
git clone https://github.com/pgvector/pgvector-php.git
cd pgvector-php
composer install
createdb pgvector_php_test
composer test
```

To run an example:

```sh
cd examples/loading
composer install
createdb pgvector_example
php example.php
```

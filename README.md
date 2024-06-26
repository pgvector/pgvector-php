# pgvector-php

[pgvector](https://github.com/pgvector/pgvector) support for PHP

[![Build Status](https://github.com/pgvector/pgvector-php/actions/workflows/build.yml/badge.svg)](https://github.com/pgvector/pgvector-php/actions)

## Getting Started

Follow the instructions for your database library:

- [Laravel](#laravel)
- [PHP](#php)

Or check out some examples:

- [Embeddings](examples/openai_embeddings.php) with OpenAI
- [Recommendations](examples/disco/example.php) with Disco

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

### PHP

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

See a [full example](examples/pgsql.php)

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

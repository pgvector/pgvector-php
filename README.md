# pgvector-php

[pgvector](https://github.com/pgvector/pgvector) support for PHP

[![Build Status](https://github.com/pgvector/pgvector-php/workflows/build/badge.svg?branch=master)](https://github.com/pgvector/pgvector-php/actions)

## Getting Started

Follow the instructions for your database library:

- [Laravel](#laravel)
- [PHP](#php)

Or check out an example:

- [Embeddings](examples/openai_embeddings.php) with OpenAI

### Laravel

Install the package

```sh
composer require ankane/pgvector
```

Create the `vector` extension

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
    protected $casts = ['embedding' => Vector::class];
}
```

Insert a vector

```php
$item = new Item();
$item->embedding = [1, 2, 3];
$item->save();
```

Get the nearest neighbors

```php
use Pgvector\Laravel\Vector;

$embedding = new Vector([1, 2, 3]);
$neighbors = Item::orderByRaw('embedding <-> ?', [$embedding])->take(5)->get();
```

Get the distances

```php
$embedding = new Vector([1, 2, 3]);
$distances = Item::selectRaw('embedding <-> ? AS distance', [$embedding])->pluck('distance');
```

Add an approximate index in a migration

```php
public function up()
{
    DB::statement('CREATE INDEX my_index ON items USING ivfflat (embedding vector_l2_ops) WITH (lists = 100)');
    // or
    DB::statement('CREATE INDEX my_index ON items USING hnsw (embedding vector_l2_ops)');
}

public function down()
{
    DB::statement('DROP INDEX my_index');
}
```

Use `vector_ip_ops` for inner product and `vector_cosine_ops` for cosine distance

### PHP

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

See a [full example](examples/pgsql.php)

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

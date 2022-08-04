# pgvector-php

[pgvector](https://github.com/pgvector/pgvector) support for PHP

[![Build Status](https://github.com/pgvector/pgvector-php/workflows/build/badge.svg?branch=master)](https://github.com/pgvector/pgvector-php/actions)

## Getting Started

Follow the instructions for your database library:

- [Laravel](#laravel)
- [PHP](#php)

### Laravel

Install the package

```sh
# TODO
```

Create the `vector` extension

```sh
php artisan vendor:publish --tag="pgvector-migrations"
php artisan migrate
```

You can now use the `vector` type in future migrations

```php
Schema::create('items', function (Blueprint $table) {
    $table->vector('factors', 3);
});
```

Insert a vector

```php
$item = new Item;
$item->factors = '[1,2,3]';
$item->save();
```

Get the nearest neighbors

```php
$neighbors = Item::orderByRaw('factors <-> ?', array('[1,2,3]'))->take(5)->get();
```

Get the distances

```php
$distances = Item::selectRaw('factors <-> ? AS distance', array('[1,2,3]'))->pluck('distance');
```

Add an approximate index in a migration

```php
public function up()
{
    DB::statement('CREATE INDEX my_index ON items USING ivfflat (factors vector_l2_ops)');
}

public function down()
{
    DB::statement('DROP INDEX my_index');
}
```

Use `vector_ip_ops` for inner product and `vector_cosine_ops` for cosine distance

### PHP

Insert a vector

```php
pg_query_params($db, 'INSERT INTO items (factors) VALUES ($1)', array('[1,2,3]'));
```

Get the nearest neighbors to a vector

```php
$result = pg_query_params($db, 'SELECT * FROM items ORDER BY factors <-> $1 LIMIT 5', array('[1,2,3]'));
```

See a [full example](example.php)

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
createdb pgvector_php_test
php example.php
```

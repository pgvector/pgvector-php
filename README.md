# pgvector-php

[pgvector](https://github.com/pgvector/pgvector) examples for PHP

[![Build Status](https://github.com/pgvector/pgvector-php/workflows/build/badge.svg?branch=master)](https://github.com/pgvector/pgvector-php/actions)

## Getting Started

Follow the instructions for your database library:

- [Laravel](#laravel)
- [PHP](#php)

### Laravel

Create a migration

```sh
php artisan make:migration create_vector_extension
```

with:

```php
public function up()
{
    DB::statement('CREATE EXTENSION vector');
}

public function down()
{
    DB::statement('DROP EXTENSION vector');
}
```

Run the migration

```sh
php artisan migrate
```

You can now use the `vector` type in future migrations

```php
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;

PostgresGrammar::macro('typeVector', function (ColumnDefinition $column) {
    if ($column->get('dimensions')) {
        return 'vector(' . intval($column->get('dimensions')) . ')';
    } else {
        return 'vector';
    }
});

Schema::create('items', function (Blueprint $table) {
    $table->addColumn('vector', 'factors', array('dimensions' => 3));
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

### PHP

Insert a vector

```php
pg_query_params($db, 'INSERT INTO items (factors) VALUES ($1)', array('[1,1,1]'));
```

Get the nearest neighbors to a vector

```php
$result = pg_query_params($db, 'SELECT * FROM items ORDER BY factors <-> $1 LIMIT 5', array('[1,1,1]'));
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

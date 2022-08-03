# pgvector-php

[pgvector](https://github.com/pgvector/pgvector) examples for PHP

[![Build Status](https://github.com/pgvector/pgvector-php/workflows/build/badge.svg?branch=master)](https://github.com/pgvector/pgvector-php/actions)

## Getting Started

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

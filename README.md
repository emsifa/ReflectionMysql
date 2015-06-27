ReflectionMysql
===========================

Inspired by `ReflectionClass`, this library will reflect your MySQL database. You can get list tables, list columns in the table, what is the primary key of the table, get table indexes, column relations, what column type is, column comment, etc.

Like `ReflectionClass`, `ReflectionMysql` has Reflection tree. 
For example, if `ReflectionClass` has many `ReflectionMethod` objects that reflect methods in a class, `ReflectionMysql` has many `ReflectionTable` objects that reflect tables in database. If `ReflectionMethod` has many `ReflectionParameters` objects that reflect parameters in method, `ReflectionTable` has many `ReflectionColumn` objects that reflect columns in table. And that objects are related each other. 

## API

### ReflectionMysql

This class will reflect a single database.

* **getName()** : get database name.
* **getConnection()** : get `mysqli` connection.
* **getTables()** : get list tables in database. It will return array of `ReflectionTable`.
* **getTable($table_name)** : get `ReflectionTable` from given table name. Will return `null` if table is not exists. 
* **hasTable($table_name)** : check table existance.

### ReflectionTable

This class will reflect your table.

* **getName()** : get table name.
* **getDatabase()** : get `ReflectionMysql` of its database.
* **getConnection()** : get `mysql` connection.
* **getColumns()** : get list columns in table. It will return array of `ReflectionColumns`.
* **getColumn($col_name)** : get `ReflectionColumn` from given column name. Will return `null` if column is not exists.
* **hasColumn($col_name)** : check column existance.
* **getIndexes()** : get table indexes. It will return array of `ReflectionColumn`.
* **getRelations()** : get table relations. It will return array of array(`ReflectionColumn` $column, `ReflectionColumn` $related_column).
* **getPrimary()** : get `ReflectionColumn` of column that has primary key. Will return `null` if table have not primary key.

### ReflectionColumn

This class will reflect column in a table.

* **getConnection()** : get `mysqli` connection.
* **getDatabase()** : get `ReflectionMysql` of its database.
* **getTable()** : get `ReflectionTable` of its table.
* **getInfo($key, $default = null)** : get Column information by given key. Look at `information_schema.COLUMNS` and `information_schema.KEY_COLUMN_USAGE` in your mysql for available keys.
* **getName()** : get column name.
* **getType()** : get column type (int, varchar, etc).
* **getDefault()** : get column default value.
* **getComment()** : get column comment/description.
* **getLength()** : get column maximum char length.
* **getCharset()** : get column charset.
* **getCollation()** : get collation.
* **isAutoIncrement()** : check the column is auto increment or not.
* **getCatalog()** : get its table catalog.
* **getSortOrder()** : get sort order.
* **isNullable()** : check the column is nullable or not.
* **isPrimary()** : check the column is primary key or not.
* **isUnique()** : check the column is unique or not.
* **isIndex()** : check the column is index(MUL) or not.
* **isIndexed()** : check the column is indexed(primary|unique|index) or not. 
* **getRelations()** : get column relations. It will return array of `ReflectionColumn` that related to .

## Examples

#### Initialize

```php
<?php

use Emsifa\ReflectionMysql\ReflectionMysql;

$host = 'localhost';
$username = 'root';
$password = 'password';
$dbname = 'my_database_name';

$db_reflection = new ReflectionMysql($host, $username, $password, $dbname);

```

#### Get list tables

```php

$tables = $db_reflection->getTables();

/**
 * $tables will contain array like this
 *
 * array(
 *      'users'                 => ReflectionTable object,
 *      'products'              => ReflectionTable object,
 *      'product_categories'    => ReflectionTable object,
 * )
 */
```

#### Get list columns

```php

$users_table = $db_reflection->getTable('users');
$columns = $users_table->getColumns();

/**
 * $columns will contain array like this
 *
 * array(
 *      'id'        => ReflectionColumn object,
 *      'username'  => ReflectionColumn object,
 *      'password'  => ReflectionColumn object,
 * )
 */
```

#### Get table relations

```php

$users_table = $db_reflection->getTable('users');
$relations = $users_table->getRelations();

/**
 * $relations will contain array like this
 *
 * array(
 *      'users.id:products.user_id'             => ReflectionColumn object,
 *      'users.id:product_categories.user_id'   => ReflectionColumn object,
 * )
 */
```

```php

$products_table = $db_reflection->getTable('products');
$relations = $products_table->getRelations();

/**
 * $relations will contain array like this
 *
 * array(
 *      'products.user_id:user.id'             => ReflectionColumn object,
 * )
 */
```

#### Using `ReflectionColumn`

```php

$users_table = $db_reflection->getTable('users');
$ref_id = $users_table->getColumn('id');
$ref_username = $users_table->getColumn('username');
$ref_password = $users_table->getColumn('password');

var_dump($ref_id->isPrimary()); // bool(true)
var_dump($ref_username->isPrimary()); // bool(false)
var_dump($ref_username->isUnique()); // bool(true)
var_dump($ref_username->isIndexed()); // bool(true)
var_dump($ref_password->isIndexed()); // bool(false)

var_dump($ref_username->getName()); // string "username"
var_dump($ref_username->getType()); // string "varchar"
var_dump($ref_username->getLength()); // int 20
var_dump($ref_username->getComment()); // something like string "it is unique dude, and it must only contain [a-zA-Z0-9_]"

var_dump($ref_username->getTable()); // ReflectionTable object of table users

$relations = $ref_id->getRelations();
/**
 * $relations will contain array like this
 *
 * array(
 *      'products.user_id'             => ReflectionColumn object,
 *      'product_categories.user_id'   => ReflectionColumn object,
 * )
 */
```
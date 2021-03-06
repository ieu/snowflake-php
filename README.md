# Snowflake

Please Note that this is not ***concurrency-safe***.

Global ID generator based on [ Twitter Snowflake](https://github.com/twitter-archive/snowflake/tree/snowflake-2010).

The generated ID is structured 64 bit integer:

```
 0           41     51     64
 +-----------+------+------+
 |timestamp  |worker|seq   |
 +-----------+------+------+
```

`timestamp` is 41 bit integer presents milliseconds since 11/04/2010 01:42:54.657 GMT.

`worker` is 10 bit integer presents worker ID the generator is running on

`seq` is 12 bit integer presents incremental sequence per millisecond

## Installation

First add this repo to `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ieu/snowflake-php"
        }
    ]
}
```

Then install it through [composer](https://getcomposer.org/download/):

```shell
composer require ieu/snowflake:dev-master
```

## Usage

```php
require __DIR__ . '/vendor/autoload.php';

use Ieu\Snowflake\Snowflake;

$datacenterId = 1;
$workerId = 1;

$snowflake = new Snowflake($datacenterId, $workerId);

// Getting Id
$id = $snowflake->nextId();
```

## Tests

```shell script
./vendor/bin/phpunit
```

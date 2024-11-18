# Flexihash

Flexihash is a small PHP library which implements [consistent hashing](http://en.wikipedia.org/wiki/Consistent_hashing), which is most useful in distributed caching. It requires PHP5 and uses [PHPUnit](http://simpletest.org/) for unit testing.

## Installation

[Composer](https://getcomposer.org/) is the recommended installation technique. You can find flexihash on [Packagist](https://packagist.org/packages/bcremer/flexihash) so installation is as easy as
```
composer require bcremer/flexihash
```
or in your `composer.json`
```json
{
    "require": {
        "bcremer/flexihash": "^4.0.0"
    }
}
```

## Usage

```php
$hash = new Flexihash();

// bulk add
$hash->addTargets(['cache-1', 'cache-2', 'cache-3']);

// simple lookup
$hash->lookup('object-a'); // "cache-1"
$hash->lookup('object-b'); // "cache-2"

// add and remove
$hash
  ->addTarget('cache-4')
  ->removeTarget('cache-1');

// lookup with next-best fallback (for redundant writes)
$hash->lookupList('object', 2); // ["cache-2", "cache-4"]

// remove cache-2, expect object to hash to cache-4
$hash->removeTarget('cache-2');
$hash->lookup('object'); // "cache-4"
```

## Tests

### Unit Test

```
% vendor/bin/phpunit
```

### Benchmark Test

```
% vendor/bin/phpunit tests/BenchmarkTest.php
```

## Further Reading

  * http://www.spiteful.com/2008/03/17/programmers-toolbox-part-3-consistent-hashing/
  * http://weblogs.java.net/blog/tomwhite/archive/2007/11/consistent_hash.html

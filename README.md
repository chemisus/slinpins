# Slinpins

A PHP dependency injection container.

## Details

 * Creator:     Terrence Howard <<chemisus@gmail.com>>
 * Version:     1.0
 * Location:    https://github.com/chemisus/slinpins

## Introduction

### Summary

Slinpins is a dependency injection container for [php](http://php.net). There
are several reasons why developers would want to utilize
[dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) in
their applications, but that is really out of the scope of this document.

### Definitions

The following definitions are related to Slinpins, and should be useful to
understanding the rest of this document.

| Name              | Definition
|-------------------|-----------------------------------------------------------
| `$scope`          | an instance of a Scope object in php code
| value             | a mixed or callback that is the result of a fetch
| fetch             | the process of obtaining a value from a scope's provider
| provider          | can initialize and fetch a value
| initialize        | the process of setting up a provider; only happens once
| constant          | an already initialized value that will never change
| variable          | a value that can differ any time it is fetched
| method            | a value that can invoke and inject into functions
| factory           | a value that can invoke and inject into constructors
| service           | a value that becomes constant after initialization

## Requirements

PHP 5.3 is required.

## Installation

```php
require_once('path/to/slinpins/src/Scope.php');
```

## Configuration

## Examples

The following examples require a $scope object, which can be obtained by the
following code:

```php
$scope = new Scope();
```

### Constant

```php
$scope.constant('a', 1);

for ($i = 0; $i < 5; $i++) {
    echo $scope.fetch('a').', ';     // prints "1, 1, 1, 1, 1"
}
```

### Variable

```php
$b = 0;

$scope.variable('b', function () use (&$b) {
    return $b++;
});

for ($i = 0; $i < 5; $i++) {
    echo $scope.fetch('b').', ';     // prints "1, 2, 3, 4, 5"
}
```

### Method

```php
$c = 0;

$scope.variable('c', function () use (&$c) {
    return $c++;
});

for ($i = 0; $i < 5; $i++) {
    $method = $scope.fetch('c');

    echo $method().', ';     // prints "1, 2, 3, 4, 5"
}
```

### Factory

```php
class TestClass {
    public $b;

    public function __construct($b) {
        $this->b = $b;
    }
}

$b = 0;

$scope.factory('d', 'TestClass');

$factory = $scope.fetch('d');

for ($i = 0; $i < 5; $i++) {
    $instance = $factory();

    echo $instance->b.', ';     // prints "1, 2, 3, 4, 5"
}
```

### Service

```php
$scope.service('e', function () {
});
```

### Injection

```php
$scope.constant('a', 1);

$b = 0;
$scope.variable('b', function () use (&$b) {
    return $b++;
});

$c = 0;
$scope.variable('c', function () use (&$c) {
    return $c++;
});

$e = 0;
$scope.service('e', function () use (&$e) {
    return $e++;
});

$method = $scope.inject(function ($a, $b, $c, $d, $i) {
    return implode(", ", func_get_args());
});

echo $method(array('i'=>0))."\n";   // prints "1, 0, 0, 0, 0"
echo $method(array('i'=>1))."\n";   // prints "1, 1, 1, 0, 1"
echo $method(array('i'=>2))."\n";   // prints "1, 2, 2, 0, 2"
echo $method(array('i'=>3))."\n";   // prints "1, 3, 3, 0, 3"
echo $method(array('i'=>4))."\n";   // prints "1, 4, 4, 0, 4"

```

## Operating Instructions

## Copywrite

Copyright (c) 2012 Terrence Howard <chemius@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

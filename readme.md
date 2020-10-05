# PHP Dot Array

This repo is unmaintained. Check out this if you need something simliar: https://github.com/adbario/php-dot-notation

Helps manage arrays in PHP with dot notation. Useful for configs, meta data, or just working with large associative arrays. 

Often, working with large arrays is cumbersome and prone to errors. Having to drill down into multiple levels of an array, checking isset all the way, is not fun.

```php

if (isset($data['level1']) && isset($data['level1']['level2'])) {
    $value = $data['level1']['level2']['key'];
} else {
    $value = null;
}

```

Instead, you can do this:

```php

$dot = new Dot($data);
$value = $dot->get('level1.level2.key');

```

It will simply return null, instead of throwing an undefined index error, if any part of the dot path doesn't exist. Also, working with dot notation makes code more readable and easier to write.

## Usage

### Create a Dot


```php

use Jbizzay\Dot;

// Create empty dot
$dot = new Dot;

// Or, initialize with an array of data
$data = [
  'stats' => [
    'web' => [
      'hits' => 99
    ],
    'mobile' => [

    ]
  ]
];

$dot = new Dot($data);

```

### Get

With no argument, get returns the entire data array. Pass a dot notation string to access parts of the data array.


```php

$dot->get(); // Returns full data array

$dot->get('stats.web.hits'); // Returns 99

$dot->get('stats.mobile.hits'); // Returns null

$dot->get('some.random.undefined.key'); // Returns null

 
```


### Set

You can set any data type, including callable functions. Any levels that don't already exist, will be created as associative arrays. Set returns the same instance of Dot allowing for method chaining. Using a callable type will recieve the currently set value (if it exists) as an argument.


```php

$dot
  ->set('stats.web.last_updated', new DateTime)
  ->set('stats.web.allow_tracking', true)
  ->set('stats.web.hits', function ($hits) {
    $hits++;
    return $hits;
  });

$dot->get('stats.web');

/* Returns:
Array
(
    [hits] => 100
    [last_updated] => DateTime Object
        (
            [date] => 2017-07-21 14:50:34.000000
            [timezone_type] => 3
            [timezone] => America/Los_Angeles
        )

    [allow_tracking] => 1
)
*/


```


### Unset

Unset a value, returns the dot instance

```php

$dot
  ->unset('path.to.value')
  ->unset('some.other.value');

```


### Has

Determines if a key is set

```php

$dot->has('stats.web'); // true
$dot->has('some.random.key'); // false

```


### Define

Gets a value, but if the key is not set, initialize the key with a value. You can also use a callable type. By default, initializes with array. Returns the dot path value

```php

$dot->define('leads.emails'); // Sets to an array
$hits = $dot->define('stats.mobile.hits', 0);
$dot->define('stats.console.hits', function () {
  // This function is called if this key is not set yet
  return 0;
});

```

### Merge

Recursively merges an array into the dot array. First argument can be a dot path, an array, or a function that returns an array. The second argument can be an array or a function, but should only be used if first argument is a key. Returns the dot instance

```php

// Merge into whole data array
$dot->merge([
  'stats' => [
    'web' => [
      'hits' => 123, 
      'leads' => 321
    ]
  ]
]);

$dot->get();

/* Returns:
Array
(
  [stats] => Array
    (
      [web] => Array
        (
          [hits] => 123
          [last_updated] => DateTime Object
            (
              [date] => 2017-07-25 13:34:49.000000
              [timezone_type] => 3
              [timezone] => America/Los_Angeles
            )

          [allow_tracking] => 1
          [leads] => 321
        )

      [mobile] => Array
        (
        )

    )
)
*/

// Merge array into a dot path
$dot->merge('stats.mobile', [
  'issues' => 33
]);

// Merge using function
$dot->merge('stats.mobile', function ($mobile) {
  return ['updated' => new DateTime];
});

// Merge into whole data array with function
$dot->merge(function ($data) {
  return ['new' => 123];
});

```



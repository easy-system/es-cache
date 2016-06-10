Usage
======

# Getting cache adapter 

## If the package is used standalone

```
$cacheNamespace = 'foo';
$cache = \Es\Cache\CacheFactory::make($cacheNamespace);
```

## If the package is used as component of System

```
$cacheNamespace = 'foo';
$cache = $services->get('Cache')->withNamespace($cacheNamespace);
```

# The state of adapters

By default, adapters have the "disabled" state. This state can be changed 
globally for all adapters by using the CacheFactory or to a specific instance of
an adapter using the `setEnabled()` method.

## If adapter is enabled

### Save variable
```
$foo = new \stdClass();
$cache->set('foo', $foo);
```
In the example used above the lifetime of variables defined for the default 
lifetime adapter. To set the lifetime for a specific variable:
```
$foo = new \stdClass();
$cache->set('foo', $foo, 360);
```
The lifetime of a variable is set in seconds.

### Retrive variable
```
$foo = $cache->get('foo');
```

### Remove variable
```
$cache->remove('foo');
```

## On error

The following methods return `false` if an error occurs and the adapter is 
enabled:

- `set()`
- `get()`
- `remove()`

## If adapter is disabled

The following methods will return `null` if the adapter is disabled

- `set()`
- `get()`
- `remove()`

# The namespace

## Change namespace
By default, the adapter uses a `default` namespace to store variables.
To change the namespace:
```
$cache = $cache->withNamespace('Foo');
```
The method `withNamespace('Foo')` will return a new instance of the adapter
with the `Foo` namespace.

An instance of the adapter with the same namespace will be the same anywhere in
the code.

## Cleaning namespace
To remove all variables from the namespace:
```
$cache->clearNamespace();
```
Usage
=====

By default, adapters have the "disabled" state. This state can be changed 
globally for all adapters by using the CacheFactory or to a specific instance of
an adapter using the `setEnabled()` method.

To check the state of the adapter, use the method of `isEnabled()`.

# If adapter is enabled

## Save variable
```
$foo = new \stdClass();
$cache->set('foo', $foo);
```

## Retrive variable
```
$foo = $cache->get('foo);
```

## Remove variable
```
$cache->remove('foo');
```

# The namespace
By default, the adapter uses a `default` namespace to store variables.
To change the namespace:
```
$cache = $cache->withNamespace('Foo');
```
The method `withNamespace('Foo')` will return a new instance of the adapter
with the `Foo` namespace.

An instance of the adapter with the same namespace will be the same anywhere in
the code.

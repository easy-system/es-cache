<?php
/**
 * This file is part of the "Easy System" package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Damon Smith <damon.easy.system@gmail.com>
 */
namespace Es\Cache;

use DomainException;
use InvalidArgumentException;

/**
 * The Factory of cache adapters.
 */
class CacheFactory
{
    /**
     * The name of default cache adapter.
     * A specific adapter class must be specified in the cache configuration.
     *
     * @var string
     */
    const DEFAULT_ADAPTER = 'filesystem';

    /**
     * The cache configuration.
     *
     * @var array
     */
    protected static $config = [
        'defaults' => [
            'adapter' => 'filesystem',
            'options' => [
                'enabled' => false,
            ],
        ],
        'adapters' => [
            'filesystem' => [
                'class'   => 'Es\Cache\Adapter\FileCache',
                'options' => [
                    'basedir'         => './data/cache',
                    'default_ttl'     => 315360000,
                    'dir_permission'  => 0700,
                    'file_permission' => 0600,
                    'gc'              => 1000,
                ],
            ],
        ],
    ];

    /**
     * Sets the configuration.
     *
     * @param array $config The configuration
     *
     * @throws \DomainException
     *
     * - If the configuration of adapters not exists.
     * - If the configuration of adapters is not array.
     * - If the configuration of default adapter not exists.
     * - If the class of any adapter is not specified.
     */
    public static function setConfig(array $config)
    {
        if (! isset($config['adapters']) || ! is_array($config['adapters'])) {
            throw new DomainException(
                'Missing adapters configuration.'
            );
        }

        $defaultAdapter = static::DEFAULT_ADAPTER;
        if (isset($config['defaults']['adapter'])) {
            $defaultAdapter = $config['defaults']['adapter'];
        }
        if (! isset($config['adapters'][$defaultAdapter])) {
            throw new DomainException(sprintf(
                'Missing configuration of default adapter "%s".',
                $defaultAdapter
            ));
        }

        foreach ($config['adapters'] as $adapter => $items) {
            if (! isset($items['class'])) {
                throw new DomainException(sprintf(
                    'The class of adapter "%s" is not specified.',
                    $adapter
                ));
            }
        }
        static::$config = $config;
    }

    /**
     * Gets the configuration.
     *
     * @return array $config The configuration
     */
    public static function getConfig()
    {
        return static::$config;
    }

    /**
     * Make the cache adapter.
     *
     * @param string $namespace Optional; null by default. The namespace
     * @param string $adapter   Optional; null by default. The type of adapter
     *
     * @throws \InvalidArgumentException If the given adapter type not specified
     *                                   in configuration
     * @throws \DomainException          If the class of specified adapter not
     *                                   inherit an Es\Cache\Adapter\AbstractCache
     *
     * @return \Es\Cache\Adapter\AbstractCache The new instance of cache adapter
     */
    public static function make($namespace = null, $adapter = null)
    {
        $defaults = isset(static::$config['defaults'])
                  ? static::$config['defaults']
                  : [];

        if (! $adapter) {
            $adapter = isset($defaults['adapter'])
                     ? $defaults['adapter']
                     : static::DEFAULT_ADAPTER;
        }

        if (! isset(static::$config['adapters'][$adapter])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown type adapter of cache "%s".',
                $adapter
            ));
        }
        $config = static::$config['adapters'][$adapter];
        $class  = $config['class'];

        $options = array_merge(
            isset($defaults['options']) ? (array) $defaults['options'] : [],
            isset($config['options'])   ? (array) $config['options']   : []
        );
        if (! $namespace) {
            $namespace = 'default';
        }
        $options['namespace'] = $namespace;

        $cache = new $class($options);
        if (! $cache instanceof AbstractCache) {
            throw new DomainException(sprintf(
                'The class "%s" of adapter "%s" must inherit '
                . 'an "Es\Cache\AbstractCache".',
                $class,
                $adapter
            ));
        }

        return $cache;
    }
}

<?php
/**
 * This file is part of the "Easy System" package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Damon Smith <damon.easy.system@gmail.com>
 */
namespace Es\Cache\Adapter;

/**
 * Representation of abstract cache adapter.
 */
abstract class AbstractCache
{
    /**#@+
     * @const integer Standard time intervals in seconds
     */
    const MINUTE_TO_SECONDS = 60;
    const HOUR_TO_SECONDS   = 3600;
    const DAY_TO_SECONDS    = 86400;
    const WEEK_TO_SECONDS   = 604800;
    const MONTH_TO_SECONDS  = 2592000;
    const YEAR_TO_SECONDS   = 31536000;
    /**#@-*/

    /**
     * The namespace of adapter.
     *
     * @var string
     */
    protected $namespace = 'default';

    /**
     * The filter of data conversion is used for data storing.
     *
     * @var callable
     */
    protected $storingFilter = 'serialize';

    /**
     * The filter of data conversion is used for data restoring.
     *
     * @var callable
     */
    protected $restoringFilter = 'unserialize';

    /**
     * The name of hashing algorithm.
     * It is used when converting variable names.
     *
     * @var string
     */
    protected $hashingAlgorithm = 'crc32';

    /**
     * The time to life.
     *
     * @var int
     */
    protected $defaultTtl = 0;

    /**
     * Is adapter enabled?
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Constructor.
     *
     * @param array $options Optional; the adapter options
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $name => $value) {
            $setter = 'set' . str_replace('_', '', $name);
            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
            }
        }
    }

    /**
     * Sets the state of adapter to "enabled state" or to "disabled state".
     *
     * @param bool $state Optional; true by default. The adapter state
     *
     * @return self
     */
    public function setEnabled($state = true)
    {
        $this->enabled = (bool) $state;

        return $this;
    }

    /**
     * Wheter the adapter enabled?
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets the filter of data conversion is used for data storing.
     *
     * @param callable $filter The name of filter
     *
     * @return self
     */
    public function setStoringFilter(callable $filter)
    {
        $this->storingFilter = $filter;

        return $this;
    }

    /**
     * Gets the filter of data conversion is used for data storing.
     *
     * @return callable
     */
    public function getStoringFilter()
    {
        return $this->storingFilter;
    }

    /**
     * Sets the filter of data conversion is used for data restoring.
     *
     * @param callable $filter The name of filter
     *
     * @return self
     */
    public function setRestoringFilter(callable $filter)
    {
        $this->restoringFilter = $filter;

        return $this;
    }

    /**
     * Gets the filter of data conversion is used for data restoring.
     *
     * @return callable
     */
    public function getRestoringFilter()
    {
        return $this->restoringFilter;
    }

    /**
     * Sets the name of hashing algorithm is used for converting variable names.
     *
     * @param string $name The algorithm name
     *
     * @return self
     */
    public function setHashingAlgorithm($name)
    {
        $this->hashingAlgorithm = $name;

        return $this;
    }

    /**
     * Gets the name of hashing algorithm is used for converting variable names.
     *
     * @return string The algorithm name
     */
    public function getHashingAlgorithm()
    {
        return $this->hashingAlgorithm;
    }

    /**
     * Gets the namespace of current adapter.
     *
     * @return string The namespace
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Sets the default time to life.
     *
     * @param int $seconds The default time to life
     *
     * @return self
     */
    public function setDefaultTtl($seconds)
    {
        $this->defaultTtl = (int) $seconds;

        return $this;
    }

    /**
     * Gets the default time to life.
     *
     * @return int The default time to life
     */
    public function getDefaultTtl()
    {
        return $this->defaultTtl;
    }

    /**
     * Sets the namespace of current adapter.
     *
     * @param string $namespace The namespace
     */
    protected function setNamespace($namespace)
    {
        $this->namespace = (string) $namespace;
    }

    /**
     * Cache a variable in the data store.
     *
     * @param string $key  The variable name
     * @param mixed  $data The variable to store
     * @param int    $ttl  Optional; 0 by default means "the default ttl".
     *                     The time to life.
     *
     * @return null|bool Returns null if adapter is disabled, true on success,
     *                   false otherwise
     */
    abstract public function set($key, $data, $ttl = 0);

    /**
     * Gets a stored variable from the cache.
     *
     * @param string $key The variable name
     *
     * @return mixed Returns null if adapter is disabled, stored data on success,
     *               false otherwise
     */
    abstract public function get($key);

    /**
     * Removes a stored variable from the cache.
     *
     * @param string $key The variable name
     *
     * @return null|bool Returns null if adapter is disabled, true on success,
     *                   false otherwise
     */
    abstract public function remove($key);

    /**
     * Returns the instance with specified namespace.
     *
     * @param string $namespace The namespace
     *
     * @return self The new instance with specified namespace
     */
    abstract public function withNamespace($namespace);

    /**
     * Cleans the namespace.
     * Remove any previously stored for the current namespace.
     *
     * @return null|bool Returns null if adapter is disabled, true on success,
     *                   false otherwise
     */
    abstract public function clearNamespace();
}

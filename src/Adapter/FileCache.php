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

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * The cache adapter for the file system.
 *
 * As a namespace uses file system directories.
 * The each variable are stored in a separate file.
 */
class FileCache extends AbstractCache
{
    /**
     * The array with namespaces as keys and instances as values.
     *
     * @var array
     */
    protected static $namespaces = [];

    /**
     * The path to base cache directory of application.
     *
     * @var string
     */
    protected $basedir = './data/cache';

    /**
     * Permissions of cache directories.
     *
     * @var int
     */
    protected $dirPermissions = 0700;

    /**
     * Permissions of cache files.
     *
     * @var int
     */
    protected $filePermissions = 0600;

    /**
     * The count of cycles before garbage collection.
     *
     * @var int
     */
    protected $gc = 1000;

    /**
     * Constructor.
     *
     * @param array $options Optional; the options of cache adapter
     */
    public function __construct(array $options = [])
    {
        $enabled = $this->enabled;
        if (array_key_exists('enabled', $options)) {
            $enabled = $options['enabled'];
            unset($options['enabled']);
        }
        parent::__construct($options);

        static::$namespaces[$this->namespace] = $this;

        $this->setEnabled($enabled);
    }

    /**
     * Destructor.
     *
     * Collect garbage.
     */
    public function __destruct()
    {
        if (! $this->enabled) {
            return;
        }
        if ($this->gc === rand(1, $this->gc)) {
            $this->clearExpired();
        }
    }

    /**
     * Returns the instance with specified namespace.
     *
     * @param string $namespace The namespace
     *
     * @return self The new instance with specified namespace
     */
    public function withNameSpace($namespace)
    {
        $namespace = (string) $namespace;

        if (! isset(static::$namespaces[$namespace])) {
            $new = clone $this;
            $new->setNamespace($namespace);
            $new->setEnabled($this->enabled);
            static::$namespaces[$namespace] = $new;
        }

        return static::$namespaces[$namespace];
    }

    /**
     * Sets the state of adapter to "enabled state" or to "disabled state".
     *
     * @param bool $state Optional; true by default. The adapter state
     *
     * @throws \RuntimeException If failed to enable the cache adapter
     *
     * @return self
     */
    public function setEnabled($state = true)
    {
        $this->enabled = (bool) $state;

        if ($this->enabled) {
            try {
                $this->createNamespace();
            } catch (Exception $ex) {
                throw new RuntimeException(sprintf(
                    'Failed to enable the cache adapter of class "%s" '
                    . 'with namespace "%s".',
                    static::CLASS,
                    $this->namespace
                ), null, $ex);
            }
        }

        return $this;
    }

    /**
     * Gets the base cache directory.
     *
     * @return string The path of cache directory
     */
    public function getBaseDir()
    {
        return $this->basedir;
    }

    /**
     * Gets permissions of cache directory.
     *
     * @return int The cache directory permissions
     */
    public function getDirPermissions()
    {
        return $this->dirPermissions;
    }

    /**
     * Gets permissions of cache files.
     *
     * @return string The permissions of cache files
     */
    public function getFilePermissions()
    {
        return $this->filePermissions;
    }

    /**
     * Gets the count of cycles before garbage collection.
     *
     * @return int The count of cycles before garbage collection
     */
    public function getGc()
    {
        return $this->gc;
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
    public function set($key, $data, $ttl = 0)
    {
        if (! $this->enabled) {
            return;
        }
        if (! $ttl) {
            $ttl = $this->defaultTtl;
        }
        set_error_handler(function () {
            throw new Exception('An error occurred.');
        }, E_WARNING);

        $filtered = call_user_func($this->storingFilter, $data);

        $file = $this->getPath($key);

        try {
            file_put_contents($file, $filtered, LOCK_EX);
            touch($file, time() + (int) $ttl);
            chmod($file, $this->filePermissions);
            clearstatcache(true, $file);
        } catch (Exception $ex) {
            restore_error_handler();
            $this->remove($key);

            return false;
        }
        restore_error_handler();

        return true;
    }

    /**
     * Gets a stored variable from the cache.
     *
     * @param string $key The variable name
     *
     * @return mixed Returns null if adapter is disabled, stored data on success,
     *               false otherwise
     */
    public function get($key)
    {
        if (! $this->enabled) {
            return;
        }
        $file = $this->getPath($key);
        if (! file_exists($file)) {
            return false;
        }

        set_error_handler(function () {
            throw new Exception('An error occurred.');
        }, E_WARNING);

        try {
            if (filemtime($file) < time()) {
                throw new Exception('Term life data has expired.');
            }
            $data = file_get_contents($file);
        } catch (Exception $ex) {
            restore_error_handler();
            $this->remove($key);

            return false;
        }
        restore_error_handler();

        return call_user_func($this->restoringFilter, $data);
    }

    /**
     * Removes a stored variable from the cache.
     *
     * @param string $key The variable name
     *
     * @return null|bool Returns null if adapter is disabled, true on success,
     *                   false otherwise
     */
    public function remove($key)
    {
        if (! $this->enabled) {
            return;
        }
        $file = $this->getPath($key);
        if (! file_exists($file)) {
            return true;
        }

        set_error_handler(function () {
            throw new Exception('An error occurred.');
        }, E_WARNING);

        try {
            unlink($file);
        } catch (Exception $ex) {
            restore_error_handler();

            return false;
        }
        restore_error_handler();

        return true;
    }

    /**
     * Cleans the namespace.
     * Remove any previously stored for the current namespace.
     *
     * @return null|bool Returns null if adapter is disabled, true on success,
     *                   false otherwise
     */
    public function clearNamespace()
    {
        if (! $this->enabled) {
            return;
        }
        $error = false;

        set_error_handler(function () use (&$error) {
            $error = true;
        }, E_WARNING);

        foreach (glob($this->getPath() . '/*.dat') as $file) {
            unlink($file);
        }
        restore_error_handler();

        return ! $error;
    }

    /**
     * Cleans all expired variables.
     *
     * It is recommended not to use this method directly.
     * This method is called from the destructor if necessary.
     *
     * @return null|bool Returns null if adapter is disabled, true on success,
     *                   false otherwise
     */
    public function clearExpired()
    {
        if (! $this->enabled) {
            return;
        }
        $error = false;

        set_error_handler(function () use (&$error) {
            $error = true;
        }, E_WARNING);
        foreach (glob($this->getPath() . '/*.dat') as $file) {
            if (filemtime($file) < time()) {
                unlink($file);
            }
        }
        restore_error_handler();

        return ! $error;
    }

    /**
     * Gets the path to the file appropriate variable or path to namespace directory.
     *
     * @param string $variableName Optional; the name of variable
     *
     * @return string If the variable name received, returns the path to
     *                appropriate file, otherwise returns the path to
     *                directory of namespace
     */
    protected function getPath($variableName = '')
    {
        $hash = function ($name) {
            return hash($this->hashingAlgorithm, $name);
        };
        $path = $this->basedir . DIRECTORY_SEPARATOR . $hash($this->namespace);
        if ($variableName) {
            $path .= DIRECTORY_SEPARATOR . $hash($variableName) . '.dat';

            return $path;
        }

        return $path;
    }

    /**
     * Sets the path to base cache directory of application.
     *
     * @param string $basedir The path to base cache directory
     */
    protected function setBaseDir($basedir)
    {
        $this->basedir = (string) $basedir;
    }

    /**
     * Sets the count of cycles before garbage collection.
     *
     * One complete cycle means the life of instance of Es\Cache\Adapter\FileCache
     * for a given namespace.
     *
     * @param int $cycles The count of cycles
     */
    protected function setGc($cycles)
    {
        $this->gc = (int) $cycles;
    }

    /**
     * Sets permissions for directories.
     *
     * @param int $permissions The directory permissions
     *
     * @throws \InvalidArgumentException If the permissions do not allow write
     *                                   to directory or read from directory
     */
    protected function setDirPermissions($permissions)
    {
        $permissions = (int) $permissions;

        if (! (0b100000000 & $permissions)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid permissions "%s" for directories. '
                . 'Directories will not available for reading.',
                decoct($permissions)
            ));
        }
        if (! (0b010000000 & $permissions)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid permissions "%s" for directories. '
                . 'Directories will not available for writing.',
                decoct($permissions)
            ));
        }
        if (! (0b001000000 & $permissions)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid permissions "%s" for directories. '
                . 'The content of directories will not available.',
                decoct($permissions)
            ));
        }
        $this->dirPermissions = $permissions;
    }

    /**
     * Sets permissions for files.
     *
     * @param int $permissions The file permissions
     *
     * @throws \InvalidArgumentException If the permissions do not allow write
     *                                   to file or read from file
     */
    protected function setFilePermissions($permissions)
    {
        $permissions = (int) $permissions;

        if (! (0b100000000 & $permissions)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid permissions "%s" for files. '
                . 'Files will not available for reading.',
                decoct($permissions)
            ));
        }
        if (! (0b010000000 & $permissions)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid permissions "%s" for files. '
                . 'Files will not available for writing.',
                decoct($permissions)
            ));
        }
        $this->filePermissions = $permissions;
    }

    /**
     * Creates directory for current namespace if not exists.
     *
     * @throws RuntimeException
     *
     * - If unable to create the directory for specified namespace.
     * - If the directory for specified namespace exists, but not writable.
     * - If the directory for specified namespace exists, but not readable.
     */
    protected function createNamespace()
    {
        $dir = $this->getPath();
        if (! file_exists($dir) || ! is_dir($dir)) {
            set_error_handler(function () {
                throw new Exception('An error occurred.');
            }, E_WARNING);
            try {
                mkdir($dir, $this->dirPermissions, true);
            } catch (Exception $ex) {
                restore_error_handler();
                throw new RuntimeException(
                    sprintf('Failed to create cache directory "%s".', $dir)
                );
            }
            restore_error_handler();
        }
        if (! is_writable($dir)) {
            throw new RuntimeException(
                sprintf('The cache directory "%s" is not writable.', $dir)
            );
        }
        if (! is_readable($dir)) {
            throw new RuntimeException(
                sprintf('The cache directory "%s" is not readable.', $dir)
            );
        }
    }
}

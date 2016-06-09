<?php
/**
 * This file is part of the "Easy System" package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Damon Smith <damon.easy.system@gmail.com>
 */
namespace Es\Cache\Test;

use Es\Cache\Adapter\AbstractCache;

class FakeAdapter extends AbstractCache
{
    protected $options = [];

    public function __construct(array $options = []) {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function set($key, $data, $ttl = 0)
    {
    }

    public function get($key)
    {
    }

    public function remove($key)
    {
    }

    public function withNamespace($namespace)
    {
    }

    public function clearNamespace()
    {
    }
}

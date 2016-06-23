<?php
/**
 * This file is part of the "Easy System" package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Damon Smith <damon.easy.system@gmail.com>
 */
namespace Es\Cache\Test\Adapter;

use Es\Cache\AbstractCache;

class AbstractCacheTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $options = [
            'enabled'           => true,
            'storing_filter'    => 'json_encode',
            'restoring_filter'  => 'json_decode',
            'hashing_algorithm' => 'md5',
            'default_ttl'       => 100,
            'namespace'         => 'some_namespace',
        ];

        $cache = $this->getMockForAbstractClass(
            AbstractCache::CLASS,
            [$options]
        );
        $this->assertTrue($cache->isEnabled());
        $this->assertEquals($cache->getStoringFilter(),    'json_encode');
        $this->assertEquals($cache->getRestoringFilter(),  'json_decode');
        $this->assertEquals($cache->getHashingAlgorithm(), 'md5');
        $this->assertEquals($cache->getDefaultTtl(),       100);
        $this->assertEquals($cache->getNamespace(),        'some_namespace');
    }

    public function testSetEnabledIsEnabled()
    {
        $cache = $this->getMockForAbstractClass(AbstractCache::CLASS);
        $cache->setEnabled(false);
        $this->assertFalse($cache->isEnabled());
        //
        $cache->setEnabled();
        $this->assertTrue($cache->isEnabled());
    }

    public function testSetStoringFilterGetStoringFilter()
    {
        $cache = $this->getMockForAbstractClass(AbstractCache::CLASS);
        $cache->setStoringFilter('json_encode');
        $this->assertEquals($cache->getStoringFilter(), 'json_encode');
    }

    public function testSetRestoringFilterGetRestoringFilter()
    {
        $cache = $this->getMockForAbstractClass(AbstractCache::CLASS);
        $cache->setRestoringFilter('json_decode');
        $this->assertEquals($cache->getRestoringFilter(), 'json_decode');
    }

    public function testSetHashingAlgorithmGetHashingAlgorithm()
    {
        $cache = $this->getMockForAbstractClass(AbstractCache::CLASS);
        $cache->setHashingAlgorithm('md4');
        $this->assertEquals($cache->getHashingAlgorithm(), 'md4');
    }

    public function testSetDefaultTtlGetDefaultTtl()
    {
        $cache = $this->getMockForAbstractClass(AbstractCache::CLASS);
        $cache->setDefaultTtl(10);
        $this->assertEquals($cache->getDefaultTtl(), 10);
    }
}

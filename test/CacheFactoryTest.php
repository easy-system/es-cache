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

use Es\Cache\CacheFactory;
use ReflectionProperty;

class CacheFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once 'FakeAdapter.php';
    }

    public function testSetConfigThrowsExceptionWhenMissingAdaptersConfig()
    {
        $this->setExpectedException('DomainException');
        CacheFactory::setConfig([]);
    }

    public function testSetConfigThrowsExceptionWhenAdaptersConfigIsNotArray()
    {
        $config = [
            'adapters' => 'the_configuration_of_adapters_is_not_an_array',
        ];
        $this->setExpectedException('DomainException');
        CacheFactory::setConfig($config);
    }

    public function testSetConfigThrowsExceptionWhenDefaultAdapterConfigurationNotSpecified()
    {
        $config = [
            'defaults' => [
                'adapter' => 'unknown_default_adapter',
            ],
            'adapters' => [
                'filesystem' => [
                    'class' => 'Es\Cache\Adapter\FileCache',
                ],
            ],
        ];
        $this->setExpectedException('DomainException');
        CacheFactory::setConfig($config);
    }

    public function testSetConfigThrowsExceptionWhenAdapterClassNotSpecified()
    {
        $config = [
            'adapters' => [
                'filesystem' => [
                    // 'class' not specified
                ],
            ],
        ];
        $this->setExpectedException('DomainException');
        CacheFactory::setConfig($config);
    }

    public function testSetConfig()
    {
        $config = [
            'adapters' => [
                'filesystem' => [
                    'class' => 'Es\Cache\Adapter\FileCache',
                ],
            ],
        ];
        CacheFactory::setConfig($config);
        $reflection = new ReflectionProperty('Es\Cache\CacheFactory', 'config');
        $reflection->setAccessible(true);
        $this->assertSame($config, $reflection->getValue('Es\Cache\CacheFactory'));
    }

    public function testGetConfig()
    {
        $config = [
            'adapters' => [
                'filesystem' => [
                    'class' => 'Es\Cache\Adapter\FileCache',
                ],
            ],
        ];
        CacheFactory::setConfig($config);
        $this->assertSame($config, CacheFactory::getConfig());
    }

    public function testMakeThrowsExceptionWhenSpecifiedAdapterIsUnknown()
    {
        $config = [
            'adapters' => [
                'filesystem' => [
                    'class' => 'Es\Cache\Adapter\FileCache',
                ],
            ],
        ];
        CacheFactory::setConfig($config);
        $this->setExpectedException('InvalidArgumentException');
        CacheFactory::make('some_namespace', 'unknown_adapter');
    }

    public function testMakeThrowsExceptionWhenAdapterClassNotInheritTheAbstractCache()
    {
        $config = [
            'adapters' => [
                'filesystem' => [
                    'class' => static::CLASS,
                ],
            ],
        ];
        CacheFactory::setConfig($config);
        $this->setExpectedException('DomainException');
        CacheFactory::make('some_namespace');
    }

    public function testMakeMakesAdapterWithOptions()
    {
        $config = [
            'defaults' => [
                'adapter' => 'fake',
                'options' => [
                    'cot' => 'cop',
                ],
            ],
            'adapters' => [
                'fake' => [
                    'class'   => 'Es\Cache\Test\FakeAdapter',
                    'options' => [
                        'bak' => 'bar',
                        'baz' => 'bat',
                    ],
                ],
            ],
        ];
        CacheFactory::setConfig($config);

        $cache    = CacheFactory::make('some_namespace', 'fake');
        $expected = [
            'cot'       => 'cop', // from defaults
            'bak'       => 'bar',
            'baz'       => 'bat',
            'namespace' => 'some_namespace',
        ];
        $this->assertEquals($cache->getOptions(), $expected);
    }

    public function testMakeWithoutNamespaceMakesAdapterWithDefaultNamespace()
    {
        $cache = CacheFactory::make();
        $this->assertSame('default', $cache->getNamespace());
    }
}

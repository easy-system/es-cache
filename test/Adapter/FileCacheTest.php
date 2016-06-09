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

use DirectoryIterator;
use Es\Cache\Adapter\FileCache;

class FileCacheTest extends \PHPUnit_Framework_TestCase
{
    protected $tempDir = '';

    public function setUp()
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'es-cache-test';
        if (file_exists($this->tempDir) && is_dir($this->tempDir)) {
            if (! is_writable($this->tempDir)) {
                $this->fail(sprintf(
                    'Temporary cache directory "%s" already exists and not writeble.',
                    $this->tempDir
                ));

                return;
            }
            if (! is_readable($this->tempDir)) {
                $this->fail(sprintf(
                    'Temporary cache directory "%s" already exists and not readable.',
                    $this->tempDir
                ));

                return;
            }
        }
        if (! @mkdir($this->tempDir, 0700, true)) {
            $this->fail(sprintf(
                'Failed to create temporary cache directory "%s".',
                $this->tempDir
            ));
        }
    }

    public function tearDown()
    {
        $this->removeRecursive($this->tempDir);
    }

    public function testSetEnabledThrowsIfUnableToCreateBaseDir()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->markTestSkipped('Not testable on windows.');
        }
        $protected = $this->tempDir . DIRECTORY_SEPARATOR . 'foo';
        mkdir($protected, 0400);
        $options = [
            'basedir' => $protected,
        ];
        $cache = new FileCache($options);
        $this->setExpectedException('RuntimeException');
        $cache->setEnabled();
    }

    public function testWithNamespaceReturnsInstanceWithSpecifiedNamespace()
    {
        $options = [
            'basedir'   => $this->tempDir,
            'namespace' => 'foo',
        ];
        $foo = new FileCache($options);

        $bar = $foo->withNameSpace('bar');
        $this->assertInstanceOf(FileCache::CLASS, $bar);
        $this->assertNotSame($foo, $bar);

        $baz = $bar->withNameSpace('baz');
        $this->assertInstanceOf(FileCache::CLASS, $baz);
        $this->assertNotSame($baz, $foo);
        $this->assertNotSame($baz, $bar);

        $secondFoo = $baz->withNameSpace('foo');
        $this->assertSame($foo, $secondFoo);

        $secondBar = $bar->withNameSpace('bar');
        $this->assertSame($bar, $secondBar);
    }

    public function testSetEnabledThrowsIfNamespaceDirIsNotWritable()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->markTestSkipped('Not testable on windows.');
        }
        $options = [
            'basedir'           => $this->tempDir,
            'hashing_algorithm' => 'crc32',
        ];
        $namespaceDir = $this->tempDir . DIRECTORY_SEPARATOR . hash('crc32', 'default');
        mkdir($namespaceDir, 0500, true);
        $cache = new FileCache($options);
        $this->setExpectedException('RuntimeException');
        $cache->setEnabled();
    }

    public function testSetEnabledThrowsIfNamespaceDirIsNotReadable()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->markTestSkipped('Not testable on windows.');
        }
        $options = [
            'basedir'           => $this->tempDir,
            'hashing_algorithm' => 'crc32',
        ];
        $namespaceDir = $this->tempDir . DIRECTORY_SEPARATOR . hash('crc32', 'default');
        mkdir($namespaceDir, 0300, true);
        $cache = new FileCache($options);
        $this->setExpectedException('RuntimeException');
        $cache->setEnabled();
    }

    public function testGetBaseDir()
    {
        $options = [
            'enabled' => false,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $this->assertEquals($cache->getBaseDir(), $this->tempDir);
    }

    public function testGetGc()
    {
        $options = [
            'enabled' => false,
            'gc'      => 10000,
        ];
        $cache = new FileCache($options);
        $this->assertEquals($cache->getGc(), 10000);
    }

    public function testSetDirPermissionsThrowsWhenPermissionsIsNotReadable()
    {
        $options = [
            'enabled'         => false,
            'dir_permissions' => 0300,
        ];
        $this->setExpectedException('InvalidArgumentException');
        $cache = new FileCache($options);
    }

    public function testSetDirPermissionsThrowsWhenPermissionsIsNotWritable()
    {
        $options = [
            'enabled'         => false,
            'dir_permissions' => 0500,
        ];
        $this->setExpectedException('InvalidArgumentException');
        $cache = new FileCache($options);
    }

    public function testSetDirPermissionsThrowsWhenPermissionsMakeContentNotAvailable()
    {
        $options = [
            'enabled'         => false,
            'dir_permissions' => 0600,
        ];
        $this->setExpectedException('InvalidArgumentException');
        $cache = new FileCache($options);
    }

    public function testGetDirPermissions()
    {
        $options = [
            'enabled'         => false,
            'dir_permissions' => 0777,
        ];
        $cache = new FileCache($options);
        $this->assertEquals($cache->getDirPermissions(), 0777);
    }

    public function testSetFilePermissionsThrowsWhenPermissionsIsNotReadable()
    {
        $options = [
            'enabled'          => false,
            'file_permissions' => 0300,
        ];
        $this->setExpectedException('InvalidArgumentException');
        $cache = new FileCache($options);
    }

    public function testSetFilePermissionsThrowsWhenPermissionsIsNotWritable()
    {
        $options = [
            'enabled'          => false,
            'file_permissions' => 0500,
        ];
        $this->setExpectedException('InvalidArgumentException');
        $cache = new FileCache($options);
    }

    public function testGetFilePermissions()
    {
        $options = [
            'enabled'          => false,
            'file_permissions' => 0777,
        ];
        $cache = new FileCache($options);
        $this->assertEquals($cache->getFilePermissions(), 0777);
    }

    public function testSetIfCacheIsDisabled()
    {
        $options = [
            'enabled' => false,
            'basedir' => $this->tempDir,
        ];
        $cache  = new FileCache($options);
        $result = $cache->set('foo', 'bar', 1000);
        $this->assertNull($result);

        $path = $this->getPath($cache, 'foo');
        $this->assertFalse(file_exists($path));
    }

    public function testSetSuccess()
    {
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache  = new FileCache($options);
        $result = $cache->set('bar', 'baz', 1000);
        $this->assertTrue($result);

        $path = $this->getPath($cache, 'bar');
        $this->assertTrue(file_exists($path));
        $this->assertTrue(filemtime($path) > time());
    }

    public function testSetSuccessWithDefaultTtl()
    {
        $options = [
            'enabled'     => true,
            'basedir'     => $this->tempDir,
            'default_ttl' => '1000',
        ];
        $cache  = new FileCache($options);
        $result = $cache->set('bar', 'baz');
        $this->assertTrue($result);

        $path = $this->getPath($cache, 'bar');
        $this->assertTrue(file_exists($path));
        $this->assertTrue(filemtime($path) > time());
    }

    public function testSetFailed()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->markTestSkipped('Not testable on windows.');
        }
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('baz', 'bat', 1000);

        $file = $this->getPath($cache, 'baz');
        chmod($file, 0500);

        $result = $cache->set('baz', 'ban', 1000);
        $this->assertFalse($result);
        // FileCache removes bad file
        $this->assertFalse(file_exists($file));
    }

    public function testGetIfCacheIsDisabled()
    {
        $options = [
            'enabled' => false,
            'basedir' => $this->tempDir,
        ];
        $cache  = new FileCache($options);
        $result = $cache->get('foo');
        $this->assertNull($result);
    }

    public function testGetSuccess()
    {
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('bar', 'baz', 1000);

        $result = $cache->get('bar');
        $this->assertEquals($result, 'baz');
    }

    public function testGetFailedIfDataNotExists()
    {
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);

        $result = $cache->get('bag');
        $this->assertFalse($result);
    }

    public function testGetTryToRemoveExpires()
    {
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = $this
            ->getMockBuilder(FileCache::CLASS)
            ->setConstructorArgs([$options])
            ->setMethods(['remove'])
            ->getMock();

        $cache->set('foo', 'foo', 1);
        sleep(2);

        $cache
            ->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo('foo'));

        $result = $cache->get('foo');
        $this->assertFalse($result);
    }

    public function testGetFailed()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->markTestSkipped('Not testable on windows.');
        }
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('baz', 'bat', 1000);

        $file = $this->getPath($cache, 'baz');
        chmod($file, 0300);

        $result = $cache->get('baz');
        $this->assertFalse($result);
    }

    public function testRemoveIfCacheIsDisabled()
    {
        $options = [
            'enabled' => false,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);

        $result = $cache->remove('foo');
        $this->assertNull($result);
    }

    public function testRemoveSuccess()
    {
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);

        $cache->set('baz', 'bar', 1000);
        $result = $cache->remove('baz');
        $this->assertTrue($result);
    }

    public function testRemoveReturnsTrueIfDataNotExists()
    {
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $this->assertTrue($cache->remove('foo'));
    }

    public function testRemoveFailed()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->markTestSkipped('Not testable on windows.');
        }
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('baz', 'bar', 1000);

        $path = $this->getPath($cache);
        chmod($path, 0100);
        $result = $cache->remove('baz');
        $this->assertFalse($result);
    }

    public function testClearNamespaceIfCacheIsDisabled()
    {
        $options = [
            'enabled' => false,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('foo', 'bar', 1000);
        $cache->set('baz', 'bag', 1000);
        $result = $cache->clearNamespace();
        $this->assertNull($result);
    }

    public function testClearNamespaceSuccess()
    {
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('foo', 'bar', 1000);
        $cache->set('bar', 'baz', 1000);

        $result = $cache->clearNamespace();
        $this->assertTrue($result);

        $path = $this->getPath($cache);
        $this->assertEquals(count(scandir($path)), 2);
    }

    public function testClearNamespaceFailed()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->markTestSkipped('Not testable on windows.');
        }
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('foo', 'bar', 1000);

        $path = $this->getPath($cache);
        chmod($path, 0500);

        $result = $cache->clearNamespace();
        $this->assertFalse($result);
    }

    public function testClearExpiredIfCacheIsDisabled()
    {
        $options = [
            'enabled' => false,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('foo', 'bar', 1);
        $cache->set('bar', 'baz', 1);
        $result = $cache->clearExpired();
        $this->assertNull($result);
    }

    public function testClearExpiredSuccess()
    {
        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('foo', 'bar', 1);
        $cache->set('bar', 'baz', 1);

        sleep(2);

        $result = $cache->clearExpired();
        $this->assertTrue($result);

        $path = $this->getPath($cache);
        $this->assertEquals(count(scandir($path)), 2);
    }

    public function testClearExpiredFailed()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->markTestSkipped('Not testable on windows.');
        }

        $options = [
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = new FileCache($options);
        $cache->set('foo', 'bar', 1);
        $cache->set('bar', 'baz', 1);

        $path = $this->getPath($cache);
        chmod($path, 0500);

        sleep(2);

        $result = $cache->clearExpired();
        $this->assertFalse($result);
    }

    public function testDestructorClearsExpired()
    {
        $options = [
            'gc'      => 1,
            'enabled' => true,
            'basedir' => $this->tempDir,
        ];
        $cache = $this
            ->getMockBuilder(FileCache::CLASS)
            ->setConstructorArgs([$options])
            ->setMethods(['clearExpired'])
            ->getMock();

        $cache
            ->expects($this->once())
            ->method('clearExpired');

        $cache->__destruct();
    }

    protected function getPath(FileCache $cache, $fileName = '')
    {
        $basedir   = $cache->getBaseDir();
        $namespace = $cache->getNamespace();
        $algorithm = $cache->getHashingAlgorithm();

        $hash = function ($name) use ($algorithm) {
            return hash($algorithm, $name);
        };
        $path = $basedir . DIRECTORY_SEPARATOR . $hash($namespace);
        if ($fileName) {
            $path .= DIRECTORY_SEPARATOR . $hash($fileName) . '.dat';

            return $path;
        }

        return $path;
    }

    protected function removeRecursive($dir)
    {
        if (file_exists($dir)) {
            $dirIt = new DirectoryIterator($dir);
            foreach ($dirIt as $entry) {
                $fname = $entry->getFilename();
                if ($fname == '.' || $fname == '..') {
                    continue;
                }

                if ($entry->isFile()) {
                    unlink($entry->getPathname());
                } else {
                    chmod($entry->getPathname(), 0700);
                    $this->removeRecursive($entry->getPathname());
                }
            }
            rmdir($dir);
        }
    }
}

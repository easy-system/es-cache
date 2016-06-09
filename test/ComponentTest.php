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

use Es\Cache\Component;

class ComponentTest extends \PHPUnit_Framework_TestCase
{
    public function testGetVersion()
    {
        $component = new Component();
        $version   = $component->getVersion();
        $this->assertInternalType('string', $version);
        $this->assertRegExp('#\d+.\d+.\d+#', $version);
    }
}

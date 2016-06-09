<?php
/**
 * This file is part of the "Easy System" package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Damon Smith <damon.easy.system@gmail.com>
 */
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';

    return;
}
if (file_exists('../../autoload.php')) {
    require_once '../../autoload.php';

    return;
}

throw new \RuntimeException('Autoloader not found.');

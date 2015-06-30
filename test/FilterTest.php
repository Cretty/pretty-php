<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../src/v.inc.php';

use \net\shawn_huang\pretty as p;
use \net\shawn_huang\pretty\Config;
/**
 * @runInSeparateProcess
 */
class FilterTest extends \PHPUnit_Framework_TestCase {

    private $baseConfig;
    public function setup() {
        $this->baseConfig = [
            'class.path' => __DIR__ . '/test_classes',
        ];
    }

    public function testByPathInfo() {
        $_SERVER['PATH_INFO'] = '/sub/index';
        $this->expectOutputString(json_encode(['foo' => 'foo', 'holy' => 'crap']));
        p\Framework::instance($this->baseConfig)->start();

    }

}
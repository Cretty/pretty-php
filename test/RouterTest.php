<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../src/Pretty4.inc.php';
use \net\shawn_huang\pretty as p;
use \net\shawn_huang\pretty\Config;

/**
 * @runInSeparateProcess
 */
class RouterTest extends \PHPUnit_Framework_TestCase {

    private $baseConfig;
    public function setUp() {
        parent::setUp();
        $this->baseConfig = [
            'class.path' => __DIR__ . '/test_classes',
            'class.actionNamespace' => '\\action',
            'class.namespace' => '\\'
        ];
    }

    public function testByPathInfo() {
        $_SERVER['PATH_INFO'] = '/index';
        $this->expectOutputString(json_encode(['foo' => 'bar', 'holy' => 'crap']));
        p\Framework::instance($this->baseConfig)->start();
    }

    public function testNotFound() {
        $_SERVER['PATH_INFO'] = 'notfound';
        $this->expectOutputString("The url you requested: [notfound] was not found.\r\n");
        p\Framework::instance($this->baseConfig)->start();
    }

}
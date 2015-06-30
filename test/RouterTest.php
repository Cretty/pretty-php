<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../src/v.inc.php';
use \net\shawn_huang\pretty as p;
use \net\shawn_huang\pretty\Config;
use \net\shawn_huang\pretty\Consts;

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

    public function testStaticClassic() {
        $this->baseConfig[Consts::CONF_ROUTER_MAPPINGS] = [
            # Classic action
            '/\/static-classic/' => '@action.sub.Index'
        ];
        $_SERVER['PATH_INFO'] = '/static-classic';
        $this->expectOutputString(json_encode(['holy' => 'shit']));
        p\Framework::instance($this->baseConfig)->start();
    }

    public function testStaticV() {
        $this->baseConfig[Consts::CONF_ROUTER_MAPPINGS] = [
            # V action
            '/\/static-v/' => '@action.sub.V'
        ];
        $_SERVER['PATH_INFO'] = '/static-v';
        $this->expectOutputString(json_encode(['foo' => 'bar']));
        p\V::bind();
        p\Framework::instance($this->baseConfig)->start();
    }

}
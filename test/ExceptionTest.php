<?php


error_reporting(E_ALL);
ini_set('display_errors', 'on');
use \net\shawn_huang\pretty as p;
require_once '../src/v.inc.php';
/**
 * @runInSeparateProcess
 */
class ExceptionTest extends \PHPUnit_Framework_TestCase {

    protected $preserveGlobalState = FALSE;

    private $config;

    public function setUp() {
        $this->config = [
            'class.path' => __DIR__ . '/test_classes'
        ];
    }

    public function testErr0() {
        $f = p\Framework::instance($this->config);
        $_SERVER['PATH_INFO'] = '/err0';
        $this->expectOutputString('{"foo":"bar"}');
        $f->start();
    }

    public function testErr1() {
        $f = p\Framework::instance($this->config);
        $_SERVER['PATH_INFO'] = '/err1';
        $this->expectOutputString("error 1\r\n");
        $f->start();
    }
}
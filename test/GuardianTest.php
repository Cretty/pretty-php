<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../src/v.inc.php';
use \net\shawn_huang\pretty as p;
use \net\shawn_huang\pretty\Config;
/**
 * @runInSeparateProcess
 */
class GuardianTest extends \PHPUnit_Framework_TestCase {

    protected $preserveGlobalState = FALSE;

    private $config;

    public function setup() {
        $this->config = [
            'class.path' => dirname(__FILE__) . '/test_classes',
            'site.entrance' => 'GuardianTest.php',
            'guardians.mappings' => [
                '/^\/abc$/' => '@.guardians.Guardian0',
                '/.*/' => '@.guardians.Guardian1'
            ]
        ];
    }

    public function testGuardian0() {
        $_SERVER['REQUEST_URI'] = '/GuardianText.php/abc';
        $this->expectOutputString('OK-0OK-0OK-0OK-0OK-0OK - 2');
        p\Framework::instance($this->config)->start();
    }

    public function testGuardian1() {
        $_SERVER['REQUEST_URI'] = '/GuardianText.php/what';
        $this->expectOutputString('OK - 2');
        p\Framework::instance($this->config)->start();
    }
}

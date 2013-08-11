<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../src/Pretty4.inc.php';
use \net\shawn_huang\pretty as p;
use \net\shawn_huang\pretty\Config;
/**
 * @runInSeparateProcess
 * @runTestsInSeparateProcess
 */
class ClassLoaderTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        Config::initDefault([
            'class.aliasLimit' => 10,
            'class.path' => __DIR__ . '/test_classes',
            'class.actionNamespace' => '\action',
            'class.alias' =>[
                'Router' => '@%SmartRouter'
            ],
            'class.lib' => $_SERVER['DOCUMENT_ROOT'] . '/lib'
        ]);
    }

    public function testAll() {

        // Config::initDefault([]);
        // Config::put('class.aliasLimit', 10);
        // Config::put('class.alias', [
        //     'Router' => '@%SmartRouter'
        // ]);
        // Config::put('class.path', dirname(__FILE__) . '/test_classes');
        // Config::put('class.actionNamespace', '\action');
        // Config::put('class.lib', $_SERVER['DOCUMENT_ROOT'] . '/lib');

        $cl = new p\ClassLoader;
        $obj = $cl->load('@*Router', true, true);
        
        $this->assertTrue(class_exists('\net\shawn_huang\pretty\Action'));
        $this->assertTrue(is_a($obj, '\net\shawn_huang\pretty\SmartRouter'));
        $this->assertTrue(is_a($obj->classLoader, '\net\shawn_huang\pretty\ClassLoader'));

        // $this->assertTrue($cl->loadDefinition('@.action.Index'));
        $this->assertTrue($cl->loadDefinition('\action\Index'));
        $action  = $cl->load('\action\Index', 1, 1);
        // $this->assertTrue(is_a($action, '\action\Index'));
    }

    public function testDomain() {
        $this->assertFalse(class_exists('\action\Index', false));
        $cl = new p\ClassLoader;
        $act = $cl->load('@.action.Index');
        $this->assertTrue(class_exists('\action\Index', false));
        $this->assertTrue(is_a($act, '\action\Index'));
    }

    public function testViews() {
        Config::initDefault([]);
        $cl = new p\ClassLoader();
        $view = $cl->load('@%view.JsonView');
        $this->assertTrue(is_a($view, '\net\shawn_huang\pretty\view\JsonView'));
    }

}
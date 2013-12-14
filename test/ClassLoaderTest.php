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
            'class.lib' => __DIR__ . '/test_lib'
        ]);
    }

    public function testAll() {
        $cl = new p\ClassLoader;
        $obj = $cl->load('@*Router', true, true);
        
        $this->assertTrue(class_exists('\net\shawn_huang\pretty\Action'));
        $this->assertTrue(is_a($obj, '\net\shawn_huang\pretty\SmartRouter'));
        $this->assertTrue(is_a($obj->classLoader, '\net\shawn_huang\pretty\ClassLoader'));

        $this->assertTrue($cl->loadDefinition('\action\Index'));
        $action  = $cl->load('\action\Index', 1, 1);
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

    public function testWarning() {
        $cl = new p\ClassLoader();
        //Class[@nothing, /Users/Shawn/Documents/Projects/php/pretty-4/test/test_classes/nothing] not found.
        $message = 'Class[@nothing, ' . __DIR__ . '/test_classes/nothing] not found.';
        try {
            $cl->load('@nothing', true);
            $this->assertFalse(1);
        } catch (\Exception $exp) {
            $this->assertEquals($message, $exp->getMessage());
        }
    }

    public function testExplainClasses() {
        # test domain
        $cl = new p\ClassLoader();
        $str = '@foo';
        $arr = $cl->explainClasses($str);
        $expect = [
            'isClass' => true,
            'isValue' => false,
            'name' => '\foo',
            'type' => p\CLASS_TYPE_DOMAIN,
            'isNew' => false,
            'file' => __DIR__ . '/test_classes/foo',
            'preloads' => null,
            'args' => null,
            'value' => null,
            'errors' => null,
            'loadChildren' => true,
            'origin' => '@foo'
        ];
        $this->assertEquals($expect, $arr);

        # test pretty
        $str = '@%foo';
        $arr = $cl->explainClasses($str);
        $expect = [
            'isClass' => true,
            'isValue' => false,
            'name' => '\net\shawn_huang\pretty\foo',
            'type' => p\CLASS_TYPE_PRETTY,
            'isNew' => false,
            'file' => dirname(__DIR__) . '/src/foo',
            'preloads' => null,
            'args' => null,
            'value' => null,
            'errors' => null,
            'loadChildren' => true,
            'origin' => '@%foo'
        ];
        $this->assertEquals($expect, $arr);

        # test domain
        $str = '\foo';
        $arr = $cl->explainClasses($str);
        $expect = [
            'isClass' => true,
            'isValue' => false,
            'name' => '\foo',
            'type' => p\CLASS_TYPE_DOMAIN,
            'isNew' => false,
            'file' => __DIR__ . '/test_classes/foo',
            'preloads' => null,
            'args' => null,
            'value' => null,
            'errors' => null,
            'loadChildren' => true,
            'origin' => '\foo'
        ];
        $this->assertEquals($expect, $arr);

    }

}
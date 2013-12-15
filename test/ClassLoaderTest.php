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

    public function testLib() {
        Config::put('class.namespace', '\aa');
        $cl = new p\ClassLoader();
        $expr = '@LibClass';
        $lib = $cl->load($expr);
        $this->assertTrue(is_a($lib, '\LibClass'));
        $lib = $cl->load('@LibClass2', false, false);
        $this->assertNull($lib);
    }

    public function testWarning() {
        $cl = new p\ClassLoader();
        //Class[@nothing, /Users/Shawn/Documents/Projects/php/pretty-4/test/test_classes/nothing] not found.
        $message = 'Class[@nothing, ' . __DIR__ . '/test_classes/nothing.class.php|.interface.php|.php] not found.';
        try {
            $cl->load('@nothing', true);
            $this->assertFalse(1);
        } catch (\Exception $exp) {
            $this->assertEquals($message, $exp->getMessage());
        }
    }

    public function testClassTemplate() {
        $cl = new p\ClassLoader();
        $expect = [
            'isClass' => false,
            'isValue' => false,
            'name' => null,
            'type' => null,
            'isNew' => false,
            'file' => null,
            'preloads' => null,
            'args' => null,
            'value' => null,
            'errors' => null,
            'loadChildren' => true,
            'origin' => null
        ];
        $this->assertEquals($expect, $cl->classTemplate(null));
        $expect['name'] = '123';
        $this->assertEquals($expect, $cl->classTemplate(null, ['name' => '123']));
    }

    public function testExplainClasses() {
        # test domain
        $cl = new p\ClassLoader();
        $str = '@foo';
        $arr = $cl->explainClasses($str);
        $expect = $cl->classTemplate($str, [
            'name' => '\foo',
            'isClass' => true,
            'type' => p\CLASS_TYPE_DOMAIN,
            'file' => __DIR__ . '/test_classes/foo'
        ]);
        $this->assertEquals($expect, $arr);

        # test pretty
        $str = '@%foo';
        $arr = $cl->explainClasses($str);
        $expect = $cl->classTemplate($str, [
            'name' => '\net\shawn_huang\pretty\foo',
            'isClass' => true,
            'type' => p\CLASS_TYPE_PRETTY,
            'file' => dirname(__DIR__) . '/src/foo'
        ]);
        $this->assertEquals($expect, $arr);

        # test domain
        $str = '\foo';
        $arr = $cl->explainClasses($str);
        $expect = $cl->classTemplate($str, [
            'name' => '\foo',
            'isClass' => true,
            'type' => p\CLASS_TYPE_DOMAIN,
            'file' => __DIR__ . '/test_classes/foo'
        ]);
        $this->assertEquals($expect, $arr);

        # test absolute
        p\Config::put('class.namespace', '\aa');

        $cl = new p\ClassLoader();
        $str = '\foo';
        $arr = $cl->explainClasses($str);
        $expect = $cl->classTemplate($str, [
            'name' => '\foo',
            'isClass' => true,
            'type' => p\CLASS_TYPE_ABSOLUTE,
            'file' => __DIR__ . '/test_lib/foo'
        ]);
        $this->assertEquals($expect, $arr);
        $str = '@foo';
        $arr = $cl->explainClasses($str);
        $expect['origin'] = '@foo';
        $this->assertEquals($expect, $arr);

        # test domain
        $str = '@.foo';
        $arr = $cl->explainClasses($str);
        $expect = $expect = $cl->classTemplate($str, [
            'name' => '\aa\foo',
            'isClass' => true,
            'type' => p\CLASS_TYPE_DOMAIN,
            'file' => __DIR__ . '/test_classes/foo'
        ]);
        $this->assertEquals($expect, $arr);
    }

}
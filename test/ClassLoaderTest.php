<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../src/v.inc.php';
use \net\shawn_huang\pretty as p;
use \net\shawn_huang\pretty\Config;
/**
 * @runInSeparateProcess
 * @runTestsInSeparateProcess
 */
class ClassLoaderTest extends \PHPUnit_Framework_TestCase {
    protected $preserveGlobalState = FALSE;

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

    public function testGeneral() {
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

    public function testReference() {
        $cl = new p\ClassLoader();
        $foo1 = $cl->load('@.Foo<123321');
        $foo2 = $cl->load('@.Foo<abc');
        $foo3 = $cl->load('@.+Foo');
        $foo4 = $cl->load('@.Foo');
        $foo5 = $cl->load('@.Foo<abc');
        $foo6 = $cl->load('@.Foo');

        $foo1->b = 1;
        $foo2->b = 2;
        $foo3->b = 3;
        $foo4->b = 4;
        $foo5->b = 5;
        $foo6->b = 6;

        $this->assertEquals($foo1->a, '123321');
        $this->assertEquals($foo1->b, 1);

        $this->assertEquals($foo2->a, 'abc');
        $this->assertEquals($foo2->b, 2);

        $this->assertEquals($foo3->a, 'a');
        $this->assertEquals($foo3->b, 3);

        $this->assertEquals($foo4->a, 'a');
        $this->assertEquals($foo4->b, 6);

        $this->assertEquals($foo5->a, 'abc');
        $this->assertEquals($foo5->b, 5);

        $this->assertEquals($foo6->a, 'a');
        $this->assertEquals($foo6->b, 6);
    }

    public function testConfig() {
        Config::put('foo', 'bar');
        $cl = new p\ClassLoader();
        $val = $cl->load('@#foo');
        $this->assertEquals('bar', $val);

        $val = $cl->load('@#nothing');
        $this->assertEquals('@#nothing', $val);

        Config::put('isFalse', false);
        $val = $cl->load('@#isFalse');
        $this->assertFalse($val);

        $val = $cl->load('@#class.aliasLimit');
        $this->assertEquals($val, Config::get('class.aliasLimit'));
    }

    public function testautoload() {
        $cl = new p\classloader();
        $cl->forkAutoload();
        $obj = new \foo();
        $this->assertnotnull($obj);
        $obj = new \action\Index;
        $this->assertnotnull($obj);
    }

}

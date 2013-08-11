<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../src/Arrays.class.php';
use \net\shawn_huang\pretty\Arrays;

/**
 * @runInSeparateProcess
 */
class ArraysTest extends \PHPUnit_Framework_TestCase {

    private $array;

    public function setUp() {
        parent::setUp();
        $this->array = array(
            'foo' => 'bar'
        );
    }

    public function testValueFrom() {
        $this->assertEquals(Arrays::valueFrom($this->array, 'foo'), 'bar');
        $this->assertNull(Arrays::valueFrom($this->array, 'bar'));
    }

    public function testPut() {
        Arrays::putMissingInto($this->array, 'hello', 'world');
        $this->assertEquals($this->array['hello'], 'world');
        Arrays::putMissingInto($this->array, 'foo', 'bar bar');
        $this->assertEquals($this->array['foo'], 'bar');
    }

    public function testValueCopy() {
        $copy = new Arrays($this->array);
        $copy->put('foo', 'bar bar');
        $this->assertEquals($copy->get('foo'), 'bar bar');
        $this->assertNull($copy->get('nothing'));
        $this->assertEquals($this->array['foo'], 'bar');
    }

    public function testReference() {
        $ref = new Arrays($this->array, true);
        $this->array['yes'] = 'no';
        $this->assertEquals($ref->get('yes'), 'no');
        $ref->put('yes', 'yes');
        $this->assertEquals($this->array['yes'], 'yes');
    }

    public function testRemove() {
        $copy = new Arrays($this->array);
        $copy->remove('foo');
        $this->assertNull($copy->get('foo'));
        $this->assertEquals($this->array['foo'], 'bar');

        $ref = new Arrays($this->array, true);
        $ref->remove('foo');
        $this->assertNull($copy->get('foo'));
        $this->assertFalse(isset($this->array['foo']));
        $this->array['foo'] = 'bar';
    }
 
    public function testReplace() {
        $old = [
            'a' => 'a',
            'b' => 'b'
        ];

        $new = [
            'a' => 'b',
            'c' => 'c'
        ];
        $copy = new Arrays($old);
        $copy->mergeWith($new);
        $this->assertEquals($copy->get('a'), 'b');
        $this->assertEquals($copy->get('b'), 'b');
        $this->assertEquals($copy->get('c'), 'c');

        $copy = new Arrays($old);
        $copy->mergeWith($new, false);
        $this->assertEquals($copy->get('a'), 'a');
        $this->assertEquals($copy->get('b'), 'b');
        $this->assertEquals($copy->get('c'), 'c');

        $oldCopy = $old;
        $copy = new Arrays($oldCopy, true);
        $copy->mergeWith($new);
        $this->assertEquals($copy->get('a'), 'b');
        $this->assertEquals($copy->get('b'), 'b');
        $this->assertEquals($copy->get('c'), 'c');

        $oldCopy2 = $old;
        $copy = new Arrays($oldCopy2, true);
        $copy->mergeWith($new);
        $this->assertEquals($copy->get('a'), 'b');
        $this->assertEquals($copy->get('b'), 'b');
        $this->assertEquals($copy->get('c'), 'c');
    }
}
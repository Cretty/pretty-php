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

    public function testMerge() {
        $first = [
            'a' => [1, 2, 3],
            'b' => 1,
            'c' => 1,
            'e' => [1, 2],
            'f' => ['a' => 1, 'b' => 2]
        ];
        $second = [
            'a' => 4, 
            'b' => ['a', 'b'],
            'c' => 'c',
            'd',
            'e' => [3],
            'f' => ['a' => 4, 'c' => 1]
        ];

        $expects0 = [
            'a' => 4,
            'b' => [
                0 => 'a',
                1 => 'b',
            ],
            'c' => 'c',
            'e' => [
                0 => 3,
                1 => 2,
            ],
            'f' => [
                'a' => 4,
                'b' => 2,
                'c' => 1,
            ],
            0 => 'd',
        ];

        $expects1 = [
            'a' => [
                0 => 1,
                1 => 2,
                2 => 3
            ],
            'b' => 1,
            'c' => 1,
            0 => 'd',
            'e' => [
                0 => 1,
                1 => 2
            ],
            'f' => [
                'a' => 1,
                'c' => 1,
                'b' => 2
            ]
        ];
        $class = new \ReflectionClass('\net\shawn_huang\pretty\Arrays');
        $property = $class->getProperty('store');
        $property->setAccessible(true);

        $arr0 = new Arrays($first);
        $arr0->mergeWith($second); 
        $this->assertEquals($expects0, $property->getValue($arr0));

        $arr1 = new Arrays($second);
        $arr1->mergeWith($first); 
        $this->assertEquals($expects1, $property->getValue($arr1));

        $arr3 = new Arrays($first);
        $arr3->mergeWith($second, false);
        $this->assertEquals($expects1, $property->getValue($arr3));

        $arr4 = new Arrays($second);
        $arr4->mergeWith($first, false);
        $this->assertEquals($expects0, $property->getValue($arr4));

        $firstCopy = $first;
        $ref0 = new Arrays($firstCopy, true);
        $ref0->mergeWith($second);
        $this->assertEquals($firstCopy, $expects0);

        $firstCopy = $first;
        $ref1 = new Arrays($firstCopy, true);
        $ref1->mergeWith($second, false);
        $this->assertEquals($firstCopy, $expects1);

        $secondCopy = $second;
        $ref2 = new Arrays($secondCopy, true);
        $ref2->mergeWith($first);
        $this->assertEquals($secondCopy, $expects1);

        $secondCopy = $second;
        $ref3 = new Arrays($secondCopy, true);
        $ref3->mergeWith($first, false);
        $this->assertEquals($secondCopy, $expects0);
    }
}
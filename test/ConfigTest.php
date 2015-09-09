<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../src/Arrays.class.php';
require_once '../src/Config.class.php';
use \net\shawn_huang\pretty\Arrays;
use \net\shawn_huang\pretty\Config;
/**
 * @runInSeparateProcess
 */
class ConfigTest extends \PHPUnit_Framework_TestCase {

    protected $preserveGlobalState = FALSE;

    public function setUp() {
        parent::setUp();
        Config::initDefault([
            'a' => 'a',
            'b' => 'b'
        ]);
    }

    public function testDefault() {
        $this->assertEquals(Config::get('a'), 'a');
        Config::put('c', 'c');
        $this->assertEquals(Config::get('c'), 'c');
        Config::putMissing('d', 'd');
        Config::putMissing('a', 'd');
        $this->assertEquals(Config::get('d'), 'd');
        $this->assertEquals(Config::get('a'), 'a');

        Config::remove('c');
        Config::remove('d');
        $this->assertNull(Config::get('c'));
        $this->assertNull(Config::get('d'));
        $new = [
            'c' => 'c',
            'a' => 'b'
        ];
        Config::mergeWith($new);

        $this->assertEquals(Config::get('c'), 'c');
        $this->assertEquals(Config::get('a'), 'b');

        Config::put('a', 'a');
        Config::remove('c');
        Config::mergeWith($new, false);
        $this->assertEquals(Config::get('c'), 'c');
        $this->assertEquals(Config::get('a'), 'a');

        Config::remove('c');
    }

    public function testScope() {
        $config = Config::pick('config');
        $config->put('f', 'f');
        $this->assertNull(Config::get('f'));
        $this->assertEquals($config->get('f'), 'f');
        $this->assertEquals(Config::pick('config')->get('f'), 'f');

        # test switch
        $this->assertEquals(Config::get('a'), 'a');
        Config::switchTo('config');
        $this->assertEquals(Config::get('f'), 'f');
        $config->put('e', 'e');
        $this->assertEquals(Config::get('e'), 'e');
        $this->assertNull(Config::get('a'));
        $this->assertEquals(Config::pick('default')->get('a'), 'a');
        Config::switchTo('default');
        $this->assertEquals(Config::get('a'), 'a');
    }
}
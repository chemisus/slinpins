<?php
/**
 *
 * @author Terrence Howard <chemisus@gmail.com>
 * @package Slinpins
 * @subpackage Test
 * @copyright (c) 2012, Terrence Howard
 */

require_once('../src/'.\str_replace('Test', '', \basename(__FILE__)));

class MockTest {
    public function __construct($a, $b) {
        $this->a = $a;

        $this->b = $b;
    }

    /**
     *
     * @inject d
     * @inject e
     * @inject f
     *
     * @param type $a
     * @param type $b
     * @param type $c
     */
    public function method($a, $b, $c) {

    }
}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-10-31 at 01:50:25.
 */
class ScopeTest extends PHPUnit_Framework_TestCase {
    public static function keysProvider() {
        return array(
            array(function ($a, $b, $c) {}, array(), array(), array('a', 'b', 'c')),
            array(function ($a, $b, $c) {}, array('d', 'e'), array(), array('d', 'e', 'c')),
            array(function ($a, $b, $c) {}, array('d', 'e'), array('f'), array('f', 'e', 'c')),
            array(function ($a, $b, $c) {}, array(1=>'e', 0=>'d'), array('f'), array('f', 'e', 'c')),
            array(function ($a, $b, $c) {}, array(1=>'e', 0=>'d'), array('a'=>'f'), array('f', 'e', 'c')),
            array(function ($a, $b, $c) {}, array('b'=>'e', 'a'=>'d'), array('a'=>'f'), array('f', 'e', 'c')),
        );
    }

    public static function injectProvider() {
        return array(
            array('sprintf', array(0=>'a%s', 1=>'b', 'format'=>'c%s', 'arg1'=>'d', 'e%s', 'f'), array(0=>'g%s', 1=>'h', 'format'=>'i%s', 'arg1'=>'j', 'k%s', 'l'), 'ij'),

            array('sprintf', array(0=>'a%s'), array(), 'a'),
            array('sprintf', array(0=>'a%s', 1=>'b'), array(), 'ab'),
            array('sprintf', array(0=>'a%s', 1=>'b'), array(0=>'g%s'), 'gb'),
            array('sprintf', array(0=>'a%s', 1=>'b'), array(0=>'g%s', 1=>'h'), 'gh'),
            array('sprintf', array(), array(0=>'g%s'), 'g'),
            array('sprintf', array(), array(0=>'g%s', 1=>'h'), 'gh'),
            array('sprintf', array(0=>'a%s'), array(1=>'h'), 'ah'),
            array('sprintf', array(0=>'a%s', 1=>'b'), array(1=>'h'), 'ah'),

            array('sprintf', array('e%s'), array(), 'e'),
            array('sprintf', array('e%s', 'f'), array(), 'ef'),
            array('sprintf', array('e%s', 'f'), array('k%s'), 'kf'),
            array('sprintf', array('e%s', 'f'), array('k%s', 'l'), 'kl'),

            array('sprintf', array(), array('k%s'), 'k'),
            array('sprintf', array(), array('k%s', 'l'), 'kl'),
        );
    }

    public static function instanceProvider() {
        return array(
            array('MockTest', array('a', 'b'), array(), array('a'=>'a', 'b'=>'b')),
            array('MockTest', array('a'), array(1=>'b'), array('a'=>'a', 'b'=>'b')),
            array('MockTest', array(1=>'b'), array('a'), array('a'=>'a', 'b'=>'b')),
            array('MockTest', array('a', 'b'), array('c'), array('a'=>'c', 'b'=>'b')),
            array('MockTest', array('a', 'b'), array('c', 'd'), array('a'=>'c', 'b'=>'d')),
            array('MockTest', array('a', 'b'), array(1=>'d'), array('a'=>'a', 'b'=>'d')),
        );
    }

    public static function methodProvider() {
        return array(
            array('a', function () {return 'a';}, array(), array(), 'a')
        );
    }

    public static function variableProvider() {
        return array(
            array('a', function () {return 'a';}, array(), 'a')
        );
    }

    public static function serviceProvider() {
        return array(
            array('a', function () {return 'a';}, array(), 'a')
        );
    }

    public static function constantProvider() {
        return array(
            array('a', 'a'),
        );
    }

    /**
     * @var Scope
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new \Scope();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {

    }

    public function test() {
        $this->object->service('test', function ($instance) {
            $factory = $instance('MockTest');
            
            $instance = $factory();
            
            return $instance();
        });
        
        $this->object->constant('a', 'a');
        
        var_dump($this->object->fetch('test'));
    }

    /**
     * @covers Scope::inject
     * @dataProvider keysProvider
     */
    public function testKeys($method, $annotations, $injects, $expect) {
        $reflection = $this->object->reflectFunction($method);

        $parameters = $this->object->parameters($reflection->getParameters());

        $result = $this->object->keys($parameters, $annotations, $injects);

        $this->assertEquals($expect, $result);
    }

    /**
     * @covers Scope::inject
     * @dataProvider injectProvider
     */
    public function testInject($method, $params, $locals, $expect) {
        $callback = $this->object->inject($method, $params);

        $inject = $callback($this->object);

        $result = $inject($locals);

        $this->assertEquals($expect, $result);
    }

    /**
     * @covers Scope::instance
     * @dataProvider instanceProvider
     */
    public function testInstance($class, $params, $locals, $gets) {
        $callback = $this->object->instance($class, $params);

        $instance = $callback($this->object);

        $object = $instance($locals);

        $this->assertInstanceOf($class, $object);

        foreach ($gets as $key=>$value) {
            $this->assertEquals($value, $object->{$key});
        }
    }

    /**
     * @covers Scope::method
     * @dataProvider methodProvider
     */
    public function testMethod($key, $value, $params, $locals, $expect) {
        $this->object->method($key, $value, $params);

        $inject = $this->object->fetch($key);

        $result = $inject($locals);

        $this->assertEquals($expect, $result);
    }

    /**
     * @covers Scope::variable
     * @dataProvider variableProvider
     */
    public function testVariable($key, $value, $params, $expect) {
        $this->object->variable($key, $value, $params);

        $result = $this->object->fetch($key);

        $this->assertEquals($expect, $result);
    }

    /**
     * @covers Scope::constant
     * @dataProvider constantProvider
     */
    public function testConstant($key, $value) {
        $this->object->constant($key, $value);

        $result = $this->object->fetch($key);

        $this->assertEquals($value, $result);
    }

    /**
     * @covers Scope::factory
     * @dataProvider instanceProvider
     */
    public function testFactory($class, $params, $locals, $gets) {
        $this->object->factory($class.'Factory', $class, $params);

        $instance = $this->object->fetch($class.'Factory');

        $object = $instance($locals);

        $this->assertInstanceOf($class, $object);

        foreach ($gets as $key=>$value) {
            $this->assertEquals($value, $object->{$key});
        }
    }

    /**
     * @covers Scope::service
     * @dataProvider serviceProvider
     */
    public function testService($key, $value, $params, $expect) {
        $this->object->variable($key, $value, $params);

        $result = $this->object->fetch($key);

        $this->assertEquals($expect, $result);
    }

}

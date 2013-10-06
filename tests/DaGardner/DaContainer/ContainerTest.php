<?php
/*
 * (c) Christian Gärtner <christiangaertner.film@googlemail.com>
 * This file is part of the Modulework Framework Tests
 * License: View distributed LICENSE file
 *
 * 
 * This file is meant to be used in PHPUnit Tests
 */

use DaGardner\DaContainer\Container;

/**
* PHPUnit Test
*/
class ContainerTest extends PHPUnit_Framework_TestCase
{

    public function testGeneral()
    {
        $interfaces = class_implements(new Container);

        $this->assertTrue(in_array('ArrayAccess', $interfaces), 'DaContainer should support ArrayAccess');
        $this->assertTrue(in_array('DaGardner\DaContainer\ResolverInterface', $interfaces), 'DaContainer should implement the Resolver interface');
    }

    public function testClosureBinding()
    {
        $con = new Container;
        $con->bind('foo', function () {
            return 'Bar';
        });

        $this->assertEquals('Bar', $con->resolve('foo'));
    }

    public function testSingletonClosureBinding()
    {
        $con = new Container;
        $obj = new StdClass;

        $con->singleton('foo', function () use ($obj) {
            return $obj;
        });

        $this->assertEquals($obj, $con->resolve('foo'));
    }

    public function testNonBindedConreteResolving()
    {
        $con = new Container;

        $this->assertInstanceOf('ConcreteStub', $con->resolve('ConcreteStub'));
    }

    public function testSingeltonConrecteResolving()
    {
        $con = new Container;
        $con->singleton('ConcreteStub');

        $this->assertEquals($con->resolve('ConcreteStub'), $con->resolve('ConcreteStub'));
    }

    public function testContainerGettingPassedToResolver()
    {
        $con = new Container;
        $con->bind('foo', function($container) {
            return $container;
        });

        $c = $con->resolve('foo');

        $this->assertEquals($c, $con);
    }

    public function testParamertersGettingPassedToResolver()
    {
        $con = new Container;
        $con->bind('foo', function ($c, $p) {
            return $p;
        });

        $params = array('foo', 'bar', 'baz');

        $this->assertEquals($params, $con->resolve('foo', $params));
    }

    public function testInstance()
    {
        $con = new Container;

        $obj = new StdClass;
        $obj->name = 'Foo';

        $con->instance('foo', $obj);

        $this->assertEquals($obj, $con->resolve('foo'));

    }

    public function testResolverCallbacks()
    {
        $con = new Container;

        $con->onResolving(function($object) {
            $object->bar = 'baz';
        });

        $con->bind('foo', function()
        {
            return new stdClass;
        });


        $this->assertEquals('baz', $con->resolve('foo')->bar);
    }

    public function testDependenyInjectionConstructor()
    {
        $con = new Container;
        $con->bind('ConcreteStubInterface', 'ConcreteStub');

        $this->isInstanceOf('ConcreteDependsOn', $con->resolve('ConcreteDependsOn'));

    }

    /**
     * This test is skipped if we' re not an 5.4 or higher
     */
    public function testDependenyInjectionMethodDetection()
    {
        if (version_compare(PHP_VERSION, '5.4.0') <= 0) {
            return true;
        }

        $con = new Container;
        $con->enableInjecterDetection(array(
            'setString',
            '_CLASSES_' => array(
                'ConcreteInjectorMethods' => array(
                    'setArray'
                )
            )
        ));
        $obj = $con->resolve('ConcreteInjectorMethods');

        $this->assertTrue($obj->debug);
    }

    /**
     * This test is skipped if we' re not an 5.4 or higher
     * @requires PHP 5.4
     */
    public function testDisableDependenyInjectionMethodDetection()
    {
        if (version_compare(PHP_VERSION, '5.4.0') <= 0) {
            return true;
        }

        $con = new Container;
        $con->enableInjecterDetection(array(
            'setString',
            '_CLASSES_' => array(
                'ConcreteInjectorMethods' => array(
                    'setArray'
                )
            )
        ));
        $con->disableInjecterDetection();
        $obj = $con->resolve('ConcreteInjectorMethods');
        $this->assertNull($obj->debug);
    }

    public function testDeepResolving()
    {
        $con = new Container;
        $con->bind('foo', 'bar');
        $con->bind('bar', function() {
            return 'baz';
        });

        $this->assertEquals('baz', $con->resolve('foo'));
    }

    /**
     * @expectedException \DaGardner\DaContainer\Exceptions\ResolveException
     */
    public function testRemove()
    {
        $con = new Container;
        $con->bind('foo', function() {
            return 'foo';
        });

        $con->remove('foo');

        $con->resolve('foo');
    }

    public function testIsBound()
    {
        $con = new Container;
        $con->bind('foo', function() {
            return 'foo';
        });

        $this->assertTrue($con->isBound('foo'));
    }

    public function testArrayAccess()
    {
        $con = new Container;

        $con['foo'] = function() {
            return 'bar';
        };

        $this->assertTrue(isset($con['foo']));

        $this->assertEquals('bar', $con['foo']);

        unset($con['foo']);

        $this->assertFalse($con->isBound('foo'));
    }

    /**
     * @expectedException \DaGardner\DaContainer\Exceptions\ResolveException
     */
    public function testExceptionOnBuild()
    {
        $con = new Container;
        $con->build('foo');
    }

    /**
     * @expectedException \DaGardner\DaContainer\Exceptions\ResolveException
     */
    public function testExceptionOnBuildCustom()
    {
        $con = new Container;
        $con->build('PrivateConcreteStub');
    }

    /**
     * @expectedException \DaGardner\DaContainer\Exceptions\ResolveException
     */
    public function testDependsOnMissingClass()
    {
        $con = new Container;
        $con->resolve('DependsOnMissingClass');
    }

    public function testResolveParameter()
    {
        $con = new Container;

        $obj = $con->resolve('DependsOnStringDefault');
    }

    /**
     * @expectedException \DaGardner\DaContainer\Exceptions\ParameterResolveException
     */
    public function testResolveException()
    {
        $con = new Container;

        $obj = $con->resolve('DependsOnString');
    }

    public function testEventPriority()
    {
        $con = new Container;
        $con->onResolving(function($object) {
            $object->name = 'foo';
        }, -10);

        $con->onResolving(function($object) {
            $object->name = 'bar';
        }, 10);

        $con->bind('foo', function() {
            return new stdClass;
        });

        $this->assertEquals('bar', $con->resolve('foo')->name);
    }

    /**
     * DIMD => dependeny injection method detection
     * @expectedException RunTimeException
     */
    public function testPHPThreeThrowsExceptionWithDIMD()
    {       
        $con = new Container;
        $con->enableInjecterDetection(array(), '5.3.0');
    }

    /**
     * This test is skipped if we' re not an 5.4 or higher
     * @requires PHP 5.4
     */
    public function testBuildDimdBlacklist()
    {
        if (version_compare(PHP_VERSION, '5.4.0') <= 0) {
            return true;
        }
        
        $con = new Container;

        $con->enableInjecterDetection();
        $con->disableInjecterDetection();

        $this->assertEquals(array(), $con->getDimdBlacklist());


        $con->enableInjecterDetection(array('foo'));
        $con->disableInjecterDetection();

        $this->assertEquals(array('foo'), $con->getDimdBlacklist());

        $con->enableInjecterDetection();

        $this->assertEquals(array('foo'), $con->getDimdBlacklist());
    }
}


class ConcreteStub implements ConcreteStubInterface {}

interface ConcreteStubInterface {}

class ConcreteDependsOn
{
    function __construct(ConcreteStubInterface $dep, $str = 'Foo') {}
}

class PrivateConcreteStub
{
    private function __construct() {}
}

class ConcreteInjectorMethods
{
    public $debug;
    function setStdClass(stdClass $class) {
        $this->debug = true;
    }

    public function setString($str = '')
    {
        $this->debug = false;
    }

    public function setArray(array $test)
    {
        $this->debug = false;
    }
}

class DependsOnMissingClass
{
    public function __construct(Foo $foo) {}
}

class DependsOnMissingClassDefault
{
    public function __construct(Foo $foo = null) {}
}

class DependsOnStringDefault
{
    public function __construct($str = "String") {}
}

class DependsOnString
{
    public function __construct($str) {}
}

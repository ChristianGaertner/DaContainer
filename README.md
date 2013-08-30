# DaContainer
[![Build Status](https://travis-ci.org/ChristianGaertner/DaContainer.png?branch=master)](https://travis-ci.org/ChristianGaertner/DaContainer)
[![Coverage Status](https://coveralls.io/repos/ChristianGaertner/DaContainer/badge.png?branch=master)](https://coveralls.io/r/ChristianGaertner/DaContainer?branch=master)
[![Latest Stable Version](https://poser.pugx.org/dagardner/dacontainer/v/stable.png)](https://packagist.org/packages/dagardner/dacontainer)
[![Dependency Status](https://www.versioneye.com/user/projects/521b6093632bac7a59008409/badge.png)](https://www.versioneye.com/user/projects/521b6093632bac7a59008409)

A simpe IoC container for PHP.

DaContainer is a powerful inversion of control container for managing class dependencies.

## Installation

Simply add ```dagardner/dacontainer``` to your composer dependencies and run ```composer install```.

    "require": {
        "dagardner/dacontainer": "1.*"
    }

The last step is to include composer' s autoload file (like this: ```require './vendor/autoload.php'```.

And thats it. In the next chapter we we' ll cover basic usage.

## Basic Usage

**DaContainer** can resolve both via Closure and direct resolution. Let' s start with Closures:

### Binding...

    $container = new \DaGardner\DaContainer\Container;
    $container->bind('foo', function() {
      return new stdClass;
    });

### Resolving

    $container->resolve('foo');

This will call the closure callback and therefor returns a new instance of a ```stdClass```.

### Singletons

Even if this pattern is often consired to be an anti-pattern, I implemented it, just in case somebody craves for it...

    $container->singelton('foo', function() {
      return new stdClass;
    });

This closure is only called *once* during execution. The returned instance is stored and a second resolve call will return the stored instance:

    ($container->resolve('foo') === $container->resolve('foo));

This statement will be true.

You can also store existing objects into the container:

    $object = new stdClass;
    $container->instance('foo', $object);


### Automatic Injection

The IoC DaContainer can also auto-inject dependencies of a class into it' s constrcutor and injector methods.

    class Foo
    {
      public function __construct(stdClass $class) {}
      public function setLogger(LoggerInterface $logger) {}
    }

With some other containers this wouldn' t be possible. The DaContainer will analyze the class with Reflections and auto-inject classes.

    $container->resolve('Foo');

This will just return a new instance of ```Foo``` with a ```stdClass``` injected into the constructor.
But how do we enable the injector method detection?

    $container->enableInjecterDetection();
    $container->resolve('Foo');

**NOTE** This feature (```enableInjecterDetection()```) requires PHP 5.4 or higher. *In PHP 5.3 closure to object binding is not supported, which is crucial for this feature to work.*

But this won' t work right now and throws a ```\DaGardner\DaContainer\Exceptions\ResolveException``` exception. Why?

The container doesn' t know what to do with this ```LoggerInterface```. Since the container doesn' t know which implementation to use you have to tell it the container!

    $container->bind('LoggerInterface', 'BasicLogger');

As you can see you can also bind an id to just a class name.

If a class requires an implementation of the ```LoggerInterface``` the container knows which implementation to use!


The ```Container::enableInjecterDetection()``` takes as optional argument a blacklist (and the ```PHP_VERSION``` string, but you can ignore this!)

The exact display of the method is:

    \DaGardner\DaContainer\Container::enableInjecterDetection([array $blacklist = array() [, string $version = PHP_VERSION]])

The blacklist should (has to be) constructed like this:

    [
      'setString',
      'setArray',
      '_CLASSES_' => [
        'SomeClass' => [
          'setLogger'
        ]
      ]
    
    ]

Strings in the main array are consired to be global and are ignored everytime.

The class specific blacklist is only checked if the object is an instance of this class. A blacklist for a class should go into the ```_CLASSES_``` key.

### Events

The IoC Container has it' s own simple event system, which can be used standalone or getting hooked into the main event dispatcher!

    $container->onResolving(function($object) {
      // Do something
    });

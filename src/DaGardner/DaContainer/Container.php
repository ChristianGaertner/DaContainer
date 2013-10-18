<?php namespace DaGardner\DaContainer;
/*
 *  (c) Christian Gärtner <christiangaertner.film@googlemail.com>
 * This file is part of the DaContainer package
 * License: View distributed LICENSE file
 */

use Closure;
use ArrayAccess;
use ReflectionClass;
use RunTimeException;
use ReflectionException;
use DaGardner\DaContainer\Exceptions\ResolveException;
use DaGardner\DaContainer\Exceptions\ParameterResolveException;

/**
* DaContainer main class.
* A simple IoC Container
* @author Christian Gärtner <christiangaertner.film@googlemail.com>
*/
class Container implements ArrayAccess, ResolverInterface
{
    /**
     * The bindings
     * @var array
     */
    protected $binds = array();

    /**
     * The singletons
     * @var array
     */
    protected $singletons = array();

    /**
     * Registered resolver callbacks
     * @var array
     */
    protected $callbacks = array();

    /**
     * The blacklist for the dependeny injection method detection
     * @var array
     */
    protected $dimbBlacklist = array();

    /**
     * Register a binding
     * @param  string               $id        The id (needed for resolving)
     * @param  Closure|string|null  $concrete  The factory
     * @param  boolean              $singleton Whether the binding should be a singelton
     */
    public function bind($id, $concrete, $singleton = false)
    {

        $concrete = $concrete ?: $id;

        if (!$concrete instanceof Closure) {
            
            // If the factory (resolver) is NOT a closure we assume,
            // that it is a classname and wrap it into a closure so it' s
            // easier when resolving.

            $concrete = function ($container) use ($id, $concrete) {

                $method = ($id == $concrete) ? 'build' : 'resolve';

                return $container->$method($concrete, array());
            };
        }


        $this->binds[$id] = compact('concrete', 'singleton');
    }

    /**
     * Register a singleton binding
     * @param  string               $id        The id (needed for resolving)
     * @param  Closure|string|null  $concrete  The factory
     *
     * @uses bind()
     */
    public function singleton($id, $concrete = null)
    {
        $this->bind($id, $concrete, true);
    }

    /**
     * Put an object (instance) into the singelton registry
     * @param  string $id       The id (needed for resolving)
     * @param  mixed  $instance The object (an instance)
     */
    public function instance($id, $instance)
    {
        $this->singletons[$id] = $instance;
    }

    /**
     * Removes a binding 
     * @param  string $id The id (used while binding)
     */
    public function remove($id)
    {
        unset($this->binds[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($id, array $parameters = array())
    {
        if (isset($this->singletons[$id])) {

            return $this->singletons[$id];

        }

        $concrete = $this->getConcrete($id);

        $object = $this->build($concrete, $parameters);


        if ($this->isSingelton($id)) {
            
            $this->singletons[$id] = $object;

        }

        $this->fireCallbacks($object);

        return $object;
    }

    /**
     * Instantiate a concrete
     * @param  string|Closure       $concrete   The concrete
     * @param  array                $parameters Parameters are getting passed to the factory
     * @return mixed                            The new instance
     *
     * @throws \DaGardner\DaContainer\Exceptions\ResolveException
     */
    public function build($concrete, array $parameters = array())
    {
        if ($concrete instanceof Closure) {

            return $concrete($this, $parameters);

        }

        try {

            $resolver = new ReflectionClass($concrete);

        } catch (ReflectionException $e) {

            throw new ResolveException('Target <' . $concrete . '> could not be found.', $e->getCode(), $e);
            
        }
        

        if (!$resolver->isInstantiable()) {

            throw new ResolveException('Target <' . $concrete . '> is not instantiable.');
            
        }

        $constructor = $resolver->getConstructor();

        // If there is no constructor we can just return a new one
        // (otherwise are parameters required)
        if (is_null($constructor)) {
            
            return new $concrete;

        }

        $dependencies = $this->getDependencies($constructor->getParameters());
        return $resolver->newInstanceArgs($dependencies);
    }

    /**
     * Determine if an ID is already bound
     * @param  string  $id The ID
     * @return boolean     Whether the ID is bound
     */
    public function isBound($id)
    {
        return isset($this->binds[$id]);
    }

    /**
     * Enable the powerful injector method detection.
     *
     * Example blacklist array:
     * [
     *     'setString',
     *     'setArray',
     *     '_CLASSES_' => [
     *         SomeClass' => [
     *         'setMailer'
     *         ]
     *     ]
     * 
     * ]
     *
     * Strings in the main array are consired to be global and are ignored everytime.
     * The class specific blacklist is only checked if the object is an instance of this class
     *
     * If the blacklist is empty it will try to recover the previous used.
     * 
     * <strong>This feature requires PHP 5.4 or higher</strong>
     * 
     * @param  array  $blacklist A blacklist of method names
     * @param  string $version   The current PHP_VERSION, default is the constant. This argument can be ignored! (used for unit-tests only)
     *
     * @throws \DaGardner\DaContainer\Exceptions\ResolveException
     * @throws \RunTimeException (if PHP version is below 5.4.0)
     */
    public function enableInjecterDetection(array $blacklist = array(), $version = PHP_VERSION)
    {
        if (version_compare($version, '5.4.0') <= 0) {

            throw new RunTimeException('This feature requires PHP 5.4 or higher');

        }

        $blacklist = $this->buildDimdBlacklist($blacklist);
        $this->dimbBlacklist = $blacklist;

        $this->onResolving(function ($object) use ($blacklist) {
            $class = get_class($object);

            $reflection = new ReflectionClass($class);

            $methods = $reflection->getMethods();

            /**
             * Cycle thru all methods. Filtering in the next control-construct
             */
            foreach ($methods as $method) {

                /**
                 * This is not a complex detection, but most injecter methods are starting with a set[...]
                 */
                if (strpos($method->name, 'set') === 0) {
                    
                    // Just check if the method is in the blacklist
                    if (in_array($method->name, $blacklist) || (isset($blacklist['_CLASSES_'][$class]) && in_array($method->name, $blacklist['_CLASSES_'][$class]))) {

                        continue;

                    }

                    try {
                        
                        $dependencies = $this->getDependencies($method->getParameters());

                        /**
                         * We keep this line in the try/catch block as well in order to skip it if an exception is thrown,
                         * otherwise we would get native PHP errors.. Nasty.
                         */
                        call_user_func_array(array($object, $method->name), $dependencies);

                    /**
                     * If an ParameterResolveException is thrown it hit a non class injector method and we simply ignore these
                     * We do NOT catch ResolveExceptions since a not found class is something we' re not responsible for.
                     */
                    } catch (ParameterResolveException $e) {
                    }
                }
            }
            
            return $object;

        }, -255);
    }

    public function disableInjecterDetection()
    {
        unset($this->callbacks[-255]);
    }

    /**
     * Register a listener for the resolving event.
     * This is only fired on the main resolve, not internal dependency resolves.
     *
     * NOTE: The high priorities are getting fired LAST, while negative (lower)
     * are getting fired FIRST.
     *
     * <strong>Reserved priorities</strong>
     * - -255 Injector Method Detection (this should fire as the first callback.)
     * 
     * @param  Closure $callback The listener
     * @param  int     $priority The priortiy of the listener
     */
    public function onResolving(Closure $callback, $priority = 0)
    {
        if (!isset($this->callbacks[$priority])) {

            $this->callbacks[$priority] = array();

        }
        $this->callbacks[$priority][] = $callback;
    }

    /**
     * Returns the currently used blacklist
     * for the dependeny injection method detection
     * @return array The blacklist
     */
    public function getDimdBlacklist()
    {
        return $this->dimbBlacklist ?: array();
    }

    /**
     * Returns the concrete of the given id
     * @param  string $id The id
     * @return mixed      The concrete
     *
     * @throws \DaGardner\DaContainer\Exceptions\ResolveException
     */
    protected function getConcrete($id)
    {
        if (!isset($this->binds[$id])) {

            return $id;          

        } else {

            return $this->binds[$id]['concrete'];

        }
    }

    /**
     * Checks whether the binding is a singelton
     * @param  string  $id The id
     * @return boolean     Whether the binding is a singelton
     */
    protected function isSingelton($id)
    {
        return (isset($this->binds[$id]['singleton']) && $this->binds[$id]['singleton'] === true);
    }

    /**
     * Resolve all dependencies of the reflection parameters
     * @param  array $parameters    The parameters
     * @return array                The resolved dependencies
     *
     * @throws \DaGardner\DaContainer\Exceptions\ResolveException
     */
    protected function getDependencies($parameters)
    {
        $dependencies = array();

        foreach ($parameters as $parameter) {

            try {

                $dependency = $parameter->getClass();

            } catch (ReflectionException $e) {

                throw new ResolveException('Target <' . $parameter . '> could not be found.', $e->getCode(), $e);

            }

            if (is_null($dependency)) {
                // It 's a string or the like
                $dependencies[] = $this->resolveArgument($parameter);

            } else {

                $dependencies[] = $this->resolveClass($parameter);

            }
        }

        return (array) $dependencies;
    }

    /**
     * Resolve a class.
     * @param  \ReflectionParameter $parameter The parameter
     * @return mixed                           The resolved class
     *
     * @throws \DaGardner\DaContainer\Exceptions\ResolveException
     */
    protected function resolveClass($parameter)
    {
        return $this->resolve($parameter->getClass()->name, array());
    }

    /**
     * Resolve a non-class argument
     * @param  \ReflectionParamter $parameter The parameter
     * @return mixed                          The resolved type
     *
     * @throws \DaGardner\DaContainer\Exceptions\ParameterResolveException
     */
    protected function resolveArgument($parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            
            return $parameter->getDefaultValue();

        } else {
            // We cannot guess the value, can we!
            throw new ParameterResolveException('Unresolvable parameter <' . $parameter . '>');
            
        }
    }

    protected function fireCallbacks($object)
    {
        ksort($this->callbacks);

        foreach ($this->callbacks as $priorities) {
            
            foreach ($priorities as $callback) {
                
                $callback($object);

            }

        }
    }

    /**
     * Just a wrapper for some basic control structured
     * @param  array  $blacklist The blacklist
     * @return array             The newly builded blacklist
     */
    protected function buildDimdBlacklist(array $blacklist)
    {
        if (!empty($blacklist)) {

            return $blacklist;

        } elseif (!empty($this->dimbBlacklist)) {

            return $this->dimbBlacklist;

        } else {

            return array();

        }
    }

    /**
     * ArrayAccess Implementation
     */
    
    /**
     * ArrayAccess
     * @param  string $id  The id used on bind()
     * @return boolean     Whether the id is bound
     *
     * @uses isBound()
     */
    public function offsetExists($id)
    {
        return $this->isBound($id);
    }

    /**
     * Resolve a binding
     * @param  string $id The id (used while binding)
     * @return mixed      The return value of the closure
     *
     * @uses resolve()
     */
    public function offsetGet($id)
    {
        return $this->resolve($id);
    }

    /**
     * Register a binding
     * @param  string               $id        The id (needed for resolving)
     * @param  Closure|string|null  $value     The factory
     *
     * @uses bind()
     */
    public function offsetSet($id, $value)
    {
        $this->bind($id, $value);
    }

    /**
     * Removes a binding
     * @param  string $if The id (used while binding)
     *
     * @uses remove()
     */
    public function offsetUnset($id)
    {
        $this->remove($id);
    }
}
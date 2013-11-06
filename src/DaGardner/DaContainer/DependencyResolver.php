<?php namespace DaGardner\DaContainer;
/*
 *  (c) Christian Gärtner <christiangaertner.film@googlemail.com>
 * This file is part of the DaContainer package
 * License: View distributed LICENSE file
 */

use ReflectionException;
use DaGardner\DaContainer\Exceptions\ResolveException;
use DaGardner\DaContainer\Exceptions\ParameterResolveException;

/**
* This class handels the gathering of dependencies
* (This was done by the Container class until 1.3)
* @author Christian Gärtner <christiangaertner.film@googlemail.com>
*/
class DependencyResolver
{

	/**
	 * The main container instance
	 * @var \DaGardner\DaContainer\Container
	 */
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
     * Resolve all dependencies of the reflection parameters
     * @param  array $parameters    The parameters
     * @return array                The resolved dependencies
     *
     * @throws \DaGardner\DaContainer\Exceptions\ResolveException
     */
    public function getDependencies(array $parameters)
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

    public function resolveArgument(\ReflectionParameter $parameter)
    {
    	if ($parameter->isDefaultValueAvailable()) {
            
            return $parameter->getDefaultValue();

        } else {
            // We cannot guess the value, can we!
            throw new ParameterResolveException('Unresolvable parameter <' . $parameter . '>');
            
        }
    }

    /**
     * Resolve a class.
     * @param  \ReflectionParameter $parameter The parameter
     * @return mixed                           The resolved class
     *
     * @throws \DaGardner\DaContainer\Exceptions\ResolveException
     */
    public function resolveClass(\ReflectionParameter $parameter)
    {
        return $this->container->resolve($parameter->getClass()->name, array());
    }

}
<?php namespace DaGardner\DaContainer\InjectorDetection;
/*
 *  (c) Christian Gärtner <christiangaertner.film@googlemail.com>
 * This file is part of the DaContainer package
 * License: View distributed LICENSE file
 */

use ReflectionMethod;

/**
* An interfaces for classes which can detect dependency injector methods.
* @author Christian Gärtner <christiangaertner.film@googlemail.com>
*/
interface DetectorInterface
{
	/**
	 * Detect if the given method is injector method
	 * @param  \ReflectionMethod $name The method to check
	 * @return bool                   Whether the method is injector method.
	 */
    public function detect(ReflectionMethod $name);
}
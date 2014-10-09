<?php namespace DaGardner\DaContainer;
/*
 *  (c) Christian Gärtner <christiangaertner.film@googlemail.com>
 * This file is part of the DaContainer package
 * License: View distributed LICENSE file
 */

/**
* An interface for any class which can resolve objects
* Used by the main DaContainer class
* @author Christian Gärtner <christiangaertner.film@googlemail.com>
*/
interface ResolverInterface
{
	/**
     * Resolve a binding
     * @param  string $id         The id (used while binding)
     * @param  array  $parameters Parameters are getting passed to the factory
     * @return mixed              The return value of the closure
     */
    public function resolve($id, array $parameters = array());
}

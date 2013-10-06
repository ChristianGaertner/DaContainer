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
    public function resolve($id, array $parameters = array());
}
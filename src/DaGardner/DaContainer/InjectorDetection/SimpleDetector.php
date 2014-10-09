<?php namespace DaGardner\DaContainer\InjectorDetection;
/*
 *  (c) Christian Gärtner <christiangaertner.film@googlemail.com>
 * This file is part of the DaContainer package
 * License: View distributed LICENSE file
 */

use ReflectionMethod;

/**
* This class handels a simple the injector-method detection
* (If the name starts with "set")
* @author Christian Gärtner <christiangaertner.film@googlemail.com>
*/
class SimpleDetector implements DetectorInterface
{

    /**
     * {@inheritdoc}
     */
    public function detect(ReflectionMethod $method)
    {
        return (strpos($method->name, 'set') === 0);
    }

}

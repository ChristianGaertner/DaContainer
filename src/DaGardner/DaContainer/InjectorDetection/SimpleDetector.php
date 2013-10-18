<?php namespace DaGardner\DaContainer\InjectorDetection;
/*
 *  (c) Christian Gärtner <christiangaertner.film@googlemail.com>
 * This file is part of the DaContainer package
 * License: View distributed LICENSE file
 */

use InvalidArgumentException;
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
    public function detect(ReflectionMethod $name)
    {
        return (strpos($method->name, 'set') === 0);
    }

}
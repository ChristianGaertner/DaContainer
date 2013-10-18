<?php
/*
 * (c) Christian Gärtner <christiangaertner.film@googlemail.com>
 * This file is part of the Modulework Framework Tests
 * License: View distributed LICENSE file
 *
 * 
 * This file is meant to be used in PHPUnit Tests
 */

use DaGardner\DaContainer\InjectorDetection\SimpleDetector;

/**
* PHPUnit Test
*/
class SimpleDetectorTest extends PHPUnit_Framework_TestCase
{

    public function testGeneral()
    {
        $interfaces = class_implements(new SimpleDetector);

        $this->assertTrue(in_array('DaGardner\DaContainer\InjectorDetection\DetectorInterface', $interfaces));
    }

    /**
     * @dataProvider detectionData
     * @covers SimpleDetector::detect()
     */
    public function testDetection($result, $name)
    {
        $this->assertEquals(
                $result,
                (new SimpleDetector())
                
                ->detect(
                    $name
                )
            );
    }

    public function detectionData()
    {
        $ref = new ReflectionClass('Mock');
        $methods = $ref->getMethods();

        $data = array();

        foreach ($methods as $method) {

            $result = false;

            switch ($method->name) {
                case 'setMailer':
                    $result = true;
                    break;
                case 'setDatabase':
                    $result = true;
                    break;
                case 'mailerSet':
                    $result = false;
                    break;
                case 'dbset':
                    $result = false;
                    break;
            }

            $data[] = array($result, $method);
        }
        
        return $data;
    }
}

/**
* Mocking FTW
*/
class Mock
{
    public function setMailer() {
        return true;
    }
    public function setDatabase() {
        return true;
    }
    public function mailerSet() {
        return true;
    }
    public function dbset() {
        return true;
    }
    
}
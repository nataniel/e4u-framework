<?php
namespace E4u\Model;

use E4u\Exception\LogicException;
use Zend\Config\Config;

class Fixture
{
    public static function generateID($name, $class)
    {
        return abs(crc32($class.':'.$name));
    }
    
    public static function find($name, $class)
    {
        $id = self::generateID($name, $class);
        return $class::find($id);
    }
    
    /**
     * @todo Sanitize filename before including
     * @param  string $filename
     * @return int
     */
    public static function load($filename)
    {
        $fixtures = include $filename;
        $fixtures = new Config($fixtures, true);
        
        $count = 0;
        foreach ($fixtures as $key => $fixture) {
            $class = strtok($key, ':');
            $name = strtok('');
            
            if (!class_exists($class)) {
                throw new LogicException(
                    sprintf('Class %s defined in %s does not exist.',
                    $class, $filename));
            }
            
            if (empty($fixture->id)) {
                $fixture->id = self::generateID($name, $class);
            }
            
            if (!$class::find($fixture->id)) {
                $entity = new $class($fixture->toArray());
                $entity->save();
                $count++;
            }
        }
        
        return $count;
    }
}
<?php
namespace E4u\Model;

use E4u\Exception\LogicException;
use Laminas\Config\Config;

class Fixture
{
    public static function generateID(string $name, string $class): int
    {
        return abs(crc32($class.':'.$name));
    }
    
    public static function find(string $name, string $class)
    {
        $id = self::generateID($name, $class);
        return $class::find($id);
    }
    
    /**
     * @todo Sanitize filename before including
     */
    public static function load(string $filename): int
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
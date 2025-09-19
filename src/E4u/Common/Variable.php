<?php
namespace E4u\Common;

class Variable
{
    public static function getType(mixed $var): string
    {
        return is_object($var) ? get_class($var) : gettype($var);
    }

    /**
     * @assert ('product_name') == 'setProductName'
     */
    public static function propertySetMethod(string $property): string
    {
        $method  = StringTools::camelCase($property);
        return 'set'.$method;
    }

    /**
     * @assert ('product_name') == 'getProductName'
     */
    public static function propertyGetMethod(string $property): string
    {
        $method  = StringTools::camelCase($property);
        return 'get'.$method;
    }
}
<?php
namespace E4u\Common;

class Variable
{
    /**
     * @param  mixed $var
     * @return string
     */
    public static function getType($var)
    {
        return is_object($var) ? get_class($var) : gettype($var);
    }

    /**
     * @assert ('product_name') == 'setProductName'
     * @param  string $property
     * @return string
     */
    public static function propertySetMethod($property)
    {
        $method  = StringTools::camelCase($property);
        $method = 'set'.$method;
        return $method;
    }

    /**
     * @assert ('product_name') == 'getProductName'
     * @param  string $property
     * @return string
     */
    public static function propertyGetMethod($property)
    {
        $method  = StringTools::camelCase($property);
        $method = 'get'.$method;
        return $method;
    }
}
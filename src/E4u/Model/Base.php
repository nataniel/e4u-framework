<?php
namespace E4u\Model;

use E4u\Common\StringTools;
use E4u\Exception\LogicException;

class Base implements \ArrayAccess
{
    public function __construct($attributes = [])
    {
        $this->loadArray($attributes);
    }

    /**
     * @param  array $attributes
     * @param  array $propertyList
     * @return static
     */
    public function loadArray($attributes, $propertyList = null)
    {
        foreach ($attributes as $field => $value) {
            if (is_null($propertyList) || in_array($field, $propertyList)) {
                $method = self::propertySetMethod($field);
                $this->$method($value);
            }
        }

        return $this;
    }

    /**
     * Defined by ArrayAccess.
     *
     * @param  string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $method = self::propertyGetMethod($offset);
        return method_exists($this, $method);
    }

    /**
     * Defined by ArrayAccess.
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $method = self::propertyGetMethod($offset);
        return $this->$method();
    }

    /**
     * Defined by ArrayAccess.
     *
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $method = self::propertySetMethod($offset);
        $this->$method($value);
    }

    /**
     * Defined by ArrayAccess.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $method = self::propertySetMethod($offset);
        $this->$method(null);
    }

    /**
     * Automagical getter/setter for all model properties, so you don't have to
     * make all those getFoo(), setFoo() methods all over the model.
     *
     * @param  string $name
     * @param  array  $argv
     * @return mixed
     */
    public function __call($name, $argv)
    {
        if (preg_match('/^(set|get|addTo|delFrom|has|unset)([A-Z].*)$/', $name, $matches)) {
            $method = '_'.$matches[1];
            $property = StringTools::underscore($matches[2]);
            
            /* PHP 5.6+ */
            # return $this->$method($property, ...$argv);
            
            array_unshift($argv, $property);
            return call_user_func_array([$this, $method], $argv);
        }

        throw new LogicException(
                sprintf('Call to undefined method %s::%s()',
                get_class($this), $name));
    }
    
    /**
     * Automagical property getter for fields and associations.
     *
     * @see __call()
     * @param  string $property
     * @return mixed
     */
    protected function _get($property)
    {
        if (!property_exists($this, $property)) {
            throw new LogicException(
                sprintf('Undefined or unreachable property: %s::$%s.',
                get_class($this), $property));
        }
        
        return $this->$property;
    }
    
    /**
     * Automagical property setter for properties
     *
     * @see _call()
     * @param  string $property Property name
     * @param  mixed  $value
     * @return static
     */
    protected function _set($property, $value)
    {
        if (!property_exists($this, $property)) {
            throw new LogicException(
                sprintf('Undefined property %s::$%s.',
                get_class($this), $property));
        }
        
        $this->$property = $value;
        return $this;
    }

    /**
     * @see _call()
     * @param  string $property Property name
     * @return static
     */
    protected function _unset($property)
    {
        if (!property_exists($this, $property)) {
            throw new LogicException(
                sprintf('Undefined property %s::$%s.',
                    get_class($this), $property));
        }

        $this->$property = null;
        return $this;
    }
    
    /**
     * @param  string $property
     * @return boolean
     */
    protected function _has($property)
    {
        if (!property_exists($this, $property)) {
            throw new LogicException(
                sprintf('Undefined property %s::$%s.',
                get_class($this), $property));
        }
        
        return !is_null($this->$property);
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

    /**
     * @assert ('products_images') == 'delFromProductsImages'
     * @param  string $property
     * @return string
     */
    public static function propertyDelFromMethod($property)
    {
        $method  = StringTools::camelCase($property);
        $method = 'delFrom'.$method;
        return $method;
    }

    /**
     * @assert ('products_images') == 'addToProductsImages'
     * @param  string $property
     * @return string
     */
    public static function propertyAddToMethod($property)
    {
        $method  = StringTools::camelCase($property);
        $method = 'addTo'.$method;
        return $method;
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
     * @assert ('product_name') == 'unsetProductName'
     * @param  string $property
     * @return string
     */
    public static function propertyUnsetMethod($property)
    {
        $method  = StringTools::camelCase($property);
        $method = 'unset'.$method;
        return $method;
    }
}
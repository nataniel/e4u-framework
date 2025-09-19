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

    public function loadArray(array $attributes, ?array $propertyList = null): static
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
     */
    public function offsetExists($offset): bool
    {
        $method = self::propertyGetMethod($offset);
        return method_exists($this, $method);
    }

    /**
     * Defined by ArrayAccess.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $method = self::propertyGetMethod($offset);
        return method_exists($this, $method)
            ? $this->$method()
            : $this->_get($offset);
    }

    /**
     * Defined by ArrayAccess.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $method = self::propertySetMethod($offset);
        method_exists($this, $method)
            ? $this->$method($value)
            : $this->_set($offset, $value);
    }

    /**
     * Defined by ArrayAccess.
     */
    public function offsetUnset(mixed $offset): void
    {
        $method = self::propertySetMethod($offset);
        method_exists($this, $method)
            ? $this->$method(null)
            : $this->_set($offset, null);
    }

    /**
     * Automagical getter/setter for all model properties, so you don't have to
     * make all those getFoo(), setFoo() methods all over the model.
     */
    public function __call(string $name, array $argv)
    {
        if (preg_match('/^(set|get|addTo|delFrom|has|unset)([A-Z].*)$/', $name, $matches)) {
            $method = '_'.$matches[1];
            $property = StringTools::underscore($matches[2]);
            
            /* PHP 5.6+ */
            # return $this->$method($property, ...$argv);
            
            array_unshift($argv, $property);
            return call_user_func_array([ $this, $method ], $argv);
        }

        throw new LogicException(
                sprintf('Call to undefined method %s::%s()',
                get_class($this), $name));
    }
    
    /**
     * Automagical property getter for fields and associations.
     *
     * @see __call()
     */
    protected function _get(string $property): mixed
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
     */
    protected function _set(string $property, mixed $value): static
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
     */
    protected function _unset(string $property): void
    {
        if (!property_exists($this, $property)) {
            throw new LogicException(
                sprintf('Undefined property %s::$%s.',
                    get_class($this), $property));
        }

        unset($this->$property);
    }
    
    protected function _has(string $property): bool
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
     */
    public static function propertyGetMethod(string $property): string
    {
        $method  = StringTools::camelCase($property);
        return 'get'.$method;
    }

    /**
     * @assert ('products_images') == 'delFromProductsImages'
     */
    public static function propertyDelFromMethod(string $property): string
    {
        $method  = StringTools::camelCase($property);
        return 'delFrom'.$method;
    }

    /**
     * @assert ('products_images') == 'addToProductsImages'
     */
    public static function propertyAddToMethod(string $property): string
    {
        $method  = StringTools::camelCase($property);
        return 'addTo'.$method;
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
     * @assert ('product_name') == 'unsetProductName'
     */
    public static function propertyUnsetMethod(string $property): string
    {
        $method  = StringTools::camelCase($property);
        return 'unset'.$method;
    }
}
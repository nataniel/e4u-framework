<?php
namespace E4u\Common;

trait ArrayAccess
{
    public function offsetExists($offset)
    {
        $method = Variable::propertyGetMethod($offset);
        return method_exists($this, $method);
    }

    public function offsetGet($offset)
    {
        $method = Variable::propertyGetMethod($offset);
        return method_exists($this, $method)
            ? $this->$method()
            : null;
    }

    public function offsetSet($offset, $value)
    {
        $method = Variable::propertySetMethod($offset);
        return method_exists($this, $method)
            ? $this->$method($value)
            : null;
    }

    public function offsetUnset($offset)
    {
        $method = Variable::propertySetMethod($offset);
        return method_exists($this, $method)
            ? $this->$method(null)
            : null;
    }
}
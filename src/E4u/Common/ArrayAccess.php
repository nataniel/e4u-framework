<?php
namespace E4u\Common;

trait ArrayAccess
{
    public function offsetExists($offset): bool
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

    public function offsetSet($offset, $value): void
    {
        $method = Variable::propertySetMethod($offset);
        if (method_exists($this, $method)) {
            $this->$method($value);
        }
    }

    public function offsetUnset($offset): void
    {
        $method = Variable::propertySetMethod($offset);
        if (method_exists($this, $method)) {
            $this->$method(null);
        }
    }
}

<?php
namespace E4u\Common;

trait ArrayAccess
{
    public function offsetExists(string $offset): bool
    {
        $method = Variable::propertyGetMethod($offset);
        return method_exists($this, $method);
    }

    public function offsetGet(string $offset)
    {
        $method = Variable::propertyGetMethod($offset);
        return method_exists($this, $method)
            ? $this->$method()
            : null;
    }

    public function offsetSet(string $offset, mixed $value): void
    {
        $method = Variable::propertySetMethod($offset);
        if (method_exists($this, $method)) {
            $this->$method($value);
        }
    }

    public function offsetUnset(string $offset): void
    {
        $method = Variable::propertySetMethod($offset);
        if (method_exists($this, $method)) {
            $this->$method(null);
        }
    }
}

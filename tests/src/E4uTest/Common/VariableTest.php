<?php
namespace E4uTest\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use E4u\Common\Variable;

#[CoversClass(Variable::class)]
class VariableTest extends TestCase
{
    public function testGetType()
    {
        $this->assertEquals('string', Variable::getType('test string'));
        $this->assertEquals('integer', Variable::getType(1234));
        $this->assertEquals('E4uTest\Common\VariableTest', Variable::getType($this));
    }
}

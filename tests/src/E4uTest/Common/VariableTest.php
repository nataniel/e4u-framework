<?php
namespace E4uTest\Common;

use PHPUnit\Framework\TestCase;
use E4u\Common\Variable;

class VariableTest extends TestCase
{
    /**
     * @covers \E4u\Common\Variable::getType
     */
    public function testGetType()
    {
        $this->assertEquals(Variable::getType('test string'), 'string');
        $this->assertEquals(Variable::getType(1234), 'integer');
        $this->assertEquals(Variable::getType($this), 'E4uTest\Common\VariableTest');
    }
}

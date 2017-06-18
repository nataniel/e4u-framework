<?php
namespace E4uTest\Model;

use E4u\Model\Fixture;
use PHPUnit\Framework\TestCase;

class FixtureTest extends TestCase
{
    /**
     * @covers Fixture::generateID
     */
    public function testId()
    {
        $this->assertEquals('1745746795', Fixture::generateID('nataniel', 'Main\Model\User'));
    }
}

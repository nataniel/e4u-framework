<?php
namespace E4uTest\Model;

use PHPUnit\Framework\TestCase;

class FixtureTest extends TestCase
{
    /**
     * @covers \E4u\Model\Fixture::generateID
     */
    public function testId()
    {
        $this->assertEquals('1745746795', \E4u\Model\Fixture::generateID('nataniel', 'Main\Model\User'));
    }
}

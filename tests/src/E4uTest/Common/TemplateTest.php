<?php
namespace E4uTest\Common;

use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    /**
     * @covers \E4u\Common\Template::wolacz
     * @covers \E4u\Common\Template::replace
     * @covers \E4u\Common\Template::merge
     */
    public function testMerge()
    {
        $vars = [ 'name' => 'Karol Cypsalbozyps' ];
        $src = 'Witaj, [[wolacz]]! Twoje imię to: [[name]].';
        $dst = 'Witaj, Karolu! Twoje imię to: Karol Cypsalbozyps.';
        
        $this->assertEquals($dst, \E4u\Common\Template::merge($src, $vars));
    }
}

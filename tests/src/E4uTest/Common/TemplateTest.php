<?php
namespace E4uTest\Common;

use E4u\Common\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    /**
     * @covers Template::wolacz
     * @covers Template::replace
     * @covers Template::merge
     */
    public function testMerge()
    {
        $vars = [ 'name' => 'Karol Cypsalbozyps' ];
        $src = 'Witaj, [[wolacz]]! Twoje imię to: [[name]].';
        $dst = 'Witaj, Karolu! Twoje imię to: Karol Cypsalbozyps.';
        
        $this->assertEquals($dst, Template::merge($src, $vars));
    }
}

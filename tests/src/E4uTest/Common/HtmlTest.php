<?php
namespace E4uTest\Common;

use E4u\Common\Html;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    /**
     * @covers Html::attributes
     */
    public function testAttributes()
    {
        $attributes = [
            'name' => 'Artur "Dwie Szopy" Johnson',
            'company' => 'ACME & Associates',
        ];
        
        $attributes = Html::attributes($attributes);
        $this->assertEquals('name="Artur &quot;Dwie Szopy&quot; Johnson" company="ACME &amp; Associates"', $attributes);
    }

    /**
     * @covers Html::tag
     */
    public function testTag()
    {
        $this->assertEquals('<a href="/test">Jakiś link</a>', Html::tag('a', [ 'href' => '/test' ], 'Jakiś link'));
        $this->assertEquals('<strong>Cośtam</strong>', Html::tag('strong', 'Cośtam'));
        $this->assertEquals('<hr class="mark" />', Html::tag('hr', [ 'class' => 'mark' ]));
        $this->assertEquals('<br />', Html::tag('br'));
    }
}

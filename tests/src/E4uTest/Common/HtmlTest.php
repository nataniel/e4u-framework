<?php
namespace E4uTest\Common;

use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    /**
     * @covers \E4u\Common\Html::attributes
     */
    public function testAttributes()
    {
        $attributes = [
            'name' => 'Artur "Dwie Szopy" Johnson',
            'company' => 'ACME & Associates',
        ];
        
        $attributes = \E4u\Common\Html::attributes($attributes);
        $this->assertEquals('name="Artur &quot;Dwie Szopy&quot; Johnson" company="ACME &amp; Associates"', $attributes);
    }

    /**
     * @covers \E4u\Common\Html::tag
     */
    public function testTag()
    {
        $this->assertEquals('<a href="/test">Jakiś link</a>', \E4u\Common\Html::tag('a', [ 'href' => '/test' ], 'Jakiś link'));
        $this->assertEquals('<strong>Cośtam</strong>', \E4u\Common\Html::tag('strong', 'Cośtam'));
        $this->assertEquals('<hr class="mark" />', \E4u\Common\Html::tag('hr', [ 'class' => 'mark' ]));
        $this->assertEquals('<br />', \E4u\Common\Html::tag('br'));
    }
}

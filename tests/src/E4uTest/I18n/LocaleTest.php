<?php
namespace E4uTest\I18n;

use PHPUnit\Framework\TestCase;
use E4u\I18n\Locale;

class LocaleTest extends TestCase
{
    /**
     * @covers \E4u\I18n\Locale::plural
     */
    public function testPlural()
    {
        $this->assertEquals('produkt', Locale::plural(1, 'produkt', 'produkty', 'produktów'));
        $this->assertEquals('produktów', Locale::plural(11, 'produkt', 'produkty', 'produktów'));
        $this->assertEquals('produktów', Locale::plural(8, 'produkt', 'produkty', 'produktów'));
        $this->assertEquals('produkty', Locale::plural(22, 'produkt', 'produkty', 'produktów'));
    }

    /**
     * @covers \E4u\I18n\Locale::countries
     */
    public function testCountries()
    {
        $countries = Locale::countries();
        $this->assertTrue(is_array($countries));
        
        foreach ($countries as $id => $country) {
            $this->assertTrue(is_int($id));
            $this->assertTrue(is_string($country));
        }
    }
}
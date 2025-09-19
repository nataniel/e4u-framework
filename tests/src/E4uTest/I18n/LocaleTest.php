<?php
namespace E4uTest\I18n;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use E4u\I18n\Locale;

#[CoversClass(Locale::class)]
class LocaleTest extends TestCase
{
    public function testPlural()
    {
        $this->assertEquals('produktów', Locale::plural(0, 'produkt', 'produkty', 'produktów'));
        $this->assertEquals('produkt', Locale::plural(1, 'produkt', 'produkty', 'produktów'));
        $this->assertEquals('produktów', Locale::plural(11, 'produkt', 'produkty', 'produktów'));
        $this->assertEquals('produktów', Locale::plural(8, 'produkt', 'produkty', 'produktów'));
        $this->assertEquals('produkty', Locale::plural(22, 'produkt', 'produkty', 'produktów'));
    }

    public function testCountries()
    {
        $countries = Locale::countries();
        foreach ($countries as $id => $country) {
            $this->assertTrue(is_int($id));
            $this->assertTrue(is_string($country));
        }
    }
}
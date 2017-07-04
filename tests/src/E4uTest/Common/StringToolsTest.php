<?php
namespace E4uTest\Common;

use E4u\Common\StringTools;
use PHPUnit\Framework\TestCase;

class StringToolsTest extends TestCase
{
    /**
     * @covers StringTools::wolacz
     */
    public function testWolacz()
    {
        $this->assertEquals('Arturze', StringTools::wolacz('Artur'));
        $this->assertEquals('Basiu', StringTools::wolacz('Basia'));
        $this->assertEquals('Elu', StringTools::wolacz('Ela'));
        $this->assertEquals('Kornelio', StringTools::wolacz('Kornelia'));
        $this->assertEquals('Kornelu', StringTools::wolacz('Kornel'));
        $this->assertEquals('Mamo', StringTools::wolacz('Mama'));

        /*
        $this->assertEquals('Michale', StringTools::wolacz('Michał'));
        $this->assertEquals('Łukaszu', StringTools::wolacz('Łukasz'));
        $this->assertEquals('Świnio', StringTools::wolacz('Świnia'));
        $this->assertEquals('Teście', StringTools::wolacz('Test'));
         */
    }

    /**
     * @covers StringTools::toAscii
     */
    public function testToAscii()
    {
        $this->assertEquals(
          'Lodz',
          StringTools::toAscii('Łódź')
        );
    }

    /**
     * @covers StringTools::underscore
     */
    public function testUnderscore()
    {
        $this->assertEquals('test_me', StringTools::underscore('test_Me'));
        $this->assertEquals('test_me', StringTools::underscore('test_Me_'));
        $this->assertEquals('product_name', StringTools::underscore('ProductName'));
        $this->assertEquals('set_product_name', StringTools::underscore('setProductName'));
        $this->assertEquals('my_sql_is_the_best', StringTools::underscore('mySQLIsTheBest'));
        $this->assertEquals('a_camel', StringTools::underscore('aCamel'));
        $this->assertEquals('nowy_ze_spacjami', StringTools::underscore('nowy, ze spacjami'));
    }

    /**
     * @covers StringTools::camelCase
     */
    public function testCamelCase()
    {
        $this->assertEquals(
          'ProductName',
          StringTools::camelCase('product_name')
        );
    }

    /**
     * @covers StringTools::toUrl
     */
    public function testToUrl()
    {
        $this->assertEquals('łódź-bardzo-skomplikowane', StringTools::toUrl('Łódź... bardzo SKOMPLIKOWANE?!'));
        $this->assertEquals('lodz-bardzo-skomplikowane', StringTools::toUrl('Łódź... bardzo SKOMPLIKOWANE?!', true));

        $this->assertEquals('bardzo-skomplikowane', StringTools::toUrl('bardzo SKOMPLIKOWANE?!'));
        $this->assertEquals('warszawa-bardzo-skomplikowane', StringTools::toUrl('Warszawa... bardzo SKOMPLIKOWANE?!'));
        $this->assertEquals('default', StringTools::toUrl('@(!'));
    }

    /**
     * @covers StringTools::shortVersion()
     */
    public function testShortVersion()
    {
        $txt = '<p>[[ja(Ty/On)]] jesteś jak zdrowie.
                <em>Ile cię</em> <strong>trzeba</strong> było z uśmiechem, a oni <a href="/">tak było przeznaczono</a>,
                by chybiano względy dla skończenia dawnego z pastwisk razem ja rozumiem!</p>';

        $this->assertEquals('jesteś jak zdrowie. Ile cię trzeba było z uśmiechem, a oni tak było przeznaczono, by', StringTools::shortVersion($txt, 15));
    }
}
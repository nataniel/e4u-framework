<?php
namespace E4uTest\I18n\Translator;

use PHPUnit\Framework\TestCase;
use E4u\I18n\Translator\ArrayLoader;

/**
 * Class ArrayLoaderTest
 * @package E4uTest\I18n\Translator
 * @covers  ArrayLoader
 */
class ArrayLoaderTest extends TestCase
{
    /**
     * @covers ArrayLoader::load()
     */
    public function testLoad()
    {
        $loader = new ArrayLoader();
        $output = $loader->load('', 'tests/files/locale.php');
        $this->assertEquals('in stock', $output['product.availability.1']);
        $this->assertEquals('TEST', $output['some.value.test']);
        $this->assertEquals('Sign in', $output['Zaloguj się']);
        $this->assertEquals('Search...', $output['Szukaj...']);
    }
}
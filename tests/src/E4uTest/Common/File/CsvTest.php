<?php
namespace E4uTest\Common\File;

use PHPUnit\Framework\TestCase;
use E4u\Common\File\Csv;

/**
 * Class CsvTest
 * @package E4uTest\Common\File
 * @covers  Csv
 */
class CsvTest extends TestCase
{
    /**
     * @var Csv
     */
    private $file;

    /**
     * <code>
     * product_id,ean,amount
     * 16721,3558380022473,7
     * 97026,3770002176399,24
     * ,8595558307005,4
     * </code>
     */
    protected function setUp()
    {
        $this->file = new Csv('files/test.csv', 'tests/');
    }

    /**
     * @covers Csv::getData()
     */
    public function testGetData()
    {
        $array = $this->file->getData();
        $this->assertTrue(is_array($array));
        $this->assertCount(3, $array);
    }

    /**
     * @covers Csv::getHeader()
     */
    public function testGetHeader()
    {
        $array = $this->file->getHeader();
        $this->assertTrue(is_array($array));
        $this->assertCount(3, $array);
    }
}
<?php
namespace E4uTest\Common\File\Csv;

use PHPUnit\Framework\TestCase;
use E4u\Common\File\Csv\CsvWithoutHeader;

/**
 * Class CsvWithoutHeaderTest
 * @package E4uTest\Common\File\Csv
 * @covers  CsvWithoutHeader
 */
class CsvWithoutHeaderTest extends TestCase
{
    /**
     * @var CsvWithoutHeader
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
        $this->file = new CsvWithoutHeader('files/test.csv', 'tests/');
    }

    /**
     * @covers CsvWithoutHeader::getData()
     */
    public function testGetData()
    {
        $data = $this->file->getData();
        $this->assertTrue(is_array($data));
        $this->assertCount(4, $data);
    }

    /**
     * @covers CsvWithoutHeader::getHeader()
     */
    public function testGetHeader()
    {
        $header = $this->file->getHeader();
        $this->assertTrue(is_array($header));
        $this->assertEmpty($header);
    }
}
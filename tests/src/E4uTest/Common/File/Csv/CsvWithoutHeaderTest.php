<?php
namespace E4uTest\Common\File\Csv;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use E4u\Common\File\Csv\CsvWithoutHeader;

/**
 * Class CsvWithoutHeaderTest
 * @package E4uTest\Common\File\Csv
 */
#[CoversClass(CsvWithoutHeader::class)]
class CsvWithoutHeaderTest extends TestCase
{
    private CsvWithoutHeader $file;

    /**
     * <code>
     * product_id,ean,amount
     * 16721,3558380022473,7
     * 97026,3770002176399,24
     * ,8595558307005,4
     * </code>
     */
    protected function setUp(): void
    {
        $this->file = new CsvWithoutHeader('files/test.csv', 'tests/');
    }

    public function testGetData()
    {
        $data = $this->file->getData();
        $this->assertCount(4, $data);
    }

    public function testGetHeader()
    {
        $header = $this->file->getHeader();
        $this->assertEmpty($header);
    }


    public function testCountColumns()
    {
        $this->assertEquals(3, $this->file->countColumns());
    }
}
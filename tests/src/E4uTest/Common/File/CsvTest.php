<?php
namespace E4uTest\Common\File;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use E4u\Common\File\Csv;

/**
 * Class CsvTest
 * @package E4uTest\Common\File
 */
#[CoversClass(Csv::class)]
class CsvTest extends TestCase
{
    private Csv $file;

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
        $this->file = new Csv('files/test.csv', 'tests/');
    }

    public function testGetData()
    {
        $array = $this->file->getData();
        $this->assertCount(3, $array);
    }

    public function testGetHeader()
    {
        $array = $this->file->getHeader();
        $this->assertCount(3, $array);
    }

    public function testCountColumns()
    {
        $this->assertEquals(3, $this->file->countColumns());
    }
}
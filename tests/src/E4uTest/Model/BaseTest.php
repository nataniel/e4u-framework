<?php
namespace E4uTest\Model;

use E4u\Model\Base;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    /**
     * @covers Base::propertyGetMethod
     */
    public function testPropertyGetMethod()
    {
        $this->assertEquals(
          'getProductName',
          Base::propertyGetMethod('product_name')
        );
    }

    /**
     * @covers Base::propertyDelFromMethod
     */
    public function testPropertyDelFromMethod()
    {
        $this->assertEquals(
          'delFromProductsImages',
          Base::propertyDelFromMethod('products_images')
        );
    }

    /**
     * @covers Base::propertyAddToMethod
     */
    public function testPropertyAddToMethod()
    {
        $this->assertEquals(
          'addToProductsImages',
          Base::propertyAddToMethod('products_images')
        );
    }

    /**
     * @covers Base::propertySetMethod
     */
    public function testPropertySetMethod()
    {
        $this->assertEquals(
          'setProductName',
          Base::propertySetMethod('product_name')
        );
    }
}

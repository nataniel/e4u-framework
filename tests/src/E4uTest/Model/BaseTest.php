<?php
namespace E4uTest\Model;

use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    /**
     * @covers \E4u\Model\Base::propertyGetMethod
     */
    public function testPropertyGetMethod()
    {
        $this->assertEquals(
          'getProductName',
          \E4u\Model\Base::propertyGetMethod('product_name')
        );
    }

    /**
     * @covers \E4u\Model\Base::propertyDelFromMethod
     */
    public function testPropertyDelFromMethod()
    {
        $this->assertEquals(
          'delFromProductsImages',
          \E4u\Model\Base::propertyDelFromMethod('products_images')
        );
    }

    /**
     * @covers \E4u\Model\Base::propertyAddToMethod
     */
    public function testPropertyAddToMethod()
    {
        $this->assertEquals(
          'addToProductsImages',
          \E4u\Model\Base::propertyAddToMethod('products_images')
        );
    }

    /**
     * @covers \E4u\Model\Base::propertySetMethod
     */
    public function testPropertySetMethod()
    {
        $this->assertEquals(
          'setProductName',
          \E4u\Model\Base::propertySetMethod('product_name')
        );
    }
}

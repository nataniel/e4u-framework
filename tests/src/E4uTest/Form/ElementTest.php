<?php
namespace E4uTest\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use E4u\Form;

#[CoversClass(Form\Element::class)]
class ElementTest extends TestCase
{
    public function testConstructor()
    {
        $field = new Form\Element\EmailAddress('login', [
            'label' => 'Adres e-mail',
            'required' => 'Podaj adres e-mail.',
            'hint' => 'np. kasia123@jakasdomena.pl',
            'autofocus' => true,
        ]);
        
        $this->assertEquals('Adres e-mail', $field->getLabel());
        $this->assertTrue($field->isRequired());
        $this->assertEquals('np. kasia123@jakasdomena.pl', $field->getHint());
        $this->assertEquals('autofocus', $field->isAutofocus());
    }
}
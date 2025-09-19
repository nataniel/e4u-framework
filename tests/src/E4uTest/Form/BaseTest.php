<?php
namespace E4uTest\Form;

use E4u\Request\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use E4u\Form;

#[CoversClass(Form\Base::class)]
class BaseTest extends TestCase
{
    protected Form\Base $form;

    protected function setUp(): void
    {
        $this->form = new Form\Base(new Test(), 'test');
        $this->form->setMethod(Form\Base::HTTP_POST, false);

        $field = new Form\Element\TextField('name', 'Nazwa strony');
        $field->setHint('np. O firmie');
        $this->form->addField($field);

        $this->form->addField(new Form\Element\CheckBox('active', 'Aktywna?'));
        $this->form->addField(new Form\Element\EmailAddress('login', [
            'label' => 'Adres e-mail',
            'required' => false,
            'hint' => 'np. kasia123@jakasdomena.pl',
            'autofocus' => true,
        ]));
    }

    public function testGetValues()
    {
        $this->assertCount(3, $this->form->getValues());
        $this->assertCount(1, $this->form->getValues([ 'name', 'status' ]));

        $field = new Form\Element\TextField('test', 'Pole testowe');
        $this->form->addField($field);

        $this->assertCount(4, $this->form->getValues());
        $this->assertCount(1, $this->form->getValues([ 'name', 'status' ]));
    }

    public function testAction()
    {
        $this->form->setAction('test/index');
        $this->assertEquals($this->form->getRequest()->getBaseUrl() . 'test/index', $this->form->getAction());
    }

    public function testAddField()
    {
        $count = count($this->form->getValues());

        $field = new Form\Element\TextArea('description', 'Opis');
        $this->form->addField($field);

        $this->assertCount($count + 1, $this->form->getValues());
        $this->assertSame($field, $this->form->getElement('description'));

        $this->form->addField(new Form\Element\Password('password', [
            'label' => 'Hasło',
            'required' => 'Podaj hasło dostępowe.',
        ]));
        $this->assertCount($count + 2, $this->form->getValues());
    }

    public function testGetValue()
    {
        $field = new Form\Element\TextField('foo', 'Pole testowe');
        $this->form->addField($field);
        $field->setValue('bar');
        $this->assertEquals('bar', $field->getValue());

        $request = $this->form->getRequest();
        $request->getPost()->fromArray([ 'test' => [ 'submit' => true, 'foo' => 'else' ] ]);
        $this->assertEquals('else', $this->form->getValue('foo'));
    }

    /**
     * @covers Form\Base::setMethod
     * @covers Form\Base::getMethod
     */
    public function testMethod()
    {
        // default method
        $this->assertEquals(Form\Base::HTTP_POST, $this->form->getMethod());

        // override default method
        $this->form->setMethod('get');
        $this->assertEquals(Form\Base::HTTP_GET, $this->form->getMethod());
    }

    public function testErrors()
    {
        $this->assertEmpty($this->form->getErrors());

        $this->form->addError('Błąd!', 'active');
        $this->assertCount(1, $this->form->getErrors());

        $this->form->getElement('name')->setRequired();
        $this->form->validate();
        $this->assertCount(2, $this->form->getErrors());
    }

    public function testIsSubmitted()
    {
        $request = $this->form->getRequest();
        $request->getPost()->fromArray([ 'test' => [ 'submit' => true, 'name' => 'TEST' ] ]);
        $this->assertTrue($this->form->isSubmitted());
    }

    public function testIsValid()
    {
        $request = $this->form->getRequest();
        $request->getPost()->fromArray([ 'test' => [ 'submit' => true ] ]);
        $this->assertTrue($this->form->isValid());

        $field = new Form\Element\TextField('test', 'Pole testowe');
        $this->form->addField($field);

        $field->setRequired();
        $this->assertTrue($field->isRequired());
        $this->assertFalse($this->form->isValid());
    }
}
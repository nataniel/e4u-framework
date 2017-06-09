<?php
namespace E4uTest\Form;

use PHPUnit\Framework\TestCase;
use E4u\Form;

class BaseTest extends TestCase
{
    /**
     * @var Form\Base
     */
    protected $form;

    public function setUp()
    {
        $this->form = new Form\Base(new \E4u\Request\Test(), 'test');
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

    /**
     * @covers \E4u\Form\Base::getValues
     */
    public function testGetValues()
    {
        $this->assertCount(3, $this->form->getValues());
        $this->assertCount(1, $this->form->getValues([ 'name', 'status' ]));

        $field = new Form\Element\TextField('test', 'Pole testowe');
        $this->form->addField($field);

        $this->assertCount(4, $this->form->getValues());
        $this->assertCount(1, $this->form->getValues([ 'name', 'status' ]));
    }

    /**
     * @covers \E4u\Form\Base::getAction
     * @covers \E4u\Form\Base::setAction
     */
    public function testAction()
    {
        $this->form->setAction('test/index');
        $this->assertEquals($this->form->getRequest()->getBaseUrl() . 'test/index', $this->form->getAction());
    }

    /**
     * @covers \E4u\Form\Base::showFields
     */
    public function testShowFields()
    {
        $this->assertContains('test[name]', $this->form->showFields());
        $this->assertNotContains('test[name]', $this->form->showFields([ 'active' ]));
    }

    /**
     * @covers \E4u\Form\Base::showFields
     */
    public function testShowFieldset()
    {
        $this->assertContains('<fieldset', $this->form->showFieldset());
        $this->assertContains('</fieldset>', $this->form->showFieldset());

        $this->assertContains('test[name]', $this->form->showFieldset());
        $this->assertNotContains('test[name]', $this->form->showFieldset([ 'active' ]));
    }

    /**
     * @covers \E4u\Form\Base::showForm
     */
    public function testStartForm()
    {
        $this->assertContains('<form', $this->form->startForm());
        $this->assertNotContains('</form>', $this->form->startForm());

        $this->assertContains('class="test"', $this->form->startForm([ 'class' => 'test' ]));
        $this->assertContains('name="test[submit]"', $this->form->startForm());
    }

    /**
     * @covers \E4u\Form\Base::showForm
     */
    public function testEndForm()
    {
        $this->assertContains('</form>', $this->form->endForm());
    }

    /**
     * @covers \E4u\Form\Base::showForm
     */
    public function testShowForm()
    {
        $this->assertContains('<form', $this->form->showForm());
        $this->assertContains('</form>', $this->form->showForm());
        $this->assertContains('class="test"', $this->form->showForm('Test formularza', [ 'class' => 'test' ]));
        $this->assertContains('Test formularza', $this->form->showForm('Test formularza', [ 'class' => 'test' ]));
    }

    /**
     * @covers \E4u\Form\Base::addField
     * @covers \E4u\Form\Base::getElement
     */
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

    /**
     * @covers \E4u\Form\Base::getValue
     * @covers \E4u\Form\Base::setValue
     */
    public function testGetValue()
    {
        $field = new Form\Element\TextField('test', 'Pole testowe');
        $this->form->addField($field);

        $field->setValue('A');
        $this->assertEquals('A', $this->form->getValue('test'));

        $request = $this->form->getRequest();
        $request->getPost()->fromArray([ 'test' => [ 'submit' => true, 'test' => 'C' ] ]);
        $this->assertEquals('C', $this->form->getValue('test'));
    }

    /**
     * @covers \E4u\Form\Base::setMethod
     * @covers \E4u\Form\Base::getMethod
     */
    public function testMethod()
    {
        // default method
        $this->assertEquals(Form\Base::HTTP_POST, $this->form->getMethod());

        // override default method
        $this->form->setMethod('get');
        $this->assertEquals(Form\Base::HTTP_GET, $this->form->getMethod());
    }

    /**
     * @covers \E4u\Form\Base::htmlId
     */
    public function testHtmlId()
    {
        $this->assertEquals('test', $this->form->htmlId());
    }

    /**
     * @covers \E4u\Form\Base::addError
     * @covers \E4u\Form\Base::getErrors
     * @covers \E4u\Form\Base::validate
     */
    public function testErrors()
    {
        $this->assertEmpty($this->form->getErrors());

        $this->form->addError('Błąd!', 'active');
        $this->assertCount(1, $this->form->getErrors());

        $this->form->getElement('name')->setRequired();
        $this->form->validate();
        $this->assertCount(2, $this->form->getErrors());
    }

    /**
     * @covers \E4u\Form\Base::isSubmitted
     */
    public function testIsSubmitted()
    {
        $this->assertFalse($this->form->isSubmitted());

        $request = $this->form->getRequest();
        $request->getPost()->fromArray([ 'test' => [ 'submit' => true, 'name' => 'TEST' ] ]);

        $this->assertTrue($this->form->isSubmitted());
    }

    /**
     * @covers \E4u\Form\Base::isValid
     */
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
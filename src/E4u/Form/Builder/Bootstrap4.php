<?php
namespace E4u\Form\Builder;

use E4u\Common\Variable;
use E4u\Form\Base,
    E4u\Form\Element,
    E4u\Common\Html,
    E4u\Application\View\Html as HtmlView,
    Laminas\Config\Config;
use E4u\Form\Exception;

class Bootstrap4 implements BuilderInterface
{
    /**
     * @var Base
     */
    protected $form;

    /**
     * @var HtmlView
     */
    protected $view;

    /**
     * @var Config
     */
    protected $options;

    public function __construct(Base $form, HtmlView $view, $options = [])
    {
        $this->form = $form;
        $this->view = $view;
        $this->options = new Config($options);
    }

    /**
     * @return Base
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param  string $text
     * @return string
     */
    protected function t($text)
    {
        return $this->view->t($text);
    }

    public function errors($options = [])
    {
        $errors = $this->form->getErrors();
        if (empty($errors)) {
            return null;
        }

        $content = [];
        foreach ($errors as $key => $error) {
            if (is_array($error)) {
                foreach ($error as $type => $message) {

                    $content[] = $this->view->tag('li', [ 'class' => is_string($type) ? $type : null ], $this->t($message));

                }
            }
            else {

                $content[] = $this->view->tag('li', $this->t($error));
            }
        }

        $attributes = array_merge([
            'class' => 'form-errors'
        ], $options);
        return $this->view->tag('ul', $attributes, join('', $content));
    }

    public function start($options = [])
    {
        $default = [
            'role' => 'form',
            'id' => $this->form->getName(),
            'name' => $this->form->getName(),
            'method' => $this->form->getMethod(),
            'enctype' => $this->form->getEnctype(),
            'novalidate' => $this->options->get('novalidate') ? 'novalidate' : null,
            'class' => $this->form->isSubmitted() ? 'was-validated' : null,
            'action' => $this->form->getAction(),
        ];

        $attributes = array_merge($default, $options);
        return sprintf('<form %s>', Html::attributes($attributes))
            . $this->submitToken()
            . $this->crsfToken();
    }

    protected function submitToken()
    {
        return $this->view->tag('input', [
            'type' => 'hidden',
            'name' => $this->form->getName() . '[submit]',
            'value' => 1,
        ]);
    }

    protected function crsfToken()
    {
        $token = $this->form->getCrsfTokenValue();
        if (empty($token)) {
            return null;
        }

        return $this->view->tag('input', [
            'type' => 'hidden',
            'name' => $this->form->getCrsfTokenName(),
            'value' => $token,
        ]);
    }

    public function end()
    {
        return '</form>';
    }

    public function fieldId($name, $value = null)
    {
        return "{$this->form->getName()}_{$name}" . ($value ? '_' . $value : '');
    }

    public function fieldName($name)
    {
        return "{$this->form->getName()}[{$name}]";
    }

    public function fieldHelp($name)
    {
        return "{$this->form->getName()}_{$name}" . 'Help';
    }

    /**
     * Available options:
     * - append
     * - prepend
     * - group_class
     * - input_class
     * - input_type
     * - placeholder
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function text($name, $options = [])
    {
        $content = $this->textTag($name, $options);
        $options = new Config($options);
        $field = $this->form->getElement($name);

        return $this->field($field, $options, $content);
    }

    /**
     * @param $name
     * @param $options
     * @return string
     */
    public function textTag($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);
        $type = $options->get('input_type', 'text');

        $value = $field->getValue();
        if ($value instanceof \DateTime) {

            switch ($type) {
                case 'datetime':
                case 'datetime-local':
                    $value = $value->format('Y-m-d\TH:i');
                    break;
                case 'date':
                    $value = $value->format('Y-m-d');
                    break;
                default:
                    $value = $value->format('Y-m-d H:i');
            }

        } elseif (is_string($value) && $type == 'number') {

            $value = str_replace(',', '.', $value);
            $value = str_replace(' ', '', $value);

        } elseif (is_null($value)) {

            $value = '';

        }

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),
            'required' => $field->isRequired() ? 'required' : null,

            'required' => $options->get('required', $field->isRequired()) ? 'required' : null,
            'disabled' => $options->get('disabled', $field->isDisabled()) ? 'disabled' : null,
            'readonly' => $options->get('readonly', $field->isReadonly()) ? 'readonly' : null,

            'value' => $value,
            'type' => $type,

            'min' => $options->get('min', $field->getAttribute('min')),
            'max' => $options->get('max', $field->getAttribute('max')),
            'step' => $options->get('step', $field->getAttribute('step')),

            'class' => $this->fieldInputClass($field, $options),
            'style' => $options->get('style', null),
            'placeholder' => $this->t($options->get('placeholder', $field->getLabel())),
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        return $this->view->tag('input', $attributes);
    }

    /**
     * @param  string $name
     * @return string
     */
    public function hidden($name)
    {
        $field = $this->form->getElement($name);

        $value = $field->getValue();
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d');
        }
        elseif (is_null($value)) {
            $value = '';
        }

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),
            'value' => $value,
            'type' => 'hidden',

        ]);

        return $this->view->tag('input', $attributes);
    }

    /**
     * @see text()
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function number($name, $options = [])
    {
        $options['input_type'] = 'number';
        return $this->text($name, $options);
    }

    public function numberTag($name, $options = [])
    {
        $options['input_type'] = 'number';
        return $this->textTag($name, $options);
    }

    /**
     * @see text()
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function password($name, $options = [])
    {
        $options['input_type'] = 'password';
        return $this->text($name, $options);
    }

    public function passwordTag($name, $options = [])
    {
        $options['input_type'] = 'password';
        return $this->textTag($name, $options);
    }

    /**
     * @see text()
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function date($name, $options = [])
    {
        $options['input_type'] = 'date';
        return $this->text($name, $options);
    }

    /**
     * @see text()
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function email($name, $options = [])
    {
        $options['input_type'] = 'email';
        return $this->text($name, $options);
    }

    public function emailTag($name, $options = [])
    {
        $options['input_type'] = 'email';
        return $this->textTag($name, $options);
    }

    /**
     * @see text()
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function file($name, $options = [])
    {
        $options = new Config($options);

        /** @var Element\FileUpload $field */
        $field = $this->form->getElement($name);

        $value = $field->getValue();
        if (!empty($value)) {
            // TODO: render currently uploaded file?
        }

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),
            'required' => $field->isRequired() ? 'required' : null,

            'type' => 'file',
            'accept' => $options->get('accept'),

            'class' => $this->fieldInputClass($field, $options),
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        $content = $this->view->tag('input', $attributes);
        return $this->field($field, $options, $content);
    }

    /**
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function multifile($name, $options = [])
    {
        $options = new Config($options);

        /** @var Element\MultiUpload $field */
        $field = $this->form->getElement($name);

        $value = $field->getValue();
        if (!empty($value)) {
            // TODO: render currently uploaded files?
        }

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name) . '[]',
            'id' => $this->fieldId($name),
            'required' => $field->isRequired() ? 'required' : null,

            'type' => 'file',
            'accept' => $options->get('accept'),
            'multiple' => true,

            'class' => $this->fieldInputClass($field, $options),
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        $content = $this->view->tag('input', $attributes);
        return $this->field($field, $options, $content);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function checkbox($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),
            'required' => $field->isRequired() ? 'required' : null,
            'checked' => $field->getValue() ? 'checked' : null,
            'value' => '1',

            'type' => 'checkbox',
            'class' => trim('custom-control-input ' . $options->get('input_class')),
        ]);

        $content = join('', [
            $this->view->tag('input', $attributes),
            $this->view->tag('span', [ 'class' => 'custom-control-indicator' ], ''),
            $this->view->tag('span', [ 'class' => 'custom-control-description' ], $this->t($field->getLabel())),
        ]);

        $label = $this->view->tag('label', [ 'class' => 'custom-control custom-checkbox' ], $content);

        $class = $options->get('group_class');
        if ($field->getErrors()) {
            $class .= ' has-danger';
        }

        return $this->formGroup(trim($class), [

            $label,
            $this->helpBlock($name, $options->get('hint') ?: $field->getHint()),

        ]);
    }

    /**
     * Available options:
     * - label
     * - group_class
     * - button_class
     * - button_type
     * - disabled
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function button($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'id' => $this->fieldId($name),
            'type' => $options->get('button_type', 'submit'),
            'class' => trim('btn btn-primary ' . $options->get('button_class')),
            'disabled' => $options->get('disabled'),

        ]);

        $label = $this->t($options->get('label', $field->getLabel()));
        return $this->view->tag('button', $attributes, $label);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     * - cols
     * - rows
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function textarea($name, $options = [])
    {
        $content = $this->textareaTag($name, $options);
        $options = new Config($options);
        $field = $this->form->getElement($name);

        return $this->field($field, $options, $content);
    }

    public function textareaTag($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),
            'required' => $field->isRequired() ? 'required' : null,
            'class' => $this->fieldInputClass($field, $options),
            'aria-describedby' => $this->fieldHelp($name),

            'cols' => $options->get('cols', 50),
            'rows' => $options->get('rows', 15),
        ]);

        $value = htmlspecialchars($field->getValue(), ENT_COMPAT | ENT_HTML5, 'UTF-8');
        return $this->view->tag('textarea', $attributes, $value);
    }

    /**
     * @param  string $caption
     * @param  mixed $value
     * @param  bool $selected
     * @param  string[] $data
     * @return string
     */
    public function selectOption($caption, $value, $selected = false, $data = [])
    {
        $attributes = [
            'value' => $value,
            'selected' => $selected,
        ];

        foreach ($data as $key => $dataValue) {
            $attributes[ 'data-' . $key ] = $dataValue;
        }

        return $this->view->tag('option', $attributes, $caption);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     * - show_label
     * - empty_caption
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function select($name, $options = [])
    {
        $content = $this->selectTag($name, $options);
        $options = new Config($options);
        $field = $this->form->getElement($name);

        return $this->field($field, $options, $content);
    }

    public function selectTag($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        if (!$field instanceof Element\Select) {
            throw new Exception(sprintf(
                'Form field passed to Bootstrap#select must be
                 instance of Element\Select, %s given.',
                Variable::getType($field)));
        }

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),
            'required' => $field->isRequired() ? 'required' : null,

            'placeholder' => $options->get('placeholder'),
            'class' => $this->fieldInputClass($field, $options),
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        $html = '';

        $placeholder = $options->get('placeholder');
        if ($placeholder) {
            $html .= $this->view->tag('option', [ 'selected' => true, 'disabled' => true, 'hidden' => true ], $placeholder);
        }

        $emptyCaption = $options->get('empty_caption', '');
        if ($emptyCaption !== false) {
            $html .= $this->view->tag('option', [ 'value' => '' ], $emptyCaption);
        }

        if ($field->getOptGroups()) {
            foreach ($field->getOptGroups() as $groupName => $groupOptions) {

                if (!empty($groupOptions)) {

                    $groupHtml = '';
                    foreach ($groupOptions as $value => $caption) {
                        $groupHtml .= $this->selectOption($this->t($caption), $value, $field->getValue() == $value, $field->getDataForOption($value));
                    }

                    $html .= $this->view->tag('optgroup', [ 'label' => $this->t($groupName) ], $groupHtml);
                }
            }
        }

        foreach ($field->getOptions() as $value => $caption) {
            $html .= $this->selectOption($this->t($caption), $value, $field->getValue() == $value, $field->getDataForOption($value));
        }

        return $this->view->tag('select', $attributes, $html);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     * - show_label
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function radioGroup($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        if (!$field instanceof Element\Options) {
            throw new Exception(sprintf(
                'Form field passed to Bootstrap#radioGroup must be
                 instance of Element\Options, %s given.',
                Variable::getType($field)));
        }

        $div = '';
        foreach ($field->getOptions() as $value => $caption) {

            $attributes = array_merge($field->getAttributes(), [

                'name' => $this->fieldName($name),
                'id' => $this->fieldId($name, $value),
                'checked' => $field->getValue() == $value ? 'checked' : null,
                'value' => $value,

                'type' => 'radio',
                'class' => trim('form-check-input' . $options->get('input_class')),
            ]);

            $content = $this->view->tag('input', $attributes);
            $label = $this->view->tag('label', [ 'class' => 'form-check-label' ], $content . ' ' . $this->t($caption));
            $div .= $this->view->tag('div', [ 'class' => 'form-check' ], $label);

        }

        return $this->field($field, $options, $div);
    }

    public function checkboxGroup($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        if (!$field instanceof Element\Options) {
            throw new Exception(sprintf(
                'Form field passed to Bootstrap#checkboxGroup must be
                 instance of Element\Options, %s given.',
                Variable::getType($field)));
        }

        $div = '';
        foreach ($field->getOptions() as $value => $caption) {
            $div .= $this->checkboxOption($name, $value, $caption, $options->toArray());
        }

        return $this->field($field, $options, $div);
    }

    public function checkboxOption($name, $value, $caption, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name) . '[]',
            'id' => $this->fieldId($name, $value),
            'checked' => in_array($value, $field->getValue()) ? 'checked' : null,
            'value' => $value,

            'type' => 'checkbox',
            'class' => trim('form-check-input' . $options->get('input_class')),
        ]);

        $content = $this->view->tag('input', $attributes);
        $label = $this->view->tag('label', [ 'class' => 'form-check-label' ], trim($content . ' ' . $this->t($caption)));

        return $this->view->tag('div', [ 'class' => 'form-check' ], $label);
    }

    protected function fieldInputClass(Element $field, Config $options)
    {
        return trim(
            'form-control ' .
            ($field->hasErrors() ? 'is-invalid ' : null) .
            $options->get('input_class')
        );
    }

    protected function field(Element $field, Config $options, $content)
    {
        $class = $options->get('group_class');
        return $this->formGroup(trim($class), [

            $this->label(
                $field->getName(),
                $this->options->get('show_labels', false) || $options->get('show_label')
            ),

            $this->inputGroup([
                $this->inputGroupAddon($options->get('prepend')),
                $content,
                $this->inputGroupAddon($options->get('append')),
            ]),

            $this->helpBlock(
                $field->getName(),
                $options->get('hint') ?: $field->getHint()
            ),

        ]);
    }

    public function label($name, $showLabels = true, $options = [ ])
    {
        $class = $showLabels ? null : 'sr-only';
        $options = new Config($options);

        $attributes = [
            'for' => $this->fieldId($name),
            'class' => trim($options->get('label_class') . ' ' . $class),
        ];
        return $this->view->tag('label', $attributes, $this->t($this->form->getElement($name)->getLabel()));
    }

    public function formGroup($class, $elements)
    {
        $elements = array_filter($elements);
        if (empty($elements)) {
            return '';
        }

        return $this->view->tag('div', [
            'class' => trim('form-group ' . $class),
        ], join(' ', $elements));
    }

    public function inputGroup($elements)
    {
        $elements = array_filter($elements);
        if (empty($elements)) {
            return '';
        }

        if (count($elements) == 1) {
            return join('', $elements);
        }

        return $this->view->tag('div', [
            'class' => 'input-group',
        ], join('', $elements));
    }

    public function inputGroupAddon($content)
    {
        if (empty($content)) {
            return '';
        }

        return $this->view->tag('span', [
            'class' => 'input-group-addon',
        ], $content);
    }

    protected function helpBlock($name, $content)
    {
        if (empty($content)) {
            return null;
        }

        return $this->view->tag('small', [
            'class' => 'form-text text-muted',
            'id' => $this->fieldHelp($name),
        ], $this->t($content));
    }
}
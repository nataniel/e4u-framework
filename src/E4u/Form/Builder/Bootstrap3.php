<?php
namespace E4u\Form\Builder;

use E4u\Common\Variable;
use E4u\Exception\LogicException;
use E4u\Form\Base,
    E4u\Form\Element,
    E4u\Common\Html,
    E4u\Application\View\Html as HtmlView,
    Laminas\Config\Config;

class Bootstrap3 implements BuilderInterface
{
    const
        FORM_DEFAULT = 'form',
        FORM_INLINE = 'form-inline',
        FORM_HORIZONTAL = 'form-horizontal';

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
     * @param  string $text
     * @return string
     */
    protected function t($text)
    {
        return $this->view->t($text);
    }

    protected function submitToken()
    {
        return Html::tag('input', [
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

        return Html::tag('input', [
            'type' => 'hidden',
            'name' => $this->form->getCrsfTokenName(),
            'value' => $token,
        ]);
    }

    public function errors()
    {
        $errors = $this->form->getErrors();
        if (empty($errors)) {
            return null;
        }

        $content = [];
        foreach ($errors as $key => $error) {
            if (is_array($error)) {
                foreach ($error as $type => $message) {

                    $content[] = Html::tag('li', [ 'class' => is_string($type) ? $type : null ], $this->t($message));
                }
            }
            else {

                $content[] = Html::tag('li', $this->t($error));
            }
        }

        return Html::tag('ul', [
            'class' => 'form-errors'
        ], join('', $content));
    }

    public function start($options = [])
    {
        $default = [
            'role' => 'form',
            'id' => $this->form->getName(),
            'name' => $this->form->getName(),
            'method' => $this->form->getMethod(),
            'enctype' => $this->form->getEnctype(),
            'class' => $this->options->get('form_class', self::FORM_DEFAULT),
            'novalidate' => $this->options->get('novalidate') ? 'novalidate' : null,
            'action' => $this->form->getAction(),
        ];

        $attributes = array_merge($default, $options);
        return sprintf('<form %s>', Html::attributes($attributes)).
            $this->submitToken().
            $this->crsfToken();
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

    public function label($name, $showLabels = true)
    {
        return Html::tag('label', [
            'for' => $this->fieldId($name),
            'class' => $showLabels ? 'control-label' : 'control-label sr-only',
        ], $this->t($this->form->getElement($name)->getLabel()));
    }

    public function inputGroupAddon($content)
    {
        if (empty($content)) {
            return '';
        }

        return Html::tag('div', [
            'class' => 'input-group-addon',
        ], $content);
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

        return Html::tag('div', [
            'class' => 'input-group',
        ], join('', $elements));
    }

    public function formGroup($class, $elements)
    {
        $elements = array_filter($elements);
        if (empty($elements)) {
            return '';
        }

        return Html::tag('div', [
            'class' => trim('form-group ' . $class),
        ], join(' ', $elements));
    }

    protected function helpBlock($content)
    {
        if (empty($content)) {
            return null;
        }

        return Html::tag('p', [ 'class' => 'help-block' ], $content);
    }

    protected function field(Element $field, Config $options, $content)
    {
        $class = $options->get('group_class');
        if ($field->getErrors()) {
            $class .= ' has-error';
        }

        return $this->formGroup(trim($class), [

            $this->label(
                $field->getName(),
                $this->options->get('show_labels', true) || $options->get('show_label')
            ),

            $this->inputGroup([
                $this->inputGroupAddon($options->get('prepend')),
                $content,
                $this->inputGroupAddon($options->get('append')),
            ]),

            $this->helpBlock($options->get('hint') ?: $field->getHint()),

        ]);
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
            'class' => trim('checkbox ' . $options->get('input_class')),
        ]);

        $content = Html::tag('input', $attributes);
        $label = Html::tag('label', [ 'class' => 'checkbox' ], $content . ' ' . $this->t($field->getLabel()));
        $div = Html::tag('div', [ 'class' => 'checkbox' ], $label);

        $class = $options->get('group_class');
        if ($field->getErrors()) {
            $class .= ' has-error';
        }

        return $this->formGroup(trim($class), [

            $div,
            $this->helpBlock($options->get('hint') ?: $field->getHint()),

        ]);
    }

    public function textarea($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),
            'required' => $field->isRequired() ? 'required' : null,
            'class' => trim('form-control ' . $options->get('input_class')),

            'cols' => $options->get('cols', 50),
            'rows' => $options->get('rows', 15),

        ]);

        $value = htmlspecialchars($field->getValue(), ENT_COMPAT | ENT_HTML5, 'UTF-8');
        $content = Html::tag('textarea', $attributes, $value);
        return $this->field($field, $options, $content);
    }

    /**
     * Available options:
     * - append
     * - prepend
     * - group_class
     * - input_class
     * - accept
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function file($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = $this->getFileAttributes($field, $options);

        $content = Html::tag('input', $attributes);
        return $this->field($field, $options, $content);
    }

    /**
     * Available options:
     * - append
     * - prepend
     * - group_class
     * - input_class
     * - accept
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function multifile($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = $this->getFileAttributes($field, $options);
        $attributes['name'] .= '[]';
        $attributes['multiple'] = 'multiple';

        $content = Html::tag('input', $attributes);
        return $this->field($field, $options, $content);
    }

    private function getFileAttributes(Element $field, $options)
    {
        return array_merge($field->getAttributes(), [

            'name' => $this->fieldName($field->getName()),
            'id' => $this->fieldId($field->getName()),
            'required' => $field->isRequired() ? 'required' : null,
            'type' => 'file',

            'class' => trim('form-control ' . $options->get('input_class')),
            'accept' => $options->get('accept'),

        ]);
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
        $options = new Config($options);
        $field = $this->form->getElement($name);
        $value = $field->getValue();
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d');
        }

        $attributes = $this->getTextAttributes($field, $value, $options);

        $content = Html::tag('input', $attributes);
        return $this->field($field, $options, $content);
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
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = $this->getTextAttributes($field, $field->getValue(), $options);
        $attributes['min'] = $options->get('min', isset($attributes['min']) ? $attributes['min'] : null);
        $attributes['max'] = $options->get('max', isset($attributes['max']) ? $attributes['max'] : null);

        $content = Html::tag('input', $attributes);
        return $this->field($field, $options, $content);
    }

    /**
     * @see text()
     * @param  $name
     * @param  array $options
     * @return string
     */
    public function password($name, $options = [])
    {
        $options['input_type'] = 'password';
        return $this->text($name, $options);
    }

    /**
     * @see text()
     * @param  $name
     * @param  array $options
     * @return string
     */
    public function email($name, $options = [])
    {
        $options['input_type'] = 'email';
        return $this->text($name, $options);
    }

    /**
     * Available options:
     * - append
     * - prepend
     * - group_class
     * - input_class
     *
     * @param  string $name
     * @param  array $options
     * @return string
     */
    public function date($name, $options = [])
    {
        $options['input_type'] = 'date';
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $value = $field->getValue();
        if ($value instanceof \DateTime) {
            $date = $value->format('Y-m-d');
        }
        elseif (is_string($value)) {
            $date = $value;
        }
        elseif (is_null($value)) {
            $date = '';
        }
        else {
            throw new LogicException(sprintf(
                'Form field passed to Bootstrap#date must be string
                 or \DateTime object, %s given.',
                Variable::getType($value)));
        }

        $attributes = $this->getTextAttributes($field, $date, $options);
        $attributes['min'] = $options->get('min', isset($attributes['min']) ? $attributes['min'] : null);
        $attributes['max'] = $options->get('max', isset($attributes['max']) ? $attributes['max'] : null);

        $content = Html::tag('input', $attributes);
        return $this->field($field, $options, $content);
    }


    /**
     * @param  Element $field
     * @param  string $value
     * @param  Config $options
     * @return string[]
     */
    protected function getTextAttributes(Element $field, $value, Config $options)
    {
        return array_merge($field->getAttributes(), [

            'name' => $this->fieldName($field->getName()),
            'id' => $this->fieldId($field->getName()),
            'required' => $field->isRequired() ? 'required' : null,
            'value' => $value,

            'type' => $options->get('input_type', 'text'),
            'class' => trim('form-control ' . $options->get('input_class')),
            'placeholder' => $this->t($options->get('placeholder', $field->getLabel())),

        ]);
    }

    /**
     * @param  string   $caption
     * @param  string   $value
     * @param  bool     $selected
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

        return Html::tag('option', $attributes, $caption);
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
        $options = new Config($options);

        /** @var Element\Options $field */
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),
            'required' => $field->isRequired() ? 'required' : null,

            'class' => trim('form-control ' . $options->get('input_class')),

        ]);

        $html = '';

        $empty_caption = $options->get('empty_caption', '');
        if ($empty_caption !== false) {
            $html .= Html::tag('option', [ 'value' => "" ], $empty_caption);
        }

        if ($field->getOptGroups()) {
            foreach ($field->getOptGroups() as $groupName => $groupOptions) {

                $groupHtml = '';
                foreach ($groupOptions as $value => $caption) {
                    $groupHtml .= $this->selectOption($this->t($caption), $value, $field->getValue() == $value, $field->getDataForOption($value));
                }

                $html .= Html::tag('optgroup', [ 'label' => $this->t($groupName) ], $groupHtml);
            }
        }

        foreach ($field->getOptions() as $value => $caption) {
            $html .= $this->selectOption($this->t($caption), $value, $field->getValue() == $value, $field->getDataForOption($value));
        }

        $content = Html::tag('select', $attributes, $html);
        return $this->field($field, $options, $content);
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

        if ($field instanceof Element\Options) {

            $div = '';
            foreach ($field->getOptions() as $value => $caption) {

                $attributes = array_merge($field->getAttributes(), [

                    'name' => $this->fieldName($name),
                    'id' => $this->fieldId($name, $value),
                    'checked' => $field->getValue() == $value ? 'checked' : null,
                    'value' => $value,

                    'type' => 'radio',
                    'class' => trim('radio' . $options->get('input_class')),
                ]);

                $content = Html::tag('input', $attributes);
                $label = Html::tag('label', [ 'class' => 'radio' ], $content . ' ' . $this->t($caption));
                $div .= Html::tag('div', [ 'class' => 'radio' ], $label);

            }
        }

        return $this->field($field, $options, $div);
    }

    /**
     * Available options:
     * - label
     * - group_class
     * - button_class
     * - button_type
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

        ]);

        $label = $this->t($options->get('label', $field->getLabel()));
        $content = Html::tag('button', $attributes, $label);
        return $this->formGroup($options->get('group_class'), [

            $content,

        ]);
    }
}
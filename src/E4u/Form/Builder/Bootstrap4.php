<?php
namespace E4u\Form\Builder;

use E4u\Common\Variable;
use E4u\Form\Element,
    E4u\Common\Html,
    Laminas\Config\Config;
use E4u\Form\Exception;

class Bootstrap4 extends Bootstrap3
{
    public function fieldHelp(string $name): string
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
     */
    public function text(string $name, array $options = []): string
    {
        $content = $this->textTag($name, $options);
        $options = new Config($options);
        $field = $this->form->getElement($name);

        return $this->field($field, $options, $content);
    }

    public function textTag($name, $options = [])
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);
        $type = $options->get('input_type', 'text');

        $value = $field->getValue();
        if ($value instanceof \DateTime) {

            $value = match ($type) {
                'datetime', 'datetime-local' => $value->format('Y-m-d\TH:i'),
                'date' => $value->format('Y-m-d'),
                default => $value->format('Y-m-d H:i'),
            };

        } elseif (is_string($value) && $type == 'number') {

            $value = str_replace(',', '.', $value);
            $value = str_replace(' ', '', $value);

        } elseif (is_null($value)) {

            $value = '';

        }

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),

            'required' => $options->get('required', $field->isRequired()) ? 'required' : null,
            'disabled' => $options->get('disabled', $field->isDisabled()) ? 'disabled' : null,
            'readonly' => $options->get('readonly', $field->isReadonly()) ? 'readonly' : null,

            'value' => $value,
            'type' => $type,

            'min' => $options->get('min', $field->getAttribute('min')),
            'max' => $options->get('max', $field->getAttribute('max')),
            'step' => $options->get('step', $field->getAttribute('step')),
            'list' => $options->get('list'),

            'class' => $this->fieldInputClass($field, $options),
            'style' => $options->get('style', null),
            'placeholder' => $this->t($options->get('placeholder', $field->getLabel())),
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        foreach ($options as $key => $option) {
            if (str_starts_with($key, 'data-')) {
                $attributes[ $key ] = $option;
            }
        }

        return Html::tag('input', $attributes);
    }

    public function hidden(string $name): string
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

        return Html::tag('input', $attributes);
    }

    /**
     * @see text()
     */
    public function number(string $name, array $options = []): string
    {
        $options['input_type'] = 'number';
        return $this->text($name, $options);
    }

    public function numberTag(string $name, array $options = []): string
    {
        $options['input_type'] = 'number';
        return $this->textTag($name, $options);
    }

    public function passwordTag(string $name, array $options = []): string
    {
        $options['input_type'] = 'password';
        return $this->textTag($name, $options);
    }

    /**
     * @see text()
     */
    public function date(string $name, array $options = []): string
    {
        $options['input_type'] = 'date';
        return $this->text($name, $options);
    }

    /**
     * @see text()
     */
    public function email(string $name, array $options = []): string
    {
        $options['input_type'] = 'email';
        return $this->text($name, $options);
    }

    public function emailTag(string $name, array $options = []): string
    {
        $options['input_type'] = 'email';
        return $this->textTag($name, $options);
    }

    /**
     * @see text()
     */
    public function file(string $name, array $options = []): string
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

            'required' => $options->get('required', $field->isRequired()) ? 'required' : null,
            'disabled' => $options->get('disabled', $field->isDisabled()) ? 'disabled' : null,
            'readonly' => $options->get('readonly', $field->isReadonly()) ? 'readonly' : null,

            'type' => 'file',
            'accept' => $options->get('accept', $field->getAttribute('accept')) ?: null,
            'capture' => $options->get('capture') ?: null,

            'class' => $this->fieldInputClass($field, $options),
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        foreach ($options as $key => $option) {
            if (str_starts_with($key, 'data-')) {
                $attributes[ $key ] = $option;
            }
        }

        $content = Html::tag('input', $attributes);
        return $this->field($field, $options, $content);
    }

    public function multifile(string $name, array $options = []): string
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

            'required' => $options->get('required', $field->isRequired()) ? 'required' : null,
            'disabled' => $options->get('disabled', $field->isDisabled()) ? 'disabled' : null,
            'readonly' => $options->get('readonly', $field->isReadonly()) ? 'readonly' : null,

            'type' => 'file',
            'accept' => $options->get('accept'),
            'multiple' => true,

            'class' => $this->fieldInputClass($field, $options),
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        foreach ($options as $key => $option) {
            if (str_starts_with($key, 'data-')) {
                $attributes[ $key ] = $option;
            }
        }

        $content = Html::tag('input', $attributes);
        return $this->field($field, $options, $content);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     */
    public function checkbox(string $name, array $options = []): string
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
            Html::tag('input', $attributes),
            Html::tag('span', [ 'class' => 'custom-control-indicator' ], ''),
            Html::tag('span', [ 'class' => 'custom-control-description' ], $this->t($field->getLabel())),
        ]);

        $label = Html::tag('label', [ 'class' => 'custom-control custom-checkbox' ], $content);

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
     */
    public function button(string $name, array $options = []): string
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'id' => $this->fieldId($name),
            'type' => $options->get('button_type', 'submit'),
            'class' => trim('btn btn-primary ' . $options->get('button_class')),
            'disabled' => $options->get('disabled'),

        ]);

        foreach ($options as $key => $option) {
            if (str_starts_with($key, 'data-')) {
                $attributes[ $key ] = $option;
            }
        }

        $label = $this->t($options->get('label', $field->getLabel()));
        return Html::tag('button', $attributes, $label);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     * - cols
     * - rows
     */
    public function textarea(string $name, array $options = []): string
    {
        $content = $this->textareaTag($name, $options);
        $options = new Config($options);
        $field = $this->form->getElement($name);

        return $this->field($field, $options, $content);
    }

    public function textareaTag(string $name, array $options = []): string
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
        return Html::tag('textarea', $attributes, $value);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     * - show_label
     * - empty_caption
     */
    public function select(string $name, array $options = []): string
    {
        $content = $this->selectTag($name, $options);
        $options = new Config($options);
        $field = $this->form->getElement($name);

        return $this->field($field, $options, $content);
    }

    public function selectTag(string $name, array $options = []): string
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
            $html .= Html::tag('option', [ 'selected' => true, 'disabled' => true, 'hidden' => true ], $placeholder);
        }

        $emptyCaption = $options->get('empty_caption', '');
        if ($emptyCaption !== false) {
            $html .= Html::tag('option', [ 'value' => '' ], $emptyCaption);
        }

        if ($field->getOptGroups()) {
            foreach ($field->getOptGroups() as $groupName => $groupOptions) {

                if (!empty($groupOptions)) {

                    $groupHtml = '';
                    foreach ($groupOptions as $value => $caption) {
                        $groupHtml .= $this->selectOption($this->t($caption), $value, $field->getValue() == $value, $field->getDataForOption($value));
                    }

                    $html .= Html::tag('optgroup', [ 'label' => $this->t($groupName) ], $groupHtml);
                }
            }
        }

        foreach ($field->getOptions() as $value => $caption) {
            $html .= $this->selectOption($this->t($caption), $value, $field->getValue() == $value, $field->getDataForOption($value));
        }

        return Html::tag('select', $attributes, $html);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     * - show_label
     */
    public function radioGroup(string $name, array $options = []): string
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

            $content = Html::tag('input', $attributes);
            $label = Html::tag('label', [ 'class' => 'form-check-label' ], $content . ' ' . $this->t($caption));
            $div .= Html::tag('div', [ 'class' => 'form-check' ], $label);

        }

        return $this->field($field, $options, $div);
    }

    public function checkboxGroup(string $name, array $options = []): string
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

    public function checkboxOption(string $name, string $value, string $caption, array $options = []): string
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

        $content = Html::tag('input', $attributes);
        $label = Html::tag('label', [ 'class' => 'form-check-label' ], trim($content . ' ' . $this->t($caption)));

        return Html::tag('div', [ 'class' => 'form-check' ], $label);
    }

    protected function fieldInputClass(Element $field, Config $options): string
    {
        return trim(
            'form-control ' .
            ($field->hasErrors() ? 'is-invalid ' : null) .
            $options->get('input_class')
        );
    }

    protected function field(Element $field, Config $options, string $content): string
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

    public function label(string $name, bool $showLabels = true, $options = []): string
    {
        $class = $showLabels ? null : 'sr-only';
        $options = new Config($options);

        $attributes = [
            'for' => $this->fieldId($name),
            'class' => trim($options->get('label_class') . ' ' . $class),
        ];
        return Html::tag('label', $attributes, $this->t($this->form->getElement($name)->getLabel()));
    }

    protected function helpBlock(?string $name, ?string $content = null): string
    {
        if (empty($content)) {
            return '';
        }

        return Html::tag('small', [
            'class' => 'form-text text-muted',
            'id' => $this->fieldHelp($name),
        ], $this->t($content));
    }
}
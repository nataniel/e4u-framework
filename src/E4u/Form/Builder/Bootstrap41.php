<?php
namespace E4u\Form\Builder;

use E4u\Common\Variable;
use E4u\Form\Base,
    E4u\Form\Element,
    E4u\Common\Html,
    E4u\Application\View\Html as HtmlView,
    Laminas\Config\Config;
use E4u\Form\Exception;

class Bootstrap41 extends Bootstrap4
{
    public function show(string $name, array $options = []): string
    {
        $field = $this->form->getElement($name);
        return match (get_class($field)) {
            Element\TextArea::class => $this->textarea($name, $options),
            Element\Select::class => $this->select($name, $options),
            Element\CheckBox::class => $this->checkbox($name, $options),
            Element\CheckBoxGroup::class => $this->checkboxGroup($name, $options),
            Element\RadioGroup::class => $this->radioGroup($name, $options),
            Element\Password::class => $this->password($name, $options),
            Element\Submit::class => $this->button($name, $options),
            Element\FileUpload::class => $this->file($name, $options),
            Element\MultiUpload::class => $this->multifile($name, $options),
            Element\Date::class => $this->date($name, $options),
            Element\Number::class => $this->number($name, $options),
            Element\Url::class => $this->text($name, array_merge($options, ['input_type' => 'url'])),
            Element\EmailAddress::class => $this->text($name, array_merge($options, ['input_type' => 'email'])),
            default => $this->text($name, $options),
        };
    }

    /**
     * @see text()
     */
    public function datetime(string $name, array $options = []): string
    {
        $options['input_type'] = 'datetime-local';
        return $this->text($name, $options);
    }

    public function customFile(string $name, array $options = []): string
    {
        $options = new Config($options, true);

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

            'class' => 'custom-file-input',
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        foreach ($options as $key => $option) {
            if (str_starts_with($key, 'data-')) {
                $attributes[ $key ] = $option;
            }
        }

        $content = Html::tag('input', $attributes);
        $label = $this->label($name, true, [ 'label_class' => 'custom-file-label' ]);

        $class = $this->formGroupClass($field, $options);
        return $this->formGroup($class, [
            Html::tag('div', [ 'class' => 'custom-file', ], $content . $label)
        ]);
    }

    /**
     * Available options:
     * - group_class
     * - input_class
     * - disabled
     * - required
     * - readonly
     * - hint
     */
    public function checkbox(string $name, array $options = []): string
    {
        $checkboxTag = $this->checkboxTag($name, $options);
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $content = join('', [
            $checkboxTag,
            Html::tag('label', [ 'class' => 'custom-control-label', 'for' => $this->fieldId($name) ], $this->t($field->getLabel())),
        ]);

        $div = Html::tag('div', [ 'class' => 'custom-control custom-checkbox' ], $content);

        $class = $this->formGroupClass($field, $options);
        return $this->formGroup($class, [

            $div,
            $this->helpBlock($name, $options->get('hint') ?: $field->getHint()),

        ]);
    }

    /**
     * Available options:
     * - input_class
     * - disabled
     * - required
     * - readonly
     * - hint
     */
    public function checkboxTag(string $name, array $options = []): string
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),

            'required' => $options->get('required', $field->isRequired()) ? 'required' : null,
            'disabled' => $options->get('disabled', $field->isDisabled()) ? 'disabled' : null,
            'readonly' => $options->get('readonly', $field->isReadonly()) ? 'readonly' : null,

            'checked' => $field->getValue() ? 'checked' : null,
            'value' => '1',

            'type' => 'checkbox',
            'class' => trim('custom-control-input ' . $options->get('input_class') . ($field->getErrors() ? 'is-invalid' : '')),
        ]);

        foreach ($options as $key => $option) {
            if (str_starts_with($key, 'data-')) {
                $attributes[ $key ] = $option;
            }
        }

        return Html::tag('input', $attributes);
    }

    public function textareaTag(string $name, array $options = []): string
    {
        $options = new Config($options);
        $field = $this->form->getElement($name);

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name),
            'id' => $this->fieldId($name),

            'required' => $options->get('required', $field->isRequired()) ? 'required' : null,
            'disabled' => $options->get('disabled', $field->isDisabled()) ? 'disabled' : null,
            'readonly' => $options->get('readonly', $field->isReadonly()) ? 'readonly' : null,

            'class' => $this->fieldInputClass($field, $options),
            'aria-describedby' => $this->fieldHelp($name),

            'cols' => $options->get('cols', 50),
            'rows' => $options->get('rows', 15),
        ]);

        foreach ($options as $key => $option) {
            if (str_starts_with($key, 'data-')) {
                $attributes[ $key ] = $option;
            }
        }

        $value = htmlspecialchars($field->getValue(), ENT_COMPAT | ENT_HTML5, 'UTF-8');
        return Html::tag('textarea', $attributes, $value);
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

            'required' => $options->get('required', $field->isRequired()) ? 'required' : null,
            'disabled' => $options->get('disabled', $field->isDisabled()) ? 'disabled' : null,
            'readonly' => $options->get('readonly', $field->isReadonly()) ? 'readonly' : null,

            'placeholder' => $options->get('placeholder'),
            'class' => $this->fieldInputClass($field, $options),
            'aria-describedby' => $this->fieldHelp($name),

        ]);

        foreach ($options as $key => $option) {
            if (str_starts_with($key, 'data-')) {
                $attributes[ $key ] = $option;
            }
        }

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
     * - disabled
     * - required
     * - readonly
     * - inline
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
            $div .= Html::tag('div', [ 'class' => $this->optionClass($options) ], $label);

        }

        return $this->field($field, $options, $div);
    }

    public function checkboxOption(string $name, string $value, string $caption, array $options = []): string
    {
        $options = new Config($options);

        /** @var Element\CheckBoxGroup $field */
        $field = $this->form->getElement($name);
        $data = $field->getDataForOption($value);

        $attributes = array_merge($field->getAttributes(), [

            'name' => $this->fieldName($name) . '[]',
            'id' => $this->fieldId($name, $value),
            'checked' => in_array($value, $field->getValue()) ? 'checked' : null,
            'value' => $value,

            'type' => 'checkbox',
            'class' => trim('form-check-input ' . $options->get('input_class')),
        ]);

        foreach ($data as $key => $dataValue) {
            $attributes[ 'data-' . $key ] = $dataValue;
        }

        $label = Html::tag('label', [
            'class' => 'form-check-label',
            'for' => $this->fieldId($name, $value),
        ], $this->t($caption));

        $content = Html::tag('input', $attributes);
        return Html::tag('div', [ 'class' => $this->optionClass($options) ], $content . $label);
    }

    protected function optionClass(Config $options): string
    {
        $isInline = $options->get('inline', false);
        return $isInline ? 'form-check form-check-inline' : 'form-check';
    }

    protected function formGroupClass(Element $field, Config $options): string
    {
        $class
            = $options->get('group_class')
            . ' type-' . $field->getType()
            . ' form-group-' . $field->getName();
        return trim($class);
    }

    protected function field(Element $field, Config $options, string $content): string
    {
        $class = $this->formGroupClass($field, $options);
        return $this->formGroup($class, [

            $this->label(
                $field->getName(),
                $options->get('show_label', $this->options->get('show_labels', false))
            ),

            $this->inputGroup([
                $this->inputGroupAddon($options->get('prepend'), 'prepend'),
                $content,
                $this->inputGroupAddon($options->get('append'), 'append'),
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

        return Html::tag('label', [
            'for' => $this->fieldId($name),
            'class' => trim($options->get('label_class') . ' ' . $class),
        ], $this->t($this->form->getElement($name)->getLabel()));
    }

    public function inputGroupAddon(?string $content, $type = 'append'): string
    {
        if (empty($content)) {
            return '';
        }

        $html = Html::tag('span', [ 'class' => 'input-group-text' ], $content);
        return Html::tag('span', [
            'class' => 'input-group-' . $type,
        ], $html);
    }
}

<?php
namespace E4u\Form;

use ArrayAccess;
use E4u\Common\StringTools;
use E4u\Common\Variable;
use E4u\Exception\LogicException;
use E4u\Model\Entity;
use E4u\Model\Validatable;
use Laminas\Validator;

abstract class Element
{
    protected Validator\ValidatorChain $validatorChain;

    protected mixed $value;

    protected mixed $default = null;

    protected ?ArrayAccess $model;
    protected ?string $model_field;

    protected bool $required = false;
    protected array $errors = [];

    protected string $name;
    protected ?string $label;
    protected ?string $hint;

    /** @var array HTML attributes for the element **/
    protected array $attributes = [];

    public function __construct(string $name, string|array|null $properties = null)
    {
        $this->setName($name);
        if (is_string($properties)) {
            $this->setLabel($properties);
        }
        elseif (is_array($properties)) {
            $this->setProperties($properties);
        }
    }

    public function setProperties(array $properties = []): static
    {
        $properties = array_filter($properties, fn($val) => !is_null($val));
        foreach ($properties as $key => $value) {
            $method  = 'set'.StringTools::camelCase($key);
            if (method_exists($this, $method)) {
                call_user_func_array([$this, $method], [$value]);
            }
            else {
              $this->_set($key, $value);
            }
        }

        return $this;
    }

    public function setAttributes(array $attributes = []): static
    {
        $attributes = array_filter($attributes, 'strlen');
        foreach ($attributes as $key => $value) {
            $this->_set($key, $value);
        }

        return $this;
    }

    /*
     * magical setter / getter for $this->attributes
     */
    public function __call($method, $argv)
    {
        if (preg_match('/^(set|get)([A-Z].*)$/', $method, $matches)) {
            $method = '_'.$matches[1];
            $matches[2][0] = strtolower($matches[2][0]);
            $property = $matches[2];
            array_unshift($argv, $property);
            return call_user_func_array([$this, $method], $argv);
        }

        throw new LogicException(
            sprintf('Call to undefined method %s::%s()',
            get_class($this), $method));
    }

    protected function _set($attr, $value): static
    {
        $this->attributes[$attr] = $value;
        return $this;
    }

    protected function _get($attr): mixed
    {
        if (isset($this->attributes[$attr])) {
            return $this->attributes[$attr];
        }

        return null;
    }

    public function setReadonly(bool $flag = true): static
    {
        if ($flag) {
            $this->attributes['readonly'] = 'readonly';
        }
        elseif (isset($this->attributes['readonly'])) {
            unset($this->attributes['readonly']);
        }

        return $this;
    }

    public function isReadonly(): bool
    {
        return !empty($this->attributes['readonly']);
    }

    public function setDisabled(bool $flag = true): static
    {
        if ($flag) {
            $this->attributes['disabled'] = 'disabled';
        }
        elseif (isset($this->attributes['disabled'])) {
            unset($this->attributes['disabled']);
        }

        return $this;
    }

    public function isDisabled(): bool
    {
        return !empty($this->attributes['disabled']);
    }

    public function setAutofocus(bool $flag = true): static
    {
        if ($flag) {
            $this->attributes['autofocus'] = 'autofocus';
        }
        elseif (isset($this->attributes['autofocus'])) {
            unset($this->attributes['autofocus']);
        }

        return $this;
    }
    
    public function isAutofocus(): bool
    {
        return isset($this->attributes['autofocus']);
    }

    public function setAutocomplete(bool|string $flag = true): static
    {
        if (is_bool($flag)) {
            $this->attributes['autocomplete'] = $flag ? 'autocomplete' : null;
        }
        else {
            $this->attributes['autocomplete'] = $flag;
        }

        return $this;
    }

    public function setModel(ArrayAccess $model, ?string $model_field = null): static
    {
        // we cannot use:
        // $element = new Element([ 'model' => 'somename' ])
        // because element knows nothing about
        // the models defined in parent form

        $this->model = $model;
        $this->model_field = $model_field;
        return $this;
    }

    public function setModelField(?string $model_field): static
    {
        $this->model_field = $model_field;
        return $this;
    }

    public function getModel(): ?ArrayAccess
    {
        return $this->model;
    }

    public function getModelField(): string
    {
        return $this->model_field ?: $this->getName();
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    public function getLabel(): string
    {
        return $this->label ?: ucfirst($this->name);
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): string
    {
        $class = static::class;
        return strtolower(preg_replace('/.*\\\\/', '', $class));
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function setHint(?string $hint): static
    {
        $this->hint = $hint;
        return $this;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function getValue(): mixed
    {
        if (!isset($this->model)) {
            return !isset($this->value)
                ? $this->default
                : $this->value;
        }

        $field = $this->getModelField();
        if ($this->model instanceof ArrayAccess) {
            $value = $this->model[ $field ];
        }
        else {
            /** deprecated, model should always be \ArrayAccess instance */
            $method = Variable::propertyGetMethod($field);
            $value = $this->getModel()->$method();
        }

        return ($value instanceof Entity)
            ? $value->id()
            : $value;
    }

    public function setDefault(mixed $value): static
    {
        if (!is_null($this->model)) {
            throw new LogicException(
                'Cannot assign default value to the model-related form element.');
        }

        $this->default = $value instanceof Entity
            ? $value->id()
            : $value;

        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function setValue(mixed $value): static
    {
        if (!isset($this->model)) {
            $this->value = $value;
            return $this;
        }

        $field = $this->getModelField();
        if ($this->model instanceof ArrayAccess) {
            $this->model[ $field ] = $value;
            return $this;
        }

        /** deprecated, model should always be \ArrayAccess instance */
        $method = Variable::propertySetMethod($this->getModelField());
        $this->getModel()->$method($value);
        return $this;
    }

    public function addError(string $message): static
    {
        $this->errors[] = $message;
        return $this;
    }

    public function getErrors(): array
    {
        return array_merge($this->errors, $this->getValidatorChain()->getMessages());
    }

    public function hasErrors(): bool
    {
        return count($this->getErrors()) > 0;
    }

    public function isValid(): bool
    {
        $value = $this->getValue();
        if (!$this->isRequired() && empty($value)) {
            return true;
        }

        if (!$this->getValidatorChain()->isValid($value)) {
            return false;
        }

        if ($this->model instanceof Validatable) {
            if (!$this->model->valid()) {
                if ($error = $this->model->getErrors($this->getModelField())) {
                    $this->errors[] = $error;
                }
            }
        }

        return empty($this->errors);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(null|bool|string $message = null): static
    {
        if ($message === false) {
            $this->required = false;
            unset($this->attributes['required']);
            return $this;
        }

        $this->required = true;
        $this->attributes['required'] = 'required';
        $this->addValidator(new Validator\NotEmpty(), $message);
        return $this;
    }

    public function setPattern(string $pattern, ?string $message = null): static
    {
        $this->attributes['pattern'] = $pattern;
        if (empty($message)) {
            $message = 'Nieprawidłowy format pola: ' . $this->getLabel() . '.';
        }

        $this->addValidator(new Validator\Regex('/^' . $pattern . '$/'), $message);
        return $this;
    }

    public function addValidator(Validator\ValidatorInterface|string $validator, ?string $message = null, bool $breakChainOnFailure = true): static
    {
        if (is_string($validator)) {
            $validator = new $validator();
        }

        if (null !== $message) {
            $validator->setMessage($message);
        }

        $this->getValidatorChain()->attach($validator, $breakChainOnFailure);
        return $this;
    }

    protected function getValidatorChain(): Validator\ValidatorChain
    {
        if (!isset($this->validatorChain)) {
            $this->validatorChain = new Validator\ValidatorChain();
        }

        return $this->validatorChain;
    }
}

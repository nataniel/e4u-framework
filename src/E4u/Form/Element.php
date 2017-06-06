<?php
namespace E4u\Form;

use E4u\Common\Variable;
use E4u\Model\Entity;
use Zend\Validator\ValidatorChain,
    Zend\Validator\ValidatorInterface;

abstract class Element
{
    /**
     * @var ValidatorChain
     */
    protected $validatorChain;

    /**
     * Current value of the field
     * @var mixed
     */
    protected $value;

    /**
     * Default value of the field
     * @var mixed
     */
    protected $default;

    /**
     * Attached model
     * @var mixed
     */
    protected $model;
    protected $model_field;

    protected $required = false;
    protected $errors = [];

    protected $name;
    protected $label;
    protected $hint;

    protected $cssClass;

    /** @var array HTML attributes for the element **/
    protected $attributes = array();

    public function __construct($name, $properties = null)
    {
        $this->setName($name);
        if (is_string($properties)) {
            $this->setLabel($properties);
        }
        elseif (is_array($properties)) {
            $this->setProperties($properties);
        }
    }

    /**
     * @param array $properties
     * @return \E4u\Form\Element
     */
    public function setProperties($properties = [])
    {
        $properties = array_filter($properties, function ($val) { return !is_null($val); });
        foreach ($properties as $key => $value) {
            $method  = 'set'.\E4u\Common\StringTools::camelCase($key);
            if (method_exists($this, $method)) {
                call_user_func_array([$this, $method], [$value]);
            }
            else {
              $this->_set($key, $value);
            }
        }

        return $this;
    }

    /**
     * @param array $attributes
     * @return \E4u\Form\Element
     */
    public function setAttributes($attributes = [])
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

        throw new \E4u\Exception\LogicException(
            sprintf('Call to undefined method %s::%s()',
            get_class($this), $method));
    }

    protected function _set($attr, $value)
    {
        $this->attributes[$attr] = $value;
        return $this;
    }

    protected function _get($attr)
    {
        if (isset($this->attributes[$attr])) {
            return $this->attributes[$attr];
        }

        return null;
    }

    /**
     * @param  boolean $flag
     * @return Element
     */
    public function setDisabled($flag = true)
    {
        if ($flag == true) {
            $this->attributes['disabled'] = 'disabled';
        }
        elseif (isset($this->attributes['disabled'])) {
            unset($this->attributes['disabled']);
        }

        return $this;
    }

    /**
     * @param  boolean $flag
     * @return Element
     */
    public function setAutofocus($flag = true)
    {
        if ($flag == true) {
            $this->attributes['autofocus'] = 'autofocus';
        }
        elseif (isset($this->attributes['autofocus'])) {
            unset($this->attributes['autofocus']);
        }

        return $this;
    }

    /**
     * @param  boolean $flag
     * @return Element
     */
    public function setAutocomplete($flag = true)
    {
        if ($flag == true) {
            $this->attributes['autocomplete'] = 'autocomplete';
        }
        elseif (isset($this->attributes['autocomplete'])) {
            unset($this->attributes['autocomplete']);
        }

        return $this;
    }

    /**
     * @param  mixed    $model to attach
     * @param  string   $model_field model field name
     * @return Element  Current instance
     */
    public function setModel($model, $model_field = null)
    {
        // we cannot use:
        // $element = new Element([ 'model' => 'somename' ])
        // because element knows nothing about
        // the models defined in parent form
        
//        if (is_string($model)) {
//            $model = $this->getModel($model);
//        }

        $this->model = $model;
        $this->model_field = $model_field;
        return $this;
    }

    /**
     * @param  string   $model_field model field name
     * @return Element  Current instance
     */
    public function setModelField($model_field)
    {
        $this->model_field = $model_field;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    public function getModelField()
    {
        return $this->model_field ?: $this->getName();
    }

    /**
     * @return string[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getLabel()
    {
        return $this->label ?: ucfirst($this->name);
    }

    /**
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function htmlId($formName)
    {
        return "{$formName}_{$this->getName()}";
    }

    /**
     * Returns field name formatted for html "name" attribute:
     * foo[bar] or foo[bar][]
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function htmlName($formName, $multiple = false)
    {
        return "{$formName}[{$this->getName()}]" . ($multiple ? '[]' : '');
    }

    /**
     *  <label for="login_login">
     *      Adres e-mail
     *      <span>(wymagane)</span>
     *  </label>
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function showLabel($formName)
    {
        $required = $this->isRequired() ? '<span>(wymagane)</span>' : '';
        return '<label for="'.$this->htmlId($formName).'">'.
                    $this->label.$required.
                '</label>';
    }

    /**
     * <div class="input">
     *     <input type="text" name="login[login]" id="login_login" value="xxx" />
     * </div>
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function showInput($formName)
    {
        return '<div class="input">'.
                    $this->render($formName).
                '</div>';
    }

    /**
     * <input type="text" name="login[login]" id="login_login" value="xxx" />
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public abstract function render($formName);

    /**
     * <p class="hint">np. kasia@kowalska.info.pl</p>
     *
     * @deprecated use Form\Builder instead
     * @return string
     */
    public function showHint()
    {
        return '<p class="hint">'.$this->hint.'</p>';
    }

    /**
     * <p class="error">Nieprawidłowy adres e-mail.</p>
     *
     * @deprecated use Form\Builder instead
     * @return string
     */
    public function showError()
    {
        if ($errors = $this->getErrors()) {
            if (is_array($errors)) {
                $errors = join(' ', $errors);
            }

            return '<p class="error">'.$errors.'</p>';
        }

        return null;
    }

    /**
     *  <div class="field text_field required" id="field-login_login">
     *      <label for="login_login">
     *          Adres e-mail
     *          <span>(wymagane)</span>
     *      </label>
     *      <div class="input">
     *          <input type="text" name="login[login]" id="login_login" value="xxx" />
     *      </div>
     *      <p class="hint">np. kasia@kowalska.info.pl</p>
     *      <p class="error"></p>
     *  </div>
     *
     * @deprecated use Form\Builder instead
     */
    public function showHTML($formName)
    {

        $id    = "field-".$this->htmlId($formName);
        $class = join(' ', array_filter([
            'field',
            $this->cssClass,
            $this->isRequired() ? 'required' : null,
            $this->getErrors() ? 'invalid' : null,
        ]));

        return '<div class="'.$class.'" id="'.$id.'">'.
                    $this->showLabel($formName).
                    $this->showInput($formName).
                    $this->showHint().
                    $this->showError().
                '</div>';
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function setHint($hint)
    {
        $this->hint = $hint;
        return $this;
    }

    public function getHint()
    {
        return $this->hint;
    }

    public function setReadonly($flag = true)
    {
        if ($flag == true) {
            $this->attributes['readonly'] = 'readonly';
        }
        else {
            unset($this->attributes['readonly']);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if (!is_null($this->model)) {
            $method = Variable::propertyGetMethod($this->getModelField());
            $value = $this->getModel()->$method();

            return ($value instanceof Entity)
                 ? $value->id()
                 : $value;
        }

        return is_null($this->value)
             ? $this->default
             : $this->value;
    }

    /**
     * @return mixed
     */
    public function setDefault($value)
    {
        if (!is_null($this->model)) {
            throw new \E4u\Exception\LogicException(
                'Cannot assign default value to the model-related form element.');
        }

        $this->default = $value instanceof Entity
            ? $value->id()
            : $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param  mixed $value
     * @return Element
     */
    public function setValue($value)
    {
        if (null === $this->model) {
            $this->value = $value;
            return $this;
        }

        $method = Variable::propertySetMethod($this->getModelField());
        $this->getModel()->$method($value);
        return $this;
    }

    public function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return array_merge($this->errors, $this->getValidatorChain()->getMessages());
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        $value = $this->getValue();
        if (!$this->isRequired() && empty($value)) {
            return true;
        }

        if (!$this->getValidatorChain()->isValid($value)) {
            return false;
        }

        if (!is_null($this->model) && $this->model instanceof \E4u\Model\Validatable) {
            if (!$this->model->valid()) {
                if ($error = $this->model->getErrors($this->getModelField())) {
                    $this->errors[] = $error;
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param  string $message
     * @return Element
     */
    public function setRequired($message = null)
    {
        if ($message === false) {
            $this->required = false;
            unset($this->attributes['required']);
            return $this;
        }

        $this->required = true;
        $this->attributes['required'] = 'required';
        $this->addValidator(new \Zend\Validator\NotEmpty(), $message);
        return $this;
    }

    /**
     * @param  string $pattern
     * @param  string $message
     * @return $this
     */
    public function setPattern($pattern, $message = null)
    {
        $this->attributes['pattern'] = $pattern;
        if (empty($message)) {
            $message = 'Nieprawidłowy format pola: ' . $this->getLabel() . '.';
        }

        $this->addValidator(new \Zend\Validator\Regex('/^' . $pattern . '$/'), $message);
        return $this;
    }

    /**
     * @param  ValidatorInterface|string $validator
     * @param  string $message
     * @param  bool   $breakChainOnFailure
     * @return $this
     */
    public function addValidator($validator, $message = null, $breakChainOnFailure = true)
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

    /**
     * @return ValidatorChain
     */
    protected function getValidatorChain()
    {
        if (null === $this->validatorChain) {
            $this->validatorChain = new ValidatorChain();
        }

        return $this->validatorChain;
    }
}
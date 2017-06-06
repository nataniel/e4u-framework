<?php
namespace E4u\Form;

use E4u\Request\Request;

class Base
{
    use \E4u\Application\Helper\Url;

    const
        HTTP_GET = 'get',
        HTTP_POST = 'post';

    const
        ENCTYPE_DEFAULT = 'application/x-www-form-urlencoded',
        ENCTYPE_MULTIPART = 'multipart/form-data',
        ENCTYPE_TEXT = 'text/plain';

    const VALID_EMAIL = 'Zend\Validator\EmailAddress';

    protected $method = self::HTTP_POST;
    protected $name;
    protected $request;
    protected $action;
    protected $enctype = self::ENCTYPE_DEFAULT;

    protected $models = [];

    // values submitted via POST or GET
    protected $values;
    protected $errors = [];

    /* @var Element[] */
    protected $fields = [];

    protected $crsf_protection = true;
    protected $crsf_token;

    public function __construct(Request $request, $models = [], $name = null)
    {
        if (is_string($models)) {
            $name = $models;
            $models = [];
        }

        $this->request = $request;
        $this->name = $name;

        foreach ($models as $key => $model) {
            $this->setModel($key, $model);
        }

        $this->init();
    }

    public function setModel($name, $model)
    {
        $this->models[$name] = $model;
        return $this;
    }

    public function getModel($name)
    {
        return $this->models[$name];
    }

    public function setAction($url)
    {
        $this->action = $this->urlTo($url);
        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Convenient method to be overwritten in extending class.
     */
    public function init()
    {

    }

    /**
     * @deprecated use Form\Builder instead
     * @param  array $elements If null, show all elements
     * @return string
     */
    public function showFields($elements = null)
    {
        if (null === $elements) {
            $elements = array_keys($this->fields);
        }

        $html = '';
        foreach ($elements as $element) {
            $html .= $this->showField($element);
        }

        return $html;
    }

    /**
     * @deprecated use Form\Builder instead
     * @param  array  $elements
     * @param  string $caption
     * @return string
     */
    public function showFieldset($elements = null, $caption = null, $attributes = [])
    {
        if (is_string($attributes)) {
            $class = $attributes;
            $attributes = [ 'class' => $class ];
        }

        return sprintf('<fieldset %s>', \E4u\Common\Html::attributes($attributes)).
                   ($caption ? '<legend><h3>'.$caption.'</h3></legend>' : '').
                   $this->showFields($elements).
               '</fieldset>';
    }

    /**
     * @deprecated use Form\Builder instead
     * @param  array $attributes
     * @return string
     */
    public function startForm($attributes = [])
    {
        $attributes['id']      = @$attributes['id']      ?: $this->htmlId();
        $attributes['name']    = @$attributes['name']    ?: $this->getName();
        $attributes['action']  = @$attributes['action']  ?: $this->getAction();
        $attributes['method']  = @$attributes['method']  ?: $this->getMethod();
        $attributes['enctype'] = @$attributes['enctype'] ?: $this->getEnctype();
        $attributes['class']   = @$attributes['class']   ?: 'powerForm';

        return sprintf('<form %s>', \E4u\Common\Html::attributes($attributes)).
               '<input type="hidden" name="'.$this->getName().'[submit]" value="1" />'.
               $this->showCrsfToken();
    }

    /**
     * @deprecated use Form\Builder instead
     * @return string
     */
    public function endForm()
    {
        return '</form>';
    }

    /**
     * @deprecated use Form\Builder instead
     * @param  string $caption
     * @return string
     */
    public function showForm($caption = null, $attributes = [])
    {
        $fieldset = [];
        if (isset($attributes['fieldset'])) {
            $fieldset = $attributes['fieldset'];
            unset($attributes['fieldset']);
        }

        return $this->startForm($attributes).
               $this->showErrors().
               $this->showFieldset(array_keys($this->fields), $caption, $fieldset).
               $this->endForm();
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getEnctype()
    {
        return $this->enctype;
    }

    /**
     * @deprecated use Form\Builder instead
     * @param  string $name
     * @return string
     */
    public function showField($name)
    {
        return $this->getElement($name)->showHTML($this->getName());
    }

    /**
     * @return bool
     */
    public function verifyCrsfToken()
    {
        if (!$this->crsf_protection) {
            return true;
        }

        switch ($this->method) {
            case self::HTTP_GET:
                $crsf_token = $this->request->getQuery('crsf_token');
                break;
            case self::HTTP_POST:
                $crsf_token = $this->request->getPost('crsf_token');
                break;
        }

        return isset($_SESSION['crsf_token'])
            ? $_SESSION['crsf_token'] == $crsf_token
            : false;
    }

    /**
     * @return Base
     */
    public function generateCrsfToken()
    {
        $this->crsf_token = md5(uniqid(rand(), true));
        $_SESSION['crsf_token'] = $this->crsf_token;
        return $this;
    }

    /**
     * @return string
     */
    public function getCrsfToken()
    {
        if (!$this->crsf_protection) {
            return null;
        }

        if (null == $this->crsf_token) {
            if (isset($_SESSION['crsf_token'])) {
                $this->crsf_token = $_SESSION['crsf_token'];
            }
            else {
                $this->generateCrsfToken();
            }
        }

        return $this->crsf_token;
    }

    /**
     * @deprecated use Form\Builder instead
     * @return string
     */
    public function showCrsfToken()
    {
        if (!$this->crsf_protection) {
            return null;
        }

        $attributes = [
            'type' => 'hidden',
            'name' => 'crsf_token',
            'value' => $this->getCrsfToken(),
        ];

        return \E4u\Common\Html::tag('input', $attributes);
    }

    /**
     * @deprecated use Form\Builder instead
     * @param  string $header
     * @return string
     */
    public function showErrors($header = 'Wystąpiły błędy')
    {
        $errors = $this->getErrors();
        if (empty($errors)) {
            return null;
        }

        $html = '';
        foreach ($errors as $key => $error) {
            if (is_array($error)) {
                foreach ($error as $type => $message) {
                    $html .= '<li class="'.$type.'"><a href="#'.$this->getName().'-'.$key.'">'.$message.'</a></li>'."\n";
                }
            }
            else {
                $html .= '<li><a href="#'.$this->getName().'-'.$key.'">'.$error.'</a></li>'."\n";
            }
        }

        $html = "<h3>$header</h3><ul>$html</ul>";
        $html = '<header class="errors">'.$html.'</header>';
        return $html;
    }

    /**
     * @param  Element[] $fields
     * @param  string $model
     * @return Base
     */
    public function addFields($fields, $model = null)
    {
        foreach ($fields as $field) {
            $this->addField($field, $model);
        }

        return $this;
    }

    /**
     * @param  Element $field
     * @param  string $model
     * @param  string $model_field
     * @return Base
     */
    public function addField(Element $field, $model = null, $model_field = null)
    {
        $name = $field->getName();
        if (isset($this->fields[$name])) {
            throw new \E4u\Exception\LogicException(
                "Field $name already defined for ".  get_class($this).".");
        }

        if (!is_null($model)) {
            if (!isset($this->models[$model])) {
                throw new \E4u\Exception\LogicException(
                    "Model $model not defined for ".  get_class($this).".");
            }

            $field->setModel($this->getModel($model), $model_field);
        }

        if ($field instanceof Element\FileUpload) {
            $this->setEnctype(self::ENCTYPE_MULTIPART);
        }

        $this->fields[$name] = $field;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if (null == $this->name) {
            // My\Form\Login -> login
            $this->name = strtolower(get_class($this));
            $this->name = preg_replace('/.*\\\\/', '', $this->name);
        }

        return $this->name;
    }

    /**
     * @deprecated use Form\Builder instead
     * @return string
     */
    public function htmlId()
    {
        return $this->getName();
    }

    /**
     * @param  string
     * @return mixed
     */
    public function getValue($name)
    {
        $this->initValues();
        return $this->fields[$name]->getValue();
    }

    /**
     * @return array
     */
    public function getValues($list = null)
    {
        $values = [];
        $list = is_null($list)
            ? array_keys($this->fields)
            : array_intersect($list, array_keys($this->fields));

        $this->initValues();
        foreach ($list as $name) {
            $values[$name] = $this->fields[$name]->getValue();
        }

        return $values;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getValues();
    }

    /**
     * @param  array $files
     * @return Base
     */
    protected function processFiles($files)
    {
        if (is_array($files)) {

            # echo "<pre>"; var_dump($files); echo "</pre>";
            foreach ($files as $key => $field) {

                if (array_key_exists('name', $field)) {

                    // single file
                    if (!empty($field['name']) && is_uploaded_file($field['tmp_name'])) {
                        $this->values[$key] = $field;
                    }

                }
                else {

                    // multiple files
                    $this->values[$key] = [];
                    foreach ($field as $file) {
                        if (!empty($file['name']) && is_uploaded_file($file['tmp_name'])) {
                            $this->values[$key][] = $file;
                        }
                    }

                }

            }

        }

        # echo "<pre>"; var_dump($this->values); exit();
        return $this;
    }

    /**
     * @return array
     */
    protected function initValues()
    {
        if (null == $this->values) {

            $this->values = [];
            if ($this->verifyCrsfToken()) {

                switch ($this->method) {
                    case self::HTTP_GET:
                        $this->values = $this->request->getQuery($this->getName());
                        break;
                    case self::HTTP_POST:
                        $this->values = $this->request->getPost($this->getName());
                        if ($this->enctype == self::ENCTYPE_MULTIPART) {
                            $this->processFiles($this->request->getFiles($this->getName()));
                        }

                        break;
                }

                if (!empty($this->values)) {
                    foreach ($this->fields as $key => $element) {
                        $element->setValue(isset($this->values[$key])
                                ? $this->values[$key]
                                : null);
                    }
                }

            }
        }

        return $this->values;
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        $defaults = [];
        foreach ($this->fields as $key => $element) {
            $defaults[$key] = $element->getDefault();
        }

        return $defaults;
    }

    /**
     * @param array $defaults
     * @return Base
     */
    public function setDefaults($defaults)
    {
        foreach ($defaults as $name => $value) {
            if (isset($this->fields[$name])) {
                $this->fields[$name]->setDefault($value);
            }
        }

        return $this;
    }

    /**
     *
     * @param  string $message
     * @param  string $name
     * @return Base
     */
    public function addError($message, $name = null)
    {
        if (null !== $name) {
            if (!isset($this->fields[$name])) {
                throw new \E4u\Exception\LogicException(
                    'Invalid form field: '.$name);
            }

            $this->fields[$name]->addError($message);
            $this->errors[$name] = $message;
        }
        else {
            $this->errors[] = $message;
        }

        return $this;
    }

    /**
     * @param  string $name
     * @return Element
     */
    public function getElement($name)
    {
        if (!isset($this->fields[$name])) {
            throw new \E4u\Exception\LogicException(
                'Invalid form field: '.$name);
        }
        
        return $this->fields[$name];
    }

    /**
     * @return Base
     */
    public function validate()
    {
        foreach ($this->fields as $key => $field) {
            if (!$field->isValid()) {
                $this->errors[$key] = $field->getErrors();
            }
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        if (!$this->isSubmitted()) {
            return false;
        }

        $this->validate();
        return empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return boolean
     */
    public function isSubmitted()
    {
        $values = $this->initValues();
        return !empty($values['submit']);
    }

    /**
     * @param bool $flag
     * @return Base
     */
    public function setCrsfProtection($flag)
    {
        $this->crsf_protection = (bool)$flag;
        return $this;
    }

    /**
     * @param  string $method
     * @param  bool $crsf_protection
     * @return Base
     */
    public function setMethod($method, $crsf_protection = null)
    {
        if (constant('self::HTTP_'.strtoupper($method))) {
            $this->method = $method;
            $this->crsf_protection = is_null($crsf_protection)
                    ? $this->method != self::HTTP_GET
                    : $crsf_protection;
        }

        return $this;
    }

    /**
     * @param  string $enctype
     * @return Base
     */
    public function setEnctype($enctype)
    {
        $this->enctype = $enctype;
        return $this;
    }
}
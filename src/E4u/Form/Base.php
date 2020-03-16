<?php
namespace E4u\Form;

use E4u\Application\Helper\Url;
use E4u\Common\Html;
use E4u\Exception\LogicException;
use E4u\Request\Request;

class Base
{
    use Url;

    const
        HTTP_GET = 'get',
        HTTP_POST = 'post';

    const
        ENCTYPE_DEFAULT = 'application/x-www-form-urlencoded',
        ENCTYPE_MULTIPART = 'multipart/form-data',
        ENCTYPE_TEXT = 'text/plain';

    const VALID_EMAIL = \Laminas\Validator\EmailAddress::class;

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

    private $crsf_protection;
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
        $this->models[ $name ] = $model;
        return $this;
    }

    public function getModel($name)
    {
        return $this->models[ $name ];
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
     * @return bool
     */
    public function verifyCrsfToken()
    {
        if (!$this->isCrsfProtectionEnabled()) {
            return true;
        }

        $crsf_name = $this->getCrsfTokenName();
        $crsf_token = $this->method == self::HTTP_GET
            ? $this->request->getQuery($crsf_name)
            : $this->request->getPost($crsf_name);

        return isset($_SESSION[ $crsf_name ])
            ? $_SESSION[ $crsf_name ] == $crsf_token
            : false;
    }

    /**
     * @return $this
     */
    public function generateCrsfToken($crsf_name)
    {
        $this->crsf_token = md5(uniqid(rand(), true));
        $_SESSION[ $crsf_name ] = $this->crsf_token;
        return $this;
    }

    /**
     * @return string
     */
    public function getCrsfTokenName()
    {
        return $this->getName() . '_crsf';
    }

    /**
     * @return string
     */
    public function getCrsfTokenValue()
    {
        if (!$this->isCrsfProtectionEnabled()) {
            return null;
        }

        if (null == $this->crsf_token) {
            $crsf_name = $this->getCrsfTokenName();
            if (isset($_SESSION[ $crsf_name ])) {
                $this->crsf_token = $_SESSION[ $crsf_name ];
            }
            else {
                $this->generateCrsfToken($crsf_name);
            }
        }

        return $this->crsf_token;
    }

    /**
     * @return bool
     */
    public function isCrsfProtectionEnabled()
    {
        if (!is_null($this->crsf_protection)) {
            return (bool)$this->crsf_protection;
        }

        return $this->method != self::HTTP_GET;
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function hasField($name)
    {
        return isset($this->fields[ $name ]);
    }

    /**
     * @param  Element[] $fields
     * @param  string $model
     * @return $this
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
     * @return $this
     */
    public function addField(Element $field, $model = null, $model_field = null)
    {
        $name = $field->getName();
        if (isset($this->fields[ $name ])) {
            throw new LogicException(
                "Field $name already defined for ".  get_class($this).".");
        }

        if (!is_null($model)) {
            if (!isset($this->models[ $model ])) {
                throw new LogicException(
                    "Model $model not defined for ".  get_class($this).".");
            }

            $field->setModel($this->getModel($model), $model_field);
        }

        if ($field instanceof Element\FileUpload) {
            $this->setEnctype(self::ENCTYPE_MULTIPART);
        }

        $this->fields[ $name ] = $field;
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
     * @param  string
     * @return mixed
     */
    public function getValue($name)
    {
        $this->initValues();
        return $this->fields[ $name ]->getValue();
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
            $values[ $name ] = $this->fields[ $name ]->getValue();
        }

        return $values;
    }

    /**
     * @return UploadedFile[]
     */
    public function getFiles($name)
    {
        $files = [];
        $value = $this->getValue($name);

        if (!is_array($value)) {
            throw new Exception('Invalid file value.');
        }

        if (isset($value['tmp_name'])) {
            // single file
            $files[] = new UploadedFile($value);
        }
        else {
            // array of files
            foreach ($value as $val) {
                $files[] = new UploadedFile($val);
            }
        }

        return $files;
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
     * @return $this
     */
    protected function processFiles($files)
    {
        if (!is_array($files)) {
            return $this;
        }

        foreach ($files as $key => $value) {

            array_key_exists('name', $value)
                ? $this->processSingleFile($value, $key)
                : $this->processMultipleFiles($value, $key);

        }

        return $this;
    }

    /**
     * @param  string[] $file
     * @param  string $key
     * @return $this
     */
    protected function processSingleFile($file, $key)
    {
        if (!empty($file['name']) && is_uploaded_file($file['tmp_name'])) {
            $this->values[ $key ] = $file;
        }

        return $this;
    }

    /**
     * @param  array $files
     * @param  string $key
     * @return $this
     */
    protected function processMultipleFiles($files, $key)
    {
        $this->values[ $key ] = [];
        foreach ($files as $file) {
            if (!empty($file['name']) && is_uploaded_file($file['tmp_name'])) {
                $this->values[ $key ][] = $file;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function initValues()
    {
        if (null !== $this->values) {
            return $this;
        }

        $this->values = [];
        if (!$this->verifyCrsfToken()) {
            return $this;
        }

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

        if (empty($this->values)) {
            return $this;
        }

        foreach ($this->fields as $key => $element) {
            if (!$element->isDisabled()) {
                $element->setValue(isset($this->values[ $key ])
                    ? $this->values[ $key ]
                    : null);
            }
        }
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        $defaults = [];
        foreach ($this->fields as $key => $element) {
            $defaults[ $key ] = $element->getDefault();
        }

        return $defaults;
    }

    /**
     * @param  array $defaults
     * @return $this
     */
    public function setDefaults($defaults)
    {
        foreach ($defaults as $name => $value) {
            if (isset($this->fields[ $name ])) {
                $this->fields[ $name ]->setDefault($value);
            }
        }

        return $this;
    }

    /**
     *
     * @param  string $message
     * @param  string $name
     * @return $this
     */
    public function addError($message, $name = null)
    {
        if (null !== $name) {
            if (!isset($this->fields[ $name ])) {
                throw new LogicException(
                    'Invalid form field: '.$name);
            }

            $this->fields[ $name ]->addError($message);
            $this->errors[ $name ] = $message;
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
        if (!isset($this->fields[ $name ])) {
            throw new LogicException(
                'Invalid form field: '.$name);
        }
        
        return $this->fields[ $name ];
    }

    /**
     * @return $this
     */
    public function validate()
    {
        foreach ($this->fields as $key => $field) {
            if (!$field->isValid()) {
                $this->errors[ $key ] = $field->getErrors();
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

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return boolean
     */
    public function isSubmitted()
    {
        $this->initValues();
        return !empty($this->values['submit']);
    }

    /**
     * @param  bool $flag
     * @return $this
     */
    public function setCrsfProtection($flag)
    {
        $this->crsf_protection = (bool)$flag;
        return $this;
    }

    /**
     * @param  string $method
     * @param  bool $crsf_protection
     * @return $this
     */
    public function setMethod($method, $crsf_protection = null)
    {
        if (constant('self::HTTP_' . strtoupper($method))) {
            $this->method = $method;

            if (!is_null($crsf_protection)) {
                $this->crsf_protection = $crsf_protection;
            }
        }

        return $this;
    }

    /**
     * @param  string $enctype
     * @return $this
     */
    public function setEnctype($enctype)
    {
        $this->enctype = $enctype;
        return $this;
    }
}
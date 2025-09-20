<?php
namespace E4u\Form;

use E4u\Application\Helper\Url;
use E4u\Exception\LogicException;
use E4u\Request\Request;

class Base
{
    use Url;

    const string
        HTTP_GET = 'get',
        HTTP_POST = 'post';

    const string
        ENCTYPE_DEFAULT = 'application/x-www-form-urlencoded',
        ENCTYPE_MULTIPART = 'multipart/form-data',
        ENCTYPE_TEXT = 'text/plain';

    const string VALID_EMAIL = \Laminas\Validator\EmailAddress::class;

    protected string $method = self::HTTP_POST;
    protected string $name;
    protected Request $request;
    protected ?string $action;
    protected string $enctype = self::ENCTYPE_DEFAULT;

    protected array $models = [];

    // values submitted via POST or GET
    protected array $values;
    protected array $errors = [];

    /* @var Element[] */
    protected array $fields = [];

    private bool $crsf_protection;
    protected string $crsf_token;

    public function __construct(Request $request, string|array $models = [], ?string $name = null)
    {
        if (is_string($models)) {
            $name = $models;
            $models = [];
        }

        $this->request = $request;
        if (!empty($name)) {
            $this->name = $name;
        }

        foreach ($models as $key => $model) {
            $this->setModel($key, $model);
        }

        $this->init();
    }

    public function setModel(string $name, mixed $model): static
    {
        $this->models[ $name ] = $model;
        return $this;
    }

    public function getModel(string $name): mixed
    {
        return $this->models[ $name ];
    }

    public function setAction(string $url): static
    {
        $this->action = $this->urlTo($url);
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Convenient method to be overwritten in extending class.
     */
    public function init()
    {

    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEnctype(): string
    {
        return $this->enctype;
    }

    public function verifyCrsfToken(): bool
    {
        if (!$this->isCrsfProtectionEnabled()) {
            return true;
        }

        $crsf_name = $this->getCrsfTokenName();
        $crsf_token = $this->method == self::HTTP_GET
            ? $this->request->getQuery($crsf_name)
            : $this->request->getPost($crsf_name);

        return isset($_SESSION[ $crsf_name ]) && $_SESSION[ $crsf_name ] === $crsf_token;
    }

    public function generateCrsfToken(string $crsf_name): void
    {
        $this->crsf_token = md5(uniqid(rand(), true));
        $_SESSION[ $crsf_name ] = $this->crsf_token;
    }

    public function getCrsfTokenName(): string
    {
        return $this->getName() . '_crsf';
    }

    public function getCrsfTokenValue(): ?string
    {
        if (!$this->isCrsfProtectionEnabled()) {
            return null;
        }

        if (!isset($this->crsf_token)) {
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
    public function isCrsfProtectionEnabled(): bool
    {
        if (isset($this->crsf_protection)) {
            return $this->crsf_protection;
        }

        return $this->method != self::HTTP_GET;
    }

    public function hasField(string $name): bool
    {
        return isset($this->fields[ $name ]);
    }

    public function addFields(array $fields, ?string $model = null): static
    {
        foreach ($fields as $field) {
            $this->addField($field, $model);
        }

        return $this;
    }

    public function addField(Element $field, ?string $model = null, ?string $model_field = null): static
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

    public function getName(): string
    {
        if (!isset($this->name)) {
            // My\Form\Login -> login
            $this->name = strtolower(get_class($this));
            $this->name = preg_replace('/.*\\\\/', '', $this->name);
        }

        return $this->name;
    }

    public function getValue(string $name): mixed
    {
        $this->initValues();
        return $this->fields[ $name ]->getValue();
    }

    public function getValues(?array $list = null): array
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
    public function getFiles(string $name): array
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

    public function toArray(): array
    {
        return $this->getValues();
    }

    protected function processFiles(?iterable $files): void
    {
        if (is_null($files)) {
            return;
        }
        
        foreach ($files as $key => $value) {

            array_key_exists('name', $value)
                ? $this->processSingleFile($value, $key)
                : $this->processMultipleFiles($value, $key);

        }
    }

    protected function processSingleFile(array $file, string $key): void
    {
        if (!empty($file['name']) && is_uploaded_file($file['tmp_name'])) {
            $this->values[ $key ] = $file;
        }
    }

    protected function processMultipleFiles(array $files, string $key): void
    {
        $this->values[ $key ] = [];
        foreach ($files as $file) {
            if (!empty($file['name']) && is_uploaded_file($file['tmp_name'])) {
                $this->values[ $key ][] = $file;
            }
        }
    }

    public function initValues(): void
    {
        if (isset($this->values)) {
            return;
        }

        $this->values = [];
        if (!$this->verifyCrsfToken()) {
            return;
        }

        switch ($this->method) {
            case self::HTTP_GET:
                $this->values = $this->request->getQuery($this->getName()) ?? [];
                break;
            case self::HTTP_POST:
                $this->values = $this->request->getPost($this->getName()) ?? [];
                if ($this->enctype == self::ENCTYPE_MULTIPART) {
                    $this->processFiles($this->request->getFiles($this->getName()));
                }

                break;
        }

        if (empty($this->values)) {
            return;
        }

        $this->setFieldsValues();
    }

    protected function setFieldsValues(): void
    {
        foreach ($this->fields as $key => $element) {
            if (!$element->isDisabled()) {
                $element->setValue($this->values[$key] ?? null);
            }
        }
    }

    public function getDefaults(): array
    {
        return array_map(function ($element) {
            return $element->getDefault();
        }, $this->fields);
    }

    public function setDefaults(array $defaults): static
    {
        foreach ($defaults as $name => $value) {
            if (isset($this->fields[ $name ])) {
                $this->fields[ $name ]->setDefault($value);
            }
        }

        return $this;
    }

    public function removeField(string $name): void
    {
        if (!isset($this->fields[ $name ])) {
            throw new LogicException(
                'Invalid form field: '.$name);
        }

        unset($this->fields[ $name ]);
    }

    public function addError(string $message, ?string $name = null): static
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

    public function getElement(string $name): Element
    {
        if (!isset($this->fields[ $name ])) {
            throw new LogicException(
                'Invalid form field: '.$name);
        }

        return $this->fields[ $name ];
    }

    public function validate(): void
    {
        foreach ($this->fields as $key => $field) {
            if (!$field->isValid()) {
                $this->errors[ $key ] = $field->getErrors();
            }
        }
    }

    public function isValid(): bool
    {
        if (!$this->isSubmitted()) {
            return false;
        }

        $this->validate();
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isSubmitted(): bool
    {
        $this->initValues();
        return !empty($this->values['submit']);
    }

    public function setCrsfProtection(bool $flag = true): static
    {
        $this->crsf_protection = $flag;
        return $this;
    }

    public function setMethod(string $method, ?bool $crsf_protection = null): static
    {
        if (constant('self::HTTP_' . strtoupper($method))) {
            $this->method = $method;

            if (!is_null($crsf_protection)) {
                $this->crsf_protection = $crsf_protection;
            }
        }

        return $this;
    }

    public function setEnctype(string $enctype): static
    {
        $this->enctype = $enctype;
        return $this;
    }
}

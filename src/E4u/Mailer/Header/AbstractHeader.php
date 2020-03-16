<?php
namespace E4u\Mailer\Header;

use E4u\Exception\LogicException;
use Laminas\Mail\Header,
    Laminas\Mail\Mime;

class AbstractHeader implements Header\HeaderInterface
{
    /**
     * @var string
     */
    protected $fieldName = null;

    /**
     * @var string
     */
    protected $fieldValue = null;

    /**
     * Header encoding
     *
     * @var null|string
     */
    protected $encoding;

    /**
     * @param string $headerLine
     * @return static
     */
    public static function fromString($headerLine)
    {
        list($name, $value) = self::splitHeaderLine($headerLine);
        $value  = Header\HeaderWrap::mimeDecodeValue($value);
        $header = new static($name, $value);

        return $header;
    }

    /**
     * Splits the header line in `name` and `value` parts.
     *
     * @param string $headerLine
     * @return string[] `name` in the first index and `value` in the second.
     * @throws LogicException If header does not match with the format ``name:value``
     */
    public static function splitHeaderLine($headerLine)
    {
        $parts = explode(':', $headerLine, 2);
        if (count($parts) !== 2) {
            throw new LogicException('Header must match with the format "name:value"');
        }

        if (! Header\HeaderName::isValid($parts[0])) {
            throw new LogicException('Invalid header name detected');
        }

        if (! Header\HeaderValue::isValid($parts[1])) {
            throw new LogicException('Invalid header value detected');
        }

        $parts[1] = ltrim($parts[1]);
        return $parts;
    }

    /**
     * Constructor
     *
     * @param string $fieldNameOrValue
     * @param string $fieldValue Optional
     */
    public function __construct($fieldNameOrValue, $fieldValue = null)
    {
        if (is_null($fieldValue)) {
            $this->setFieldValue($fieldNameOrValue);
        }
        else {

            $this->setFieldName($fieldNameOrValue);
            $this->setFieldValue($fieldValue);

        }
    }

    /**
     * Set header name
     *
     * @param  string $fieldName
     * @return $this
     * @throws LogicException;
     */
    public function setFieldName($fieldName)
    {
        if (!is_string($fieldName) || empty($fieldName)) {
            throw new LogicException('Header name must be a string');
        }

        // Pre-filter to normalize valid characters, change underscore to dash
        $fieldName = str_replace(' ', '-', ucwords(str_replace(['_', '-'], ' ', $fieldName)));

        if (! Header\HeaderName::isValid($fieldName)) {
            throw new LogicException(
                'Header name must be composed of printable US-ASCII characters, except colon.'
            );
        }

        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set header value
     *
     * @param  string $fieldValue
     * @return $this
     * @throws LogicException;
     */
    public function setFieldValue($fieldValue)
    {
        $fieldValue = (string) $fieldValue;

        if (! Header\HeaderWrap::canBeEncoded($fieldValue)) {
            throw new LogicException(
                'Header value must be composed of printable US-ASCII characters and valid folding sequences.'
            );
        }

        $this->fieldValue = $fieldValue;
        $this->encoding   = null;

        return $this;
    }

    /**
     * @param  bool $format
     * @return string
     */
    public function getFieldValue($format = Header\HeaderInterface::FORMAT_RAW)
    {
        if (Header\HeaderInterface::FORMAT_ENCODED === $format) {
            return Header\HeaderWrap::wrap($this->fieldValue, $this);
        }

        return $this->fieldValue;
    }

    /**
     * @param  string $encoding
     * @return $this
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        if (! $this->encoding) {
            $this->encoding = Mime::isPrintable($this->fieldValue) ? 'ASCII' : 'UTF-8';
        }

        return $this->encoding;
    }

    /**
     * @return string
     * @throws LogicException
     */
    public function toString()
    {
        $name = $this->getFieldName();
        if (empty($name)) {
            throw new LogicException('Header name is not set, use setFieldName()');
        }
        $value = $this->getFieldValue(Header\HeaderInterface::FORMAT_ENCODED);

        return $name . ': ' . $value;
    }
}
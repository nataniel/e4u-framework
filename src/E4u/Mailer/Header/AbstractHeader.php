<?php
namespace E4u\Mailer\Header;

use E4u\Exception\LogicException;
use Laminas\Mail\Header;

class AbstractHeader implements Header\HeaderInterface
{
    protected ?string $fieldName = null;
    protected ?string $fieldValue = null;
    protected ?string $encoding;

    /**
     * @param string $headerLine
     */
    public static function fromString($headerLine)
    {
        list($name, $value) = self::splitHeaderLine($headerLine);
        $value  = Header\HeaderWrap::mimeDecodeValue($value);
        return new static($name, $value);
    }

    /**
     * Splits the header line in `name` and `value` parts.
     *
     * @param string $headerLine
     * @return string[] `name` in the first index and `value` in the second.
     * @throws LogicException If header does not match with the format ``name:value``
     */
    public static function splitHeaderLine(string $headerLine): array
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

    public function __construct(string $fieldNameOrValue, ?string $fieldValue = null)
    {
        if (is_null($fieldValue)) {
            $this->setFieldValue($fieldNameOrValue);
        }
        else {

            $this->setFieldName($fieldNameOrValue);
            $this->setFieldValue($fieldValue);

        }
    }

    public function setFieldName(string $fieldName): static 
    {
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

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function setFieldValue(string $fieldValue): static
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
    public function getFieldValue($format = Header\HeaderInterface::FORMAT_RAW): string
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
            $this->encoding = $this->isPrintable($this->fieldValue) ? 'ASCII' : 'UTF-8';
        }

        return $this->encoding;
    }

    public static function isPrintable(string $str): bool
    {
        $qpKeysString =
        "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F\x80\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF\xB0\xB1\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
        
        return (strcspn($str, $qpKeysString) == strlen($str));
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
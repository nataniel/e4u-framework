<?php
namespace E4u\Mailer\Header;

use E4u\Exception\LogicException;
use Laminas\Mail\Header\GenericHeader,
    Laminas\Mail\Header\HeaderWrap,
    Laminas\Mail\Header\HeaderValue;

class ListUnsubscribe extends AsciiHeader
{
    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
        $value = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'list-unsubscribe') {
            throw new LogicException('Invalid header line for List-Unsubscribe string');
        }

        return new static($value);
    }

    public function getFieldName(): ?string
    {
        return 'List-Unsubscribe';
    }

    public function setFieldValue(string $fieldValue): static
    {
        if (! HeaderValue::isValid($fieldValue)
            || preg_match("/[\r\n]/", $fieldValue)
            || !filter_var($fieldValue, FILTER_VALIDATE_URL)
        ) {
            throw new LogicException('Invalid Url detected');
        }

        parent::setFieldValue(sprintf('<%s>', $fieldValue));
        return $this;
    }
}

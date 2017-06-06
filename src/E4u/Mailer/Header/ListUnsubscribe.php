<?php
namespace E4u\Mailer\Header;

use E4u\Exception\LogicException;
use Zend\Mail\Header\GenericHeader,
    Zend\Mail\Header\HeaderWrap,
    Zend\Mail\Header\HeaderValue;

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

        $header = new static($value);
        return $header;
    }

    public function getFieldName()
    {
        return 'List-Unsubscribe';
    }

    /**
     * Set the unsubscribe url
     *
     * @param  string $url
     * @return ListUnsubscribe
     */
    public function setFieldValue($url)
    {
        if (! HeaderValue::isValid($url)
            || preg_match("/[\r\n]/", $url)
            || !filter_var($url, FILTER_VALIDATE_URL)
        ) {
            throw new LogicException('Invalid Url detected');
        }

        parent::setFieldValue(sprintf('<%s>', $url));
        return $this;
    }
}

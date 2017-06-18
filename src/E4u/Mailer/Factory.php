<?php
namespace E4u\Mailer;

use E4u\Exception\LogicException;
use Zend\Mail\Transport;

class Factory
{
    const
        SMTP = 'smtp',
        FILE = 'file',
        TEST = 'test',
        SENDMAIL = 'sendmail';

    /**
     * @param $type
     * @param $options
     * @return Transport\TransportInterface
     */
    public static function get($type, $options)
    {
        switch ($type) {
            case self::SMTP:
                return new Transport\Smtp(new Transport\SmtpOptions($options));
            case self::FILE:
                return new Transport\File(new Transport\FileOptions($options));
            case self::SENDMAIL:
                return new Transport\Sendmail($options);
            case self::TEST:
                return new \E4u\Mailer\Test($options);
            default:
                if (class_exists($type)) {
                    return new $type($options);
                }
        }

        throw new LogicException(sprintf(
            "Invalid or undefined mailer type: %s.", $type));
    }
}
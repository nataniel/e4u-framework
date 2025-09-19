<?php
namespace E4u\Mailer;

use E4u\Exception\LogicException;
use Laminas\Mail\Transport;

class Factory
{
    const string
        SMTP = 'smtp',
        FILE = 'file',
        TEST = 'test',
        SENDMAIL = 'sendmail';

    public static function get(string $type, ?iterable $options): Transport\TransportInterface
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
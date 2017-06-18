<?php
namespace E4u\Common;

use E4u\Exception\LogicException;

class Template
{
    public static function wolacz($vars, $options = null)
    {
        $name = null; $locale = 'pl';
        if (is_array($vars) || $vars instanceof \ArrayAccess) {
            if (isset($vars['first_name'])) { $name = $vars['first_name']; }
            elseif (isset($vars['name'])) { $name = $vars['name']; }

            if (isset($vars['locale'])) { $locale = $vars['locale']; }
        }

        if ($locale === 'pl') {
            return StringTools::wolacz($name);
        }

        return $name;
    }

    public static function pln($vars, $options = null)
    {
        if (is_array($vars) || $vars instanceof \ArrayAccess) {
            if (isset($vars[$options])) {
                return number_format($vars[$options], 2, ',', ' ') . ' zł';
            }
        }

        return '';
    }

    public static function replace($name, $vars, $options = null)
    {
        if (strpos($name, '.')) {

            list($var, $name) = explode('.', $name);
            $vars = $vars[$var];

        }

        if (empty($options) && isset($vars[$name])) {

            return is_array($vars[$name])
                ? join("\n", $vars[$name])
                : $vars[$name];
        }

        if (method_exists(__CLASS__, $name)) {
            return self::$name($vars, $options);
        }

        return $name . ' / ' . $options;
    }
    
    public static function merge($txt, $vars = null)
    {
        if (!is_null($vars) && !is_array($vars) && !$vars instanceof \ArrayAccess) {
            throw new LogicException(sprintf(
                '$vars can be null, array or ArrayAccess, %s given.',
                Variable::getType($vars)));
        }
        
        return preg_replace_callback('/\[\[((?<name>.*)(\((?<options>.*)\))?)\]\]/U', function ($matches) use ($vars) {

            if (empty($matches['name'])) {
                return '';
            }

            return !empty($matches['options'])
                ? self::replace($matches['name'], $vars, @$matches['options'])
                : self::replace($matches['name'], $vars);

        }, $txt);
    }
}
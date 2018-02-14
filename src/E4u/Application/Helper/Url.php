<?php
namespace E4u\Application\Helper;

use E4u\Request;
use Zend\Uri\Uri;

trait Url
{
    /**
     * Class importing this method must declare getRequest() method
     * @return Request\Request
     */
    public abstract function getRequest();

    /**
     * @param  string $target
     * @return bool
     */
    public static function isExternalUrl($target)
    {
        if (!is_string($target)) {
            return false;
        }

        return strpos($target, 'http://') === 0 ||
               strpos($target, 'https://') === 0 ||
               strpos($target, '//') === 0;
    }

    /**
     * Replace %2F with / for readability
     *
     * @param  string $txt
     * @return string
     */
    public function urlEncode($txt)
    {
        return str_replace('%2F', '/', urlencode($txt));
    }

    /**
     * @return string
     */
    public function backUrl()
    {
        $current = $this->getRequest()->getCurrentPath();

        if ($this->getRequest() instanceof Request\Http) {
            $query = $this->getRequest()->getQueryString();
            return $this->urlEncode(empty($query) ? $current : $current . '?' . $query);
        }

        return $this->urlEncode($current);
    }

    /**
     * Returns path to the specified resource,
     * including base url (derived from current Request).
     *
     * For HTTP based request it should return path relative to the
     * server root, for example: file.jpg -> /blog/file.jpg.
     * For offline based request, it should return full URL, for example:
     * file.jpg => http://www.somewebsite.com/blog/file.jpg
     *
     * If $target is an array, it attempts to assemble the URL
     * with Router from current Request, using $route.
     *
     * @usage $this->urlTo('site/action/show/15')
     * @usage $this->urlTo([ 'action' => 'show', 'id' => 15 ], 'default')
     * @usage $this->urlTo([ 'action' => 'show', 'id' => 15, 'route' => 'default' ])
     *
     * @param  array|string $target
     * @param  bool $fullUrl Use fully qualified URL or local
     * @return string URL to the resource
     */
    public function urlTo($target, $fullUrl = false)
    {
        if ($target instanceof Uri) {
            return $target;
        }

        if (is_object($target) && method_exists($target, 'toUrl')) {
            $target = $target->toUrl();
        }

        if (self::isExternalUrl($target)) {
            return $target;
        }

        if (is_array($target)) {
            $options['name'] = isset($target['route']) ? $target['route'] : 'default';
            $options['query'] = isset($target['query']) ? $target['query'] : null;
            $options['fragment'] = isset($target['fragment']) ? $target['fragment'] : null;
            $options['force_canonical'] = $fullUrl;

            $target = $this->getRequest()->getRouter()->assemble($target, $options);
            if (!$fullUrl || self::isExternalUrl($target)) {
                return $target;
            }
        }

        $file = trim($target, '/');
        $base = $fullUrl ? $this->getRequest()->getFullUrl() : $this->getRequest()->getBaseUrl();
        return rtrim($base, '/') . '/' . str_replace(' ', '%20', $file);
    }
}
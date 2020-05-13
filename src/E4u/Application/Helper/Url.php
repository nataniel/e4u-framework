<?php
namespace E4u\Application\Helper;

use E4u\Request;
use Laminas\Uri\Uri;

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
        $current = $this->currentUrl();
        return $this->urlEncode($current);
    }

    /**
     * @return string
     */
    public function currentUrl()
    {
        $current = $this->getRequest()->getCurrentPath();
        if (!$this->getRequest() instanceof Request\Http) {
            return $current;
        }

        $query = $this->getRequest()->mergeQuery([ 'route' => null, ]);
        return empty($query) ? $current : $current . '?' . $query;
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

    /**
     * @param  array $array
     * @return string
     */
    public function buildQuery($array)
    {
        $query = http_build_query(array_filter($array, function ($x) {
            return !is_null($x);
        }));

        $query = str_replace('%2C', ',', $query);
        $query = str_replace('%5B', '[', $query);
        $query = str_replace('%5D', ']', $query);
        $query = str_replace('%28', '(', $query);
        $query = str_replace('%29', ')', $query);
        return str_replace('%2F', '/', $query);
    }
}

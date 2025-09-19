<?php
namespace E4u\Application\Helper;

use E4u\Request;
use Laminas\Uri\Uri;

trait Url
{
    /**
     * Class importing this method must declare getRequest() method
     */
    public abstract function getRequest(): Request\Request;

    public static function isExternalUrl(mixed $target): bool
    {
        if (!is_string($target)) {
            return false;
        }

        return str_starts_with($target, 'http://') ||
            str_starts_with($target, 'https://') ||
            str_starts_with($target, '//');
    }

    /**
     * Replace %2F with / for readability
     */
    public function urlEncode(string $txt): string
    {
        return str_replace('%2F', '/', urlencode($txt));
    }

    public function backUrl(): string
    {
        $current = $this->currentUrl();
        return $this->urlEncode($current);
    }

    public function currentUrl(): string
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
     */
    public function urlTo(array|string|Uri $target, bool $fullUrl = false): string
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
            $options['name'] = $target['route'] ?? 'default';
            $options['query'] = $target['query'] ?? null;
            $options['fragment'] = $target['fragment'] ?? null;
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

    public function buildQuery(array $array): string
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

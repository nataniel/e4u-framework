<?php
namespace E4u\Application\View;

use E4u\Application\View,
    E4u\Application\Helper,
    E4u\Common\File\Image,
    E4u\Common\Collection\Paginable;
use Laminas\View\Helper as LaminasHelper;

/**
 * Class Html
 * @package E4u\Application\View
 *
 * @method Helper\GaduGadu|string gg($gg, $description = null)
 * @method Helper\Breadcrumbs|string bc($crumbs, $options = [])
 * @method Helper\Flash|string flash()
 * @method Helper\Pagination|string pagination(Paginable $collection, $options = [])
 * @method Helper\Breadcrumbs|string breadcrumbs($crumbs, $options = [])
 * @method Helper\Flaticon|string icon($icon, $title = null)
 * @method Helper\BackUrl|string back()
 * @method LaminasHelper\Doctype|string doctype($doctype = null)
 */
class Html extends View
{
    protected $_viewSuffix = '.html';
    protected $_externalTarget = '_blank';
    protected $_externalClass = 'external';
    protected $_mailToClass = 'mailTo';

    protected $_title = null;
    protected $_description = null;
    protected $_keywords = null;
    protected $_metaProperties = [];
    protected $_canonicalUrl;

    /** @var Helper\ViewHelper[] */
    protected $_helpers = [
        'gg'          => Helper\GaduGadu::class,
        'bc'          => Helper\Breadcrumbs::class,
        'flash'       => Helper\Flash::class,
        'pagination'  => Helper\Pagination::class,
        'breadcrumbs' => Helper\Breadcrumbs::class,
        'icon'        => Helper\Flaticon::class,
        'back'        => Helper\BackUrl::class,
    ];

    public function __construct()
    {
        $this->doctype()->setDoctype(LaminasHelper\Doctype::HTML5);
        $this->registerHelpers();
    }

    protected function registerHelpers()
    {
        foreach ($this->_helpers as $key => $helper) {
            /** @var Helper\ViewHelper $x */
            $x = new $helper();
            $x->setView($this);
            $this->plugins()->setService($key, $x);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_description ?: $this->get('description');
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->_keywords ?: $this->get('keywords');
    }

    /**
     * @param  string $description
     * @return Html Current instance
     */
    public function setDescription($description)
    {
        $this->_description = $description;
        return $this;
    }

    /**
     * @param  string $keywords
     * @return Html Current instance
     */
    public function setKeywords($keywords)
    {
        $this->_keywords = $keywords;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getMetaProperties()
    {
        return array_filter($this->_metaProperties ?: $this->get('metaProperties') ?: [], 'strlen');
    }

    /**
     * @return string
     */
    public function showMetaProperties()
    {
        $html = '';
        foreach ($this->getMetaProperties() as $key => $value) {
            $html .= $this->tag('meta', [ 'property' => $key, 'content' => $value, ]) . "\n";
        }

        return $html;
    }

    /**
     * @return mixed
     */
    public function getCanonicalUrl()
    {
        return $this->_canonicalUrl ?: $this->get('canonicalUrl');
    }

    /**
     * @param  mixed $url
     * @return $this
     */
    public function setCanonicalUrl($url)
    {
        $this->_canonicalUrl = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function showCanonicalUrl()
    {
        return $url = $this->getCanonicalUrl()
            ? $this->tag('link', [ 'rel' => 'canonical', 'href' => $this->urlTo($this->getCanonicalUrl(), true) ])
            : '';
    }

    /**
     * @param  string $title
     * @return $this Current instance
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     * @param  string $tag
     * @param  string $title
     * @return null|string
     */
    public function showTitle($tag = 'h1', $title = null)
    {
        if (!is_null($title)) {
            $this->setTitle($title);
        }

        $title = $this->getTitle();
        if (!empty($title)) {
            return $this->tag($tag, $title);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        $title = $this->_title ?: $this->get('title');
        if (is_array($title)) {
            $params = $title;
            $title = array_shift($params);
        }
        else {
            $title = (string)$title;
            $params = [];
        }

        if (empty($title)) {
            $title = ucfirst($this->getActiveController());
        }

        return $this->t($title, $params);
    }

    /**
     * @todo   Cache separated files into one big file?
     * @param  array  $files
     * @param  string $path
     * @return string
     */
    public function stylesheets($files, $path = 'stylesheets')
    {
        $html = ''; $ext = 'css';
        foreach ($files as $name) {

            if (!Helper\Url::isExternalUrl($name)) {

                $filename = "$path/$name.$ext";
                $key = '?v=' . filemtime('public/'.$filename);

            }
            else {

                $filename = "$name.$ext";
                $key = null;

            }

            $html .= $this->tag('link', [
                    'href' => $this->urlTo($filename) . $key,
                    'media' => 'screen, print',
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                ])."\n";
        }

        return $html;
    }

    /**
     * @todo   Cache separated files into one big file?
     * @param  array  $files
     * @param  array  $options
     * @return string
     */
    public function scripts($files, $options = [])
    {
        $html = ''; $ext = 'js';
        if (is_string($options)) {
            $options = [ 'path' => $options ];
        }

        $options = array_merge([
            'path' => 'javascripts',
            'suffix' => $ext,
            'type' => 'text/javascript',
            'exec' => null,
        ], $options);

        foreach ($files as $name) {

            if ((0 !== strpos($name, 'http://')) && (0 !== strpos($name, '//'))) {

                $filename = "{$options['path']}/$name.{$options['suffix']}";
                $key = '?v=' . filemtime('public/'.$filename);

            }
            else {

                $filename = "$name.{$options['suffix']}";
                $key = null;

            }

            $html .= $this->tag('script', [
                'type' => $options['type'],
                'src' => $this->urlTo($filename) . $key,
                $options['exec'] => $options['exec'],
            ], '') . "\n";
        }

        return $html;
    }

    /**
     * Generate expected filename for controller-specific stylesheet
     * @return string
     */
    public function controllerCSS()
    {
        $module = $this->getActiveModule();
        $file = empty($module)
            ? $this->getActiveController()
            : $module . '/' . $this->getActiveController();

        return $file;
    }

    /**
     * Generate ID for controller-specific HTML element
     * @return string
     */
    public function controllerID()
    {
        $module = $this->getActiveModule();
        $class = empty($module)
            ? $this->getActiveController()
            : $module . ucfirst($this->getActiveController());
        return 'controller-' . $class;
    }

    /**
     * Generate ID for action-specific HTML element
     * @return string
     */
    public function actionID()
    {
        return 'action-'.$this->getAction();
    }

    /**
     * Creates HTML anchor: <a href="mailto:$email" ...>$caption</a>
     *
     * @param  string $email
     * @param  string $caption  (defaults to $email)
     * @param  array  $attributes
     * @return string
     */
    public function mailTo($email, $caption = null, $attributes = [])
    {
        if (empty($email)) {
            return '';
        }

        if (is_array($caption)) {
            $caption = null;
            $attributes = $caption;
        }

        if (is_null($caption)) {
            $caption = $email;
        }

        $attributes['href'] = 'mailto:'.$email;
        $attributes['class'] = trim(@$attributes['class'] . ' ' . $this->_mailToClass);
        return $this->tag('a', $attributes, $caption);
    }

    /**
     * Creates HTML anchor: <a href="tel:$phone" ...>$caption</a>
     *
     * @param  string $phone
     * @param  string $caption  (defaults to $phone)
     * @param  array  $attributes
     * @return string
     */
    public function telTo($phone, $caption = null, $attributes = [])
    {
        if (empty($phone)) {
            return '';
        }

        if (is_array($caption)) {
            $caption = null;
            $attributes = $caption;
        }

        if (is_null($caption)) {
            $caption = $phone;
        }

        $attributes['href'] = 'tel:'.preg_replace('/[\s\-.]/', '', $phone);
        return $this->tag('a', $attributes, $caption);
    }

    /**
     * @param  string $link
     * @param  array $attributes
     * @param  string|array $target Target URL or URL specification
     * @return string
     */
    public function linkBackOr($link, $target, $attributes = [])
    {
        if ($back = $this->getRequest()->getQuery('back')) {
            $target = $back;
        }

        return $this->linkTo($link, $target, $attributes);
    }

    /**
     * Creates HTML anchor: <a href="$target" ...>$link</a>
     *
     * @see \E4u\Application\Helper\Url
     * @param  string $caption Linked text
     * @param  string|array $target Target URL or URL specification
     * @param  array  $attributes HTML attributes of the <a ...>  tag
     * @return string HTML string
     */
    public function linkTo($caption, $target, $attributes = [])
    {
        if (is_null($target)) {
            return $caption;
        }

        if (empty($caption)) {
            return '';
        }

        if (empty($attributes['class'])) {
            $attributes['class'] = '';
        }

        if (strpos($caption, '<img ') === 0) {
            $attributes['class'] = trim($attributes['class'].' image');
        }

        if (Helper\Url::isExternalUrl($target)) {
            $attributes['target'] = $this->_externalTarget;
            $attributes['class'] = trim($attributes['class'] . ' ' . $this->_externalClass);
        }

        if (!empty($attributes['confirm'])) {
            $attributes['onClick'] = sprintf("return confirm(%s)", json_encode($attributes['confirm']));
            unset($attributes['confirm']);
        }

        $back = '';
        if (!empty($attributes['back']) && $attributes['back']) {
            $back = is_bool($attributes['back'])
                ? $this->backUrl()
                : $attributes['back'];
            unset($attributes['back']);
        }

        $attributes['href'] = $this->addUrlParam($this->urlTo($target), 'back', $back);
        return $this->tag('a', $attributes, $caption);
    }

    /**
     * @param  string $url
     * @param  string $name
     * @param  string $value
     * @return string
     */
    private function addUrlParam($url, $name, $value)
    {
        if (empty($value)) {
            return trim($url, '&?');
        }

        $url = strtok($url, '#');
        $anchor = strtok('');
        $separator = strpos($url, '?') === false
            ? '?'
            : '&';
        return $url . $separator . $name . '=' . $value . ($anchor ? '#' . $anchor : '');
    }

    /**
     * Creates HTML button: <button>$link</button> within
     * <form action="$target" ...>...</form>
     *
     * @see \E4u\Application\Helper\Url
     * @param  string $link Linked text
     * @param  string|array $target Target URL or URL specification
     * @param  array  $attributes HTML attributes of the <button ...>  tag
     * @return string HTML string
     */
    public function buttonTo($link, $target, $attributes = [])
    {
        $formAttributes = [ 'action' => $this->urlTo($target), 'method' => 'post', 'class' => 'buttonTo' ];
        if (!empty($attributes['id'])) {
            $formAttributes['id'] = $attributes['id'];
            unset($attributes['id']);
        }

        if (!empty($attributes['class'])) {
            $formAttributes['class'] .= ' '.$attributes['class'];
            unset($attributes['class']);
        }

        if (!empty($attributes['target'])) {
            $formAttributes['target'] = $attributes['target'];
            unset($attributes['target']);
        }

        if (!empty($attributes['confirm'])) {
            $formAttributes['onSubmit'] = sprintf("return confirm(%s)", json_encode($attributes['confirm']));
            unset($attributes['confirm']);
        }

        $button = $this->tag('button', $attributes, $link);
        return $this->tag('form', $formAttributes, $button);
    }

    /**
     * @param  string $tag
     * @param  string[] $attributes
     * @param  string $content
     * @return string
     */
    public function tag($tag, $attributes = null, $content = null)
    {
        return \E4u\Common\Html::tag($tag, $attributes, $content);
    }

    /**
     * @deprecated use Image#getThumbnail instead
     * @param  string|Image $file
     * @param  int    $maxWidth
     * @param  int    $maxHeight
     * @param  string $alt
     * @param  array  $attributes
     * @return string
     */
    public function thumbnail($file, $maxWidth, $maxHeight, $alt, $attributes = [])
    {
        if (is_null($file)) {
            return null;
        }
        elseif (is_string($file)) {
            $file = new Image($file);
        }

        if ($file instanceof Image) {
            return $this->image($file->getThumbnail($maxWidth, $maxHeight), $alt, $attributes);
        }

        return null;
    }

    /**
     * @param  mixed $file
     * @param  string $alt
     * @param  array  $attributes
     * @return string
     */
    public function image($file, $alt, $attributes = [])
    {
        if (is_null($file)) {
            return null;
        }

        if (is_array($alt)) {
            $attributes = $alt;
        }
        elseif (is_string($alt)) {
            $attributes['alt'] = $alt;
        }

        if (!isset($attributes['width']) && is_object($file) && method_exists($file, 'getWidth')) {
            $attributes['width'] = $file->getWidth();
        }

        if (!isset($attributes['height']) && is_object($file) && method_exists($file, 'getHeight')) {
            $attributes['height'] = $file->getHeight();
        }

        $attributes['src'] = $this->urlTo($file);
        if (isset($attributes['href'])) {
            $url = $attributes['href'];
            unset($attributes['href']);
            return $this->linkTo($this->tag('img', $attributes), $url, [ 'title' => $attributes['alt'] ]);
        }

        return $this->tag('img', $attributes);
    }
}
<?php
namespace E4u\Application\View;

use E4u\Application\View,
    E4u\Application\Helper,
    E4u\Common\File\Image,
    E4u\Common\Collection\Paginable;
use Laminas\Uri\Uri;
use Laminas\View\Helper as LaminasHelper;

/**
 * Class Html
 * @package E4u\Application\View
 *
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
    protected string
        $_viewSuffix = '.html',
        $_externalTarget = '_blank',
        $_externalClass = 'external',
        $_mailToClass = 'mailTo';

    protected ?string $_title = null;
    protected ?string $_description = null;
    protected ?string $_keywords = null;
    protected array $_metaProperties = [];
    protected ?string $_canonicalUrl;

    /** @var Helper\ViewHelper[] */
    protected array $_helpers = [
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

    protected function registerHelpers(): void
    {
        foreach ($this->_helpers as $key => $helper) {
            $x = new $helper();
            $x->setView($this);
            $this->plugins()->setService($key, $x);
        }
    }

    public function getDescription(): ?string
    {
        return $this->_description ?: $this->get('description');
    }

    public function getKeywords(): ?string
    {
        return $this->_keywords ?: $this->get('keywords');
    }

    public function setDescription(?string $description): static
    {
        $this->_description = $description;
        return $this;
    }

    public function setKeywords(?string $keywords): static
    {
        $this->_keywords = $keywords;
        return $this;
    }

    public function getMetaProperties(): array
    {
        return array_filter($this->_metaProperties ?: $this->get('metaProperties') ?: [], 'strlen');
    }

    public function showMetaProperties(): string
    {
        $html = '';
        foreach ($this->getMetaProperties() as $key => $value) {
            $html .= $this->tag('meta', [ 'property' => $key, 'content' => $value, ]) . "\n";
        }

        return $html;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->_canonicalUrl ?: $this->get('canonicalUrl');
    }

    public function setCanonicalUrl(?string $url): static
    {
        $this->_canonicalUrl = $url;
        return $this;
    }

    public function showCanonicalUrl(): string
    {
        return $url = $this->getCanonicalUrl()
            ? $this->tag('link', [ 'rel' => 'canonical', 'href' => $this->urlTo($this->getCanonicalUrl(), true) ])
            : '';
    }

    public function setTitle(?string $title): static
    {
        $this->_title = $title;
        return $this;
    }

    public function showTitle(string $tag = 'h1', ?string $title = null): string
    {
        if (!is_null($title)) {
            $this->setTitle($title);
        }

        $title = $this->getTitle();
        if (!empty($title)) {
            return $this->tag($tag, $title);
        }

        return '';
    }

    public function getTitle(): string
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
     */
    public function stylesheets(array $files, string $path = 'stylesheets'): string
    {
        $html = ''; $ext = 'css';
        foreach ($files as $name) {

            if (!self::isExternalUrl($name)) {
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
     */
    public function scripts(array $files, array $options = []): string
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

            if (!self::isExternalUrl($name)) {
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
     */
    public function controllerCSS(): string
    {
        $module = $this->getActiveModule();
        return empty($module)
            ? $this->getActiveController()
            : $module . '/' . $this->getActiveController();
    }

    /**
     * Generate ID for controller-specific HTML element
     */
    public function controllerID(): string
    {
        $module = $this->getActiveModule();
        $class = empty($module)
            ? $this->getActiveController()
            : $module . ucfirst($this->getActiveController());
        return 'controller-' . $class;
    }

    /**
     * Generate ID for action-specific HTML element
     */
    public function actionID(): string
    {
        return 'action-'.$this->getAction();
    }

    /**
     * Creates HTML anchor: <a href="mailto:$email" ...>$caption</a>
     */
    public function mailTo(string $email, null|string|array $caption = null, array $attributes = []): string
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
     */
    public function telTo(string $phone, null|string|array $caption = null, array $attributes = []): string
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

    public function linkBackOr(string $link, string|array|Uri $target, array $attributes = []): string
    {
        if ($back = $this->getRequest()->getQuery('back')) {
            $target = $back;
        }

        return $this->linkTo($link, $target, $attributes);
    }

    /**
     * Creates HTML anchor: <a href="$target" ...>$link</a>
     * @see \E4u\Application\Helper\Url
     */
    public function linkTo(string $caption, null|string|array|Uri $target, array $attributes = []): string
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

        if (str_starts_with($caption, '<img ')) {
            $attributes['class'] = trim($attributes['class'].' image');
        }

        if (self::isExternalUrl($target)) {
            $attributes['target'] = $this->_externalTarget;
            $attributes['class'] = trim($attributes['class'] . ' ' . $this->_externalClass);
        }

        if (!empty($attributes['confirm'])) {
            $attributes['onClick'] = sprintf("return confirm(%s)", json_encode($attributes['confirm']));
            unset($attributes['confirm']);
        }

        $back = '';
        if (!empty($attributes['back'])) {
            $back = is_bool($attributes['back'])
                ? $this->backUrl()
                : $attributes['back'];
            unset($attributes['back']);
        }

        $attributes['href'] = $this->addBackParam($this->urlTo($target), $back);
        return $this->tag('a', $attributes, $caption);
    }

    private function addBackParam(string $url, string $value): string
    {
        if (empty($value)) {
            return trim($url, '&?');
        }

        $url = strtok($url, '#');
        $anchor = strtok('');
        $separator = !str_contains($url, '?')
            ? '?'
            : '&';
        return $url . $separator . 'back=' . $value . ($anchor ? '#' . $anchor : '');
    }

    /**
     * Creates HTML button: <button>$link</button> within
     * <form action="$target" ...>...</form>
     *
     * @see \E4u\Application\Helper\Url
     */
    public function buttonTo(string $link, string|array $target, array $attributes = []): string
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

    public function tag(string $tag, mixed $attributes = null, ?string $content = null): string
    {
        return \E4u\Common\Html::tag($tag, $attributes, $content);
    }

    public function image(mixed $file, mixed $alt, array $attributes = [])
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
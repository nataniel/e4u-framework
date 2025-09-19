<?php
namespace E4u\Application\Helper;

use E4u\Application\View;
use E4u\Common\Html;
use E4u\Exception\LogicException;

class Breadcrumbs extends ViewHelper
{
    /**
     * http://www.paulund.co.uk/breadcrumb-schema-org
        <ol class="breadcrumb">
          <li><a href="#">Home</a></li>
          <li><a href="#">Library</a></li>
          <li class="active">Data</li>
        </ol>
     */
    public function show(array $crumbs, array $options = []): string
    {
        $li = []; $i = 0; $cnt = count($crumbs);
        $view = $this->getView();
        if (!$view instanceof View\Html) {
            throw new LogicException('This view helper needs to be set with View\Html.');
        }
        
        foreach ($crumbs as $caption => $url) {

            $i++;
            if ($i < $cnt) {
                $li[] = Html::tag('li',
                    $view->linkTo($caption, $url, [ 'itemprop' => 'url' ]));
            }
            else {
                $li[] = Html::tag('li', [ 'class' => 'active' ], $caption);
            }
        }

        $attributes = [ 'itemprop' => 'breadcrumb' ];
        return Html::tag('ol', array_merge($attributes, $options), join('', $li));
    }
}
<?php
namespace E4u\Application\Helper;

class Breadcrumbs extends ViewHelper
{
    /**
     * http://www.paulund.co.uk/breadcrumb-schema-org
     * 
     * @param array $crumbs
     * @return string
     *
        <ol class="breadcrumb">
          <li><a href="#">Home</a></li>
          <li><a href="#">Library</a></li>
          <li class="active">Data</li>
        </ol>
     */
    public function show($crumbs, $options = [])
    {
        $li = []; $i = 0; $cnt = count($crumbs);
        foreach ($crumbs as $caption => $url) {

            $i++;
            if ($i < $cnt) {
                $li[] = $this->getView()->tag('li',
                    $this->getView()->linkTo($caption, $url, [ 'itemprop' => 'url' ]));
            }
            else {
                $li[] = $this->getView()->tag('li', [ 'class' => 'active' ], $caption);
            }
        }

        $attributes = [ 'itemprop' => 'breadcrumb' ];
        return $this->getView()->tag('ol', array_merge($attributes, $options), join('', $li));
    }
}
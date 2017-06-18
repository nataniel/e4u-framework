<?php
namespace E4u\Application\Helper;

use E4u\Common\Collection\Paginable;
use E4u\Application\View;

/**
 * Usage:
 * <?= $this->_('pagination', $this->paginator) ?>
 */
class Pagination extends ViewHelper
{
    /**
     * http://v4-alpha.getbootstrap.com/components/pagination/
     * http://getbootstrap.com/components/#pagination
     *
        <ul class="pagination">
        <li class="page-item">
          <a href="#" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
          </a>
        </li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item disabled"><a class="page-link" href="#">&hellip;</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item"><a class="page-link" href="#">4</a></li>
        <li class="page-item"><a class="page-link" href="#">5</a></li>
        <li class="page-item">
          <a class="page-link" href="#" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
        </ul>
     *
     * @param  Paginable $collection
     * @param  array $options
     * @return string
     */
    public function show(Paginable $collection, $options = [])
    {
        if (isset($options['prev'])) {
            $prevCaption =  $options['prev'];
            unset($options['prev']);
        }
        else {
            $prevCaption = '&laquo;';
        }

        if (isset($options['next'])) {
            $nextCaption =  $options['next'];
            unset($options['next']);
        }
        else {
            $nextCaption = '&raquo;';
        }

        if (isset($options['empty'])) {
            $emptyCaption =  $options['empty'];
            unset($options['empty']);
        }
        else {
            $emptyCaption = '&hellip;';
        }

        $li = [
            $this->listElement($collection->prevPage(), $prevCaption),
            $this->listElement(1, [ 'active' => $collection->currentPage() ]),
        ];

        if ($collection->pageCount() > 1) {

            if ($collection->pageCount() > 2) {

                $rangeStart = max($collection->currentPage() - 2, 2);
                $rangeEnd   = min($collection->currentPage() + 2, $collection->pageCount() - 1);
                if ($rangeStart > 2) {
                    $li[] = $this->listElement(null, [ 'caption' => $emptyCaption ]);
                }

                foreach (range($rangeStart, $rangeEnd) as $page) {
                    $li[] = $this->listElement($page, [ 'active' => $collection->currentPage() ]);
                }

                if ($rangeEnd < $collection->pageCount() - 1) {
                    $li[] = $this->listElement(null, [ 'caption' => $emptyCaption ]);
                }
            }

            $li[] = $this->listElement($collection->pageCount(), [ 'active' => $collection->currentPage() ]);
        }

        $li[] = $this->listElement($next = $collection->nextPage(), $nextCaption);
        return $this->getView()->tag('ul', $options, join('', $li));
    }

    protected function listElement($page, $options = [])
    {
        $class = null;
        if (is_string($options)) {

            $caption = $options;

        }
        else {

            $caption = $page;

            if (is_array($options)) {
                if (isset($options['class'])) {
                    $class = $options['class'];
                }

                if (isset($options['caption'])) {
                    $caption = $options['caption'];
                }

                if (isset($options['active']) && ($options['active'] == $page)) {
                    $class = 'active';
                }
            }

        }

        if (is_null($page)) {
            $class = 'disabled';
        }

        return $this->getView()->tag('li', [
            'class' => trim('page-item ' . $class)
        ], $this->linkToPage($page, $caption));
    }

    /**
     * @param  int $page
     * @param  string $caption
     * @return string
     */
    protected function linkToPage($page, $caption = null)
    {
        $href = '?' . $this->getView()->getRequest()->mergeQuery([ 'page' => $page ]);
        return $this->getView()->tag('a', [
            'href' => $href,
            'class' => 'page-link',
            'data-page' => $page,
            'title' => $page ? 'strona '.$page : null
        ], $caption ?: $page).' ';
    }
}
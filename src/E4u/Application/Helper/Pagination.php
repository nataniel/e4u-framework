<?php
namespace E4u\Application\Helper;

use E4u\Common\Collection\Paginable;
use E4u\Common\Html;
use E4u\Exception\LogicException;
use E4u\Request\Http;

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
     */
    public function show(Paginable $collection, array $options = []): string
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
        return Html::tag('ul', $options, join('', $li));
    }

    protected function listElement(?int $page, array|string $options = []): string
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

        return Html::tag('li', [
            'class' => trim('page-item ' . $class)
        ], $this->linkToPage($page, $caption));
    }

    protected function linkToPage(int $page, ?string $caption = null): string
    {
        $request = $this->view->getRequest();
        if (!$request instanceof Http) {
            throw new LogicException('Request must be Http to use BackUrl.');
        }
        
        $href = $this->view->urlTo($request->getCurrentPath())
            . '?' . $request->mergeQuery([ 'page' => $page, 'route' => null ]);

        return Html::tag('a', [
            'href' => $href,
            'class' => 'page-link internal',
            'role' => 'button',
            'data-page' => $page,
            'title' => $page ? 'strona '.$page : null
        ], $caption ?: $page).' ';
    }
}
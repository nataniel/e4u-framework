<?php
namespace E4u\Common\Collection;

interface Paginable extends \Countable, \IteratorAggregate
{
    public function pageCount();
    public function total();
    public function start();
    public function end();
    public function prevPage();
    public function nextPage();
    public function currentPage();
}
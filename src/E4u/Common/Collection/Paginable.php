<?php
namespace E4u\Common\Collection;

interface Paginable extends \Countable, \IteratorAggregate
{
    public function pageCount(): int;
    public function total(): int;
    public function start(): int;
    public function end(): int;
    public function prevPage(): ?int;
    public function nextPage(): ?int;
    public function currentPage();
}
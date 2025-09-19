<?php
namespace E4u\Model;

use Countable, IteratorAggregate, ArrayIterator;
use E4u\Common\Collection\Paginable;
use E4u\Common\Variable;
use E4u\Exception\LogicException;
use Iterator;

class Paginator implements Countable, IteratorAggregate, Paginable
{
    protected mixed $collection;
    
    protected int $perPage = 20;
    
    protected int $currentPage = 1;
    
    private \Iterator|array $_elements;
    private int $_pageCount;
    private int $_totalCount;

    public function __construct($collection, ?int $currentPage = null, ?int $perPage = null)
    {
        $this->setCollection($collection);
        if (!is_null($currentPage)) {
            $this->setCurrentPage($currentPage);
        }

        if (!is_null($perPage)) {
            $this->setPerPage($perPage);
        }
    }

    public function setOptions(array $options): static
    {
        if (isset($options['per_page'])) {
            $this->setPerPage((int)$options['per_page']);
        }

        if (isset($options['current_page'])) {
            $this->setCurrentPage((int)$options['current_page']);
        }

        return $this;
    }
    
    public function setCollection(mixed $collection): static
    {
        if (!is_array($collection)
            && (!$collection instanceof \Traversable)
            && (!$collection instanceof \Countable
             || !method_exists($collection, 'slice'))) {
            throw new LogicException(
                sprintf('Paginator accepts an array, a Traversable or any Countable class with "slice" method defined, %s given.',
                Variable::getType($collection)));
        }
        
        $this->collection = $collection;
        return $this;
    }
    
    public function setPerPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    protected function initialize(): void
    {
        if (isset($this->_elements)) {
            return;
        }
    
        if (method_exists($this->collection, 'slice')) {
            $this->_elements = $this->collection->slice($this->currentOffset(), $this->perPage);
        }
        elseif ($this->collection instanceof \Traversable) {
            /* @TODO -- TRAVERSE to offset */
            $this->collection = iterator_to_array($this->collection);
            $this->_elements = array_slice($this->collection, $this->currentOffset(), $this->perPage);
        }
        elseif (is_array($this->collection)) {
            $this->_elements = array_slice($this->collection, $this->currentOffset(), $this->perPage);
        }
    }

    public function currentOffset(): int
    {
        $page = min($this->currentPage(), $this->pageCount());
        if ($page > 0) {
            return ($this->currentPage-1) * $this->perPage;
        }
        
        return 0;
    }
    
    public function currentPage(): int
    {
        return $this->currentPage;
    }
    
    public function nextPage(): ?int
    {
        if ($this->currentPage + 1 > $this->pageCount()) {
            return null;
        }
        
        return $this->currentPage + 1;
    }
    
    public function prevPage(): ?int
    {
        if ($this->currentPage <= 1) {
            return null;
        }
        
        return $this->currentPage - 1;
    }
    
    public function setCurrentPage(int $currentPage): static
    {
        $this->currentPage = $currentPage;
        unset($this->_elements);
        return $this;
    }
    
    public function getPerPage(): int
    {
        return $this->perPage;
    }
    
    public function toArray(): array
    {
        $this->initialize();
        return $this->_elements instanceof \Iterator
            ? iterator_to_array($this->_elements)
            : $this->_elements;
    }
    
    public function total(): int
    {
        if (!isset($this->_totalCount)) {
            if (!is_array($this->collection)
                && !$this->collection instanceof \Countable) {
                $this->collection = iterator_to_array($this->collection);
            }
            
            $this->_totalCount = count($this->collection);
        }
        
        return $this->_totalCount;
    }
    
    public function start(): int
    {
        return $this->currentOffset() + 1;
    }
    
    public function end(): int
    {
        return ($this->currentPage-1) * $this->perPage + $this->count();
    }
    
    public function pageCount(): int
    {
        if (!isset($this->_pageCount)) {
            $this->_pageCount = ceil($this->total() / $this->perPage);
        }
        
        return $this->_pageCount;
    }

    /**
     * Returns the number of elements on current page.
     * Defined by Countable interface.
     */
    public function count(): int
    {
        $this->initialize();
        return count($this->_elements);
    }

    /**
     * Checks whether the current page is empty.
     */
    public function isEmpty(): bool
    {
        $this->initialize();
        return !$this->_elements;
    }

    /**
     * Gets an iterator for iterating over the elements in the collection.
     * Defined by IteratorAggregate interface.
     */
    public function getIterator(): \Iterator
    {
        $this->initialize();
        if ($this->_elements instanceof \Iterator) {
            return $this->_elements;
        }

        return new ArrayIterator($this->_elements);
    }
}
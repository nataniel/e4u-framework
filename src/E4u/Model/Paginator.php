<?php
namespace E4u\Model;

use Countable, IteratorAggregate, ArrayIterator;
use E4u\Common\Variable;
use E4u\Exception\LogicException;

class Paginator implements Countable, IteratorAggregate
{
    /**
     * @var Collection
     */
    protected $collection;
    
    /**
     * @var int
     */
    protected $perPage;
    
    /**
     * @var int
     */
    protected $currentPage;
    
    private   $_elements;
    private   $_pageCount;
    private   $_totalCount;

    public function __construct($collection, $currentPage = null, $perPage = 20)
    {
        $this->setCollection($collection);
        $this->setCurrentPage($currentPage);
        $this->setPerPage($perPage);
    }

    public function setOptions($options)
    {
        if (isset($options['per_page'])) {
            $this->setPerPage((int)$options['per_page']);
        }

        if (isset($options['current_page'])) {
            $this->setCurrentPage((int)$options['current_page']);
        }

        return $this;
    }
    
    /**
     * @param  mixed $collection
     * @return $this
     */
    public function setCollection($collection)
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
    
    /**
     * @param  int $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = (int)$perPage ?: 20;
        return $this;
    }

    /**
     * @return $this
     */
    protected function initialize()
    {
        if (null === $this->_elements) {
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
        
        return $this;
    }

    /**
     * @return int
     */
    public function currentOffset()
    {
        $page = min($this->currentPage(), $this->pageCount());
        if ($page > 0) {
            return ($this->currentPage-1) * $this->perPage;
        }
        
        return 0;
    }
    
    /**
     * @return int
     */
    public function currentPage()
    {
        return $this->currentPage;
    }
    
    /**
     * @return int
     */
    public function nextPage()
    {
        if ($this->currentPage + 1 > $this->pageCount()) {
            return null;
        }
        
        return $this->currentPage + 1;
    }
    
    /**
     * @return int
     */
    public function prevPage()
    {
        if ($this->currentPage <= 1) {
            return null;
        }
        
        return $this->currentPage - 1;
    }
    
    /**
     * @param  int $currentPage
     * @return $this
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = (int)$currentPage ?: 1;
        $this->_elements = null;
        return $this;
    }
    
    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }
    
    /**
     * @return array
     */
    public function toArray()
    {
        $this->initialize();

        if (is_array($this->_elements)) {
            return $this->_elements;
        }

        if ($this->_elements instanceof \IteratorAggregate) {
            return iterator_to_array($this->_elements->getIterator());
        }

        return $this->_elements->toArray();
    }
    
    /**
     * @return int
     */
    public function total()
    {
        if (null === $this->_totalCount) {
            if (!is_array($this->collection)
                && !$this->collection instanceof \Countable) {
                $this->collection = iterator_to_array($this->collection);
            }
            
            $this->_totalCount = count($this->collection);
        }
        
        return $this->_totalCount;
    }
    
    /**
     * @return int
     */
    public function start()
    {
        return $this->currentOffset() + 1;
    }
    
    /**
     * @return int
     */
    public function end()
    {
        return ($this->currentPage-1) * $this->perPage + $this->count();
    }
    
    /**
     * @return int
     */
    public function pageCount()
    {
        if (null === $this->_pageCount) {
            $this->_pageCount = ceil($this->total() / $this->perPage);
        }
        
        return $this->_pageCount;
    }

    /**
     * Returns the number of elements on current page.
     * Defined by Countable interface.
     *
     * @return integer The number of elements on current page.
     */
    public function count()
    {
        $this->initialize();
        return count($this->_elements);
    }

    /**
     * Checks whether the current page is empty.
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        $this->initialize();
        return !$this->_elements;
    }

    /**
     * Gets an iterator for iterating over the elements in the collection.
     * Defined by IteratorAggregate interface.
     *
     * @return ArrayIterator|\Iterator
     */
    public function getIterator()
    {
        $this->initialize();
        if ($this->_elements instanceof \Iterator) {
            return $this->_elements;
        }

        return new ArrayIterator($this->_elements);
    }
}
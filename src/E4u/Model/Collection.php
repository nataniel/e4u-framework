<?php
namespace E4u\Model;

use Doctrine\ORM\QueryBuilder,
    Doctrine\ORM\Query,
    Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator,
    Countable, IteratorAggregate, ArrayIterator, ArrayAccess;

class Collection implements Countable, IteratorAggregate, ArrayAccess
{
    /** @var Query **/
    protected $query;

    /** @var Paginator **/
    protected $paginator;

    /** @var int */
    private $_count;

    /** @var array */
    private $_result;

    /**
     *
     * @param Query|QueryBuilder|array $input
     */
    public function __construct($input)
    {
        if (is_object($input)) {
            $this->setQuery($input);
        }
        elseif (is_array($input)) {
            $this->_result = $input;
        }
        elseif (is_null($input)) {
            $this->_result = [];
        }
    }

    /**
     * @return Collection
     */
    protected function initialize()
    {
        if (null === $this->_result) {
            $this->_result = $this->getQuery()->getResult();
        }

        return $this;
    }

    /**
     * @param  array $options
     * @return Paginator
     */
    public function getPaginator($options = [])
    {
        if (null === $this->paginator) {
            $this->paginator = new Paginator($this);
        }

        $this->paginator->setOptions($options);
        return $this->paginator;
    }

    /**
     * @return Collection
     */
    public function setOrder()
    {
        # NOT IMPLEMENTED
        return $this;
    }

    /**
     * @param Query|QueryBuilder $query $query
     * @return Collection
     */
    public function setQuery($query)
    {
        if ($query instanceof QueryBuilder) {
            $this->query = $query->getQuery();
        }
        elseif ($query instanceof Query) {
            $this->query = $query;
        }
        else {
            throw new \E4u\Exception\LogicException(
                sprintf('$query must be instance of QueryBuilder or Query, %s given',
                \E4u\Common\Variable::getType($query)));
        }

        return $this;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return Query
     */
    protected function cloneQuery()
    {
        $query = clone $this->query;
        $parameters = clone $this->query->getParameters();

        $query->setParameters($parameters);
        return $query;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $this->initialize();
        return $this->_result;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * @return int
     */
    public function count()
    {
        if (null === $this->_count) {
            if (null === $this->_result) {

                $paginator = new DoctrinePaginator($this->query);
                $this->_count = $paginator->count();

            }
            else {

                $this->_count = count($this->_result);

            }

        }

        return $this->_count;
    }

    /**
     * @param  int $offset
     * @param  int $length
     * @return array|IteratorAggregate
     */
    public function slice($offset, $length = null)
    {
        if (null === $this->_result) {

            $this->getQuery()
                ->setFirstResult($offset)
                ->setMaxResults($length);
            $paginator = new DoctrinePaginator($this->query);
            return $paginator->getIterator();

        }

        return array_slice($this->_result, $offset, $length);
    }

    /**
     * Gets an iterator for iterating over the elements in the collection.
     * Defined by IteratorAggregate interface.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $this->initialize();
        return new ArrayIterator($this->_result);
    }

    /**
     * Implements \ArrayAccess
     */
    public function offsetExists($name)
    {
        $this->initialize();
        return isset($this->_result[$name]);
    }

    public function offsetGet($name)
    {
        $this->initialize();
        return isset($this->_result[$name]) ? $this->_result[$name] : null;
    }

    public function offsetSet($name, $value)
    {
        throw new \E4u\Exception\LogicException("Collection is read only.");
    }

    public function offsetUnset($name)
    {
        throw new \E4u\Exception\LogicException("Collection is read only.");
    }
}
<?php
namespace E4u\Model;

use Doctrine\ORM\QueryBuilder,
    Doctrine\ORM\Query,
    Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator,
    Countable, IteratorAggregate, ArrayIterator, ArrayAccess;
use E4u\Common\Variable;
use E4u\Exception\LogicException;
use ReturnTypeWillChange;

class Collection implements Countable, IteratorAggregate, ArrayAccess
{
    protected Query $query;

    protected Paginator $paginator;

    private int $_count;

    private array $_result;

    public function __construct(Query|QueryBuilder|array|null $input)
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

    protected function initialize(): void
    {
        if (!isset($this->_result)) {
            $this->_result = $this->getQuery()->getResult();
        }
    }

    public function getPaginator(array $options = []): Paginator
    {
        if (!isset($this->paginator)) {
            $this->paginator = new Paginator($this);
        }

        return $this->paginator->setOptions($options);
    }

    /**
     * @return Collection
     */
    public function setOrder(): static
    {
        # NOT IMPLEMENTED
        return $this;
    }

    public function setQuery(Query|QueryBuilder $query): void
    {
        $this->query = $query instanceof QueryBuilder
            ? $query->getQuery()
            : $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    protected function cloneQuery(): Query
    {
        $query = clone $this->query;
        $parameters = clone $this->query->getParameters();

        $query->setParameters($parameters);
        return $query;
    }

    public function toArray(): array
    {
        $this->initialize();
        return $this->_result;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function count(): int
    {
        if (!isset($this->_count)) {
            if (!isset($this->_result)) {

                $paginator = new DoctrinePaginator($this->query, false);
                $this->_count = $paginator->count();

            }
            else {

                $this->_count = count($this->_result);

            }

        }

        return $this->_count;
    }

    public function slice(int $offset, ?int $length = null): array|ArrayIterator
    {
        if (!isset($this->_result)) {

            $this->query
                ->setFirstResult($offset)
                ->setMaxResults($length);

            $fetchJoinCollection = is_null($this->query->getAST()->groupByClause);
            $paginator = new DoctrinePaginator($this->query, $fetchJoinCollection);
            return $paginator->getIterator();

        }

        return array_slice($this->_result, $offset, $length);
    }

    /**
     * Gets an iterator for iterating over the elements in the collection.
     * Defined by IteratorAggregate interface.
     */
    public function getIterator(): ArrayIterator
    {
        $this->initialize();
        return new ArrayIterator($this->_result);
    }

    /**
     * Implements \ArrayAccess
     */
    public function offsetExists($offset): bool
    {
        $this->initialize();
        return isset($this->_result[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        $this->initialize();
        return $this->_result[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new LogicException("Collection is read only.");
    }

    public function offsetUnset($offset): void
    {
        throw new LogicException("Collection is read only.");
    }
}
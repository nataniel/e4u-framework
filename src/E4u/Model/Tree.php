<?php
namespace E4u\Model;

trait Tree
{
    /**
     * #OneToMany(targetEntity="Category", mappedBy="parent", cascade={"persist"})
     * #OrderBy({"position" = "ASC", "name" = "ASC"})
     **/
    # protected $children;

    /**
     * #ManyToOne(targetEntity="Category", inversedBy="children", cascade={"persist"})
     **/
    # protected $parent;

    abstract public function id(): mixed;

    abstract public function isActive(): bool;

    abstract public function __toString();

    abstract public function getParent(): ?static;

    /** @return Tree[] */
    abstract public function getChildren(): array;

    public function hasChildren(): bool
    {
        return count($this->getChildren()) > 0;
    }

    /**
     * @return static[]
     */
    public function getActiveChildren(): array
    {
        $children = [];
        foreach ($this->getChildren() as $child) {
            if ($child->isActive()) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * @return static[]
     */
    public function getAllChildren(bool $onlyActive = true): array
    {
        $children = [];
        foreach ($this->getChildren() as $child) {
            if (!$onlyActive || $child->isActive()) {
                $children[] = $child;
                $children = array_merge($children, $child->getAllChildren($onlyActive));
            }
        }

        return $children;
    }

    public function getRoot(): static
    {
        $root = $this;
        while ($parent = $root->getParent()) {
            $root = $parent;
        }

        return $root;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return is_null($this->getParent());
    }

    public function getPath(?int $limit = null, int $offset = 0): array
    {
        $path = [];
        $root = $this;
        $path[] = $this;

        while ($parent = $root->getParent()) {
            $path[] = $parent;
            $root = $parent;
        }

        return array_slice(array_reverse($path), $offset, $limit);
    }

    public function showPath(string $separator, ?int $limit = null, int $offset = 0): string
    {
        $path = $this->getPath($limit, $offset);

        $pieces = [];
        foreach ($path as $element) {
            $pieces[] = $element->__toString();
        }

        return join($separator, $pieces);
    }

    public function isChildOf(self $target): bool
    {
        $parent = $this->getParent();
        if (empty($parent)) {
            return false;
        }

        if ($parent === $target) {
            return true;
        }

        return $parent->isChildOf($target);
    }
}
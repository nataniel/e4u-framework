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

    /** @return bool */
    abstract public function isActive();

    /** @return string */
    abstract public function __toString();

    /** @return Tree */
    abstract public function getParent();

    /** @return Tree[] */
    abstract public function getChildren();

    /**
     * @return boolean
     */
    public function hasChildren()
    {
        return count($this->getChildren()) > 0;
    }

    /**
     * @return self[]
     */
    public function getActiveChildren()
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
     * @param  boolean $onlyActive
     * @return self[]
     */
    public function getAllChildren($onlyActive = true)
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

    /**
     * @return self
     */
    public function getRoot()
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
    public function isRoot()
    {
        return is_null($this->getParent());
    }

    /**
     * @param  int $limit
     * @param  int $offset
     * @return self[]
     */
    public function getPath($limit = null, $offset = 0)
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

    /**
     * @param  string $separator
     * @param  int $limit
     * @param  int $offset
     * @return string
     */
    public function showPath($separator, $limit = null, $offset = 0)
    {
        $path = $this->getPath($limit, $offset);

        $pieces = [];
        foreach ($path as $element) {
            $pieces[] = $element->__toString();
        }

        return join($separator, $pieces);
    }

    /**
     * @param  Tree $target
     * @return bool
     */
    public function isChildOf($target)
    {
        $parent = $this->getParent();

        if ($parent === $target) {
            return true;
        }

        if (empty($parent)) {
            return false;
        }

        return $parent->isChildOf($target);
    }
}
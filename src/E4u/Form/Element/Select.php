<?php
namespace E4u\Form\Element;

class Select extends Options
{
    protected $optGroups = [];

    /**
     * @param  array $optGroups
     * @return $this
     */
    public function setOptGroups($optGroups)
    {
        $this->optGroups = $optGroups;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptGroups()
    {
        return $this->optGroups;
    }

    /**
     * @param  string $name
     * @param  array $group
     * @return $this
     */
    public function addOptGroup($name, $group)
    {
        $this->optGroups[ $name ] = $group;
        return $this;
    }
}
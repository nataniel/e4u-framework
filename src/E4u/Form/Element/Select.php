<?php
namespace E4u\Form\Element;

class Select extends Options
{
    protected array $optGroups = [];

    public function setOptGroups(array $optGroups): static
    {
        $this->optGroups = $optGroups;
        return $this;
    }

    public function getOptGroups(): array
    {
        return $this->optGroups;
    }

    public function addOptGroup(string $name, array $group): static
    {
        $this->optGroups[ $name ] = $group;
        return $this;
    }
}
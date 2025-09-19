<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

abstract class Options extends Element
{
    protected array $options = [];
    protected array $data = [];

    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    /**
     *
     * @param string[] $options [ value1 => caption1, value2 => caption2, ... ]
     * @param array[] $data [ value1 => dataArray1, value2 => dataArray2, ... ]
     * @return Options
     */
    public function addOptions(array $options, array $data = []): static
    {
        foreach ($options as $value => $caption) {
            $dataForOption = $data[ $value ] ?? [];
            $this->addOption($value, $caption, $dataForOption);
        }

        return $this;
    }

    public function addOption(string $value, string $caption, array $data = []): static
    {
        $this->options[ $value ] = $caption;
        $this->data[ $value ] = $data;
        return $this;
    }

    public function getDataForOption(string $value): array
    {
        return $this->data[ $value ] ?? [];
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

abstract class Options extends Element
{
    protected $options = [];
    protected $data = [];
    protected $default = [];

    /**
     * @param  array $options
     * @return Options
     */
    public function setOptions($options)
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
    public function addOptions($options, $data = [])
    {
        foreach ($options as $value => $caption) {
            $dataForOption = isset($data[$value]) ? $data[$value] : [];
            $this->addOption($value, $caption, $dataForOption);
        }

        return $this;
    }

    /**
     * @param  string   $value
     * @param  string   $caption
     * @param  string[] $data
     * @return Options
     */
    public function addOption($value, $caption, $data = [])
    {
        $this->options[ $value ] = $caption;
        $this->data[ $value ] = $data;
        return $this;
    }

    /**
     * @param  string $value
     * @return string[]
     */
    public function getDataForOption($value)
    {
        return isset($this->data[ $value ])
            ? $this->data[ $value ]
            : [];
    }

    /**
     * @param  array[] $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
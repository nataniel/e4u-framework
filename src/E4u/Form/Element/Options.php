<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

abstract class Options extends Element
{
    protected $options = [];
    protected $data = [];

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
        $this->options[$value] = $caption;
        $this->data[$value] = $data;
        return $this;
    }

    /**
     * @param  string $value
     * @return string[]
     */
    public function getDataForOption($value)
    {
        return isset($this->data[$value])
            ? $this->data[$value]
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

    /**
     * @deprecated use Form\Builder instead
     * @param string $formName
     * @param mixed $value
     * @param string $caption
     * @return string
     */
    public abstract function renderOption($formName, $value, $caption);

    /**
     * <input type="radio" name="game[max_players]" id="game_max_players_1" value="1" selected="selected" />
     * <input type="radio" name="game[max_players]" id="game_max_players_2" value="2" />
     * (...)
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function render($formName)
    {
        $html = '';
        foreach ($this->options as $value => $caption) {
            $html .= $this->renderOption($formName, $value, $caption);
        }

        return $html;
    }

    /**
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @param  string $value
     * @return string
     */
    public function optionId($formName, $value)
    {
        $value = \E4u\Common\StringTools::underscore($value);
        return $this->htmlId($formName).'_'.$value;
    }
}
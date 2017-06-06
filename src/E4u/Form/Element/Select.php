<?php
namespace E4u\Form\Element;

class Select extends Options
{
    protected $cssClass = 'select';
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

    /**
     * <select  name="game[max_players]" id="game_max_players">
     * <option value="1" data-email="one@example.com" selected="selected">User 1</option>
     * <option value="2" data-email="two@example.com">User 2</option>
     * (...)
     * </select>
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function render($formName)
    {
        $options = '';
        foreach ($this->options as $value => $caption) {
            $options .= $this->renderOption($formName, $value, $caption, $this->data[$value]);
        }

        $this->setAttributes([
            'name' => $this->htmlName($formName),
            'id' => $this->htmlId($formName),
        ]);

        return \E4u\Common\Html::tag('select', $this->attributes, $options);
    }

    /**
     * @deprecated use Form\Builder instead
     * @param string $formName
     * @param mixed $value
     * @param string $caption
     * @param array $data
     * @return string
     */
    public function renderOption($formName, $value, $caption, $data = [])
    {
        $attributes = [
            'selected' => $this->getValue() == $value ? 'selected' : null,
            'value' => $value
        ];

        foreach ($data as $key => $dataValue) {
            $attributes['data-'.$key] = $dataValue;
        }

        return \E4u\Common\Html::tag('option', $attributes, $caption);
    }
}
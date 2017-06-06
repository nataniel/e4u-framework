<?php
namespace E4u\Form\Builder;

use E4u\Application\View;
use E4u\Form\Base;

interface BuilderInterface
{
    public function __construct(Base $form, View\Html $view, $options = []);
    public function errors();
    public function start($options = []);
    public function end();

    public function fieldId($name, $value = null);
    public function fieldName($name);

    public function label($name, $showLabels = true);
    public function checkbox($name, $options = []);
    public function textarea($name, $options = []);
    public function file($name, $options = []);
    public function text($name, $options = []);
    public function number($name, $options = []);
    public function password($name, $options = []);
    public function email($name, $options = []);
    public function date($name, $options = []);
    public function select($name, $options = []);
    public function radioGroup($name, $options = []);
    public function button($name, $options = []);
}
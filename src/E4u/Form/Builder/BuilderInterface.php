<?php
namespace E4u\Form\Builder;

use E4u\Application\View;
use E4u\Form\Base;

interface BuilderInterface
{
    public function __construct(Base $form, View\Html $view, array $options = []);
    public function errors(): ?string;
    public function start(array $options = []);
    public function end(): string;

    public function fieldId(string $name, mixed $value = null): string;
    public function fieldName(string $name): string;

    public function label(string $name, bool $showLabels = true): string;
    public function checkbox(string $name, array $options = []): string;
    public function textarea(string $name, array $options = []): string;
    public function file(string $name, array $options = []): string;
    public function text(string $name, array $options = []): string;
    public function number(string $name, array $options = []): string;
    public function password(string $name, array $options = []): string;
    public function email(string $name, array $options = []): string;
    public function date(string $name, array $options = []): string;
    public function select(string $name, array $options = []): string;
    public function radioGroup(string $name, array $options = []): string;
    public function button(string $name, array $options = []): string;
}
<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Layout;

trait TextInputTrait
{
    /**
     * Initialize current input element
     *
     * @param string|null $name Form element name
     * @param array|null $options Options array
     * @return Layout|null
     */
    public function input(?string $name = null, ?array $options = null): ?Layout
    {
        $input = new Layout('input', [
            'id' => $name . '-0-' . self::$uniqueId,
            'name' => $name,
            'type' => $this->getType(),
            'class' => 'text'
        ]);

        $this->container($input);
        $this->inputs[] = $input;

        if (isset($this->label)) {
            $this->label->setAttribute('for', $name . '-0-' . self::$uniqueId);
        }

        return $input;
    }

    /**
     * Set form item default value
     *
     * @param mixed $value Form item default value
     */
    protected function inputValue($value)
    {
        if (isset($value)) {
            $this->input->setAttribute('value', $this->filterValue($value));
        } else {
            $this->input->removeAttribute('value');
        }
    }

    /**
     * @param string $value
     * @return string
     */
    abstract protected function filterValue(string $value): string;

    /**
     * @return string
     */
    abstract protected function getType(): string;
}

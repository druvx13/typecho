<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Virtual field helper class
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Fake extends Element
{
    /**
     * Constructor
     *
     * @param string $name 表单输入项名称
     * @param mixed $value Form default value
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        self::$uniqueId++;

        /** Run custom initialization function */
        $this->init();

        /** Initialize form item */
        $this->input = $this->input($name);

        /** Initialize form value */
        if (null !== $value) {
            $this->value($value);
        }
    }

    /**
     * Initialize current input element
     *
     * @param string|null $name Form element name
     * @param array|null $options Options array
     * @return Layout|null
     */
    public function input(?string $name = null, ?array $options = null): ?Layout
    {
        $input = new Layout('input');
        $this->inputs[] = $input;
        return $input;
    }

    /**
     * Set form item default value
     *
     * @param mixed $value Form item default value
     */
    protected function inputValue($value)
    {
        $this->input->setAttribute('value', $value);
    }
}

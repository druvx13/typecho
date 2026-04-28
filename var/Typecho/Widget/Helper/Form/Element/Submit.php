<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Submit button form element helper class
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Submit extends Element
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
        $this->setAttribute('class', 'typecho-option typecho-option-submit');
        $input = new Layout('button', ['type' => 'submit']);
        $this->container($input);
        $this->inputs[] = $input;

        return $input;
    }

    /**
     * Set form element value
     *
     * @param mixed $value Form element value
     */
    protected function inputValue($value)
    {
        $this->input->html($value ?? 'Submit');
    }
}

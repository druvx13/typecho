<?php

namespace Typecho\Widget\Helper\Form\Element;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Textarea helper class
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Textarea extends Element
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
        $input = new Layout('textarea', ['id' => $name . '-0-' . self::$uniqueId, 'name' => $name]);
        $this->label->setAttribute('for', $name . '-0-' . self::$uniqueId);
        $this->container($input->setClose(false));
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
        $this->input->html(htmlspecialchars($value ?? ''));
    }
}

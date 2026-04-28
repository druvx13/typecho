<?php

namespace Typecho\Widget\Helper\Form;

use Typecho\Widget\Helper\Layout;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Abstract form element class
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
abstract class Element extends Layout
{
    /**
     * 单例Unique ID
     *
     * @access protected
     * @var integer
     */
    protected static int $uniqueId = 0;

    /**
     * 表单元素容器
     *
     * @access public
     * @var Layout
     */
    public Layout $container;

    /**
     * 输入栏
     *
     * @access public
     * @var Layout|null
     */
    public ?Layout $input;

    /**
     * inputs
     *
     * @var array
     * @access public
     */
    public array $inputs = [];

    /**
     * 表单Title
     *
     * @access public
     * @var Layout
     */
    public Layout $label;

    /**
     * 表单验证器
     *
     * @access public
     * @var array
     */
    public array $rules = [];

    /**
     * 表单名称
     *
     * @access public
     * @var string
     */
    public ?string $name;

    /**
     * 表单值
     *
     * @access public
     * @var mixed
     */
    public $value;

    /**
     * 表单描述
     *
     * @access private
     * @var Layout
     */
    protected Layout $description;

    /**
     * 表单消息
     *
     * @access protected
     * @var Layout
     */
    protected Layout $message;

    /**
     * 多行输入
     *
     * @access public
     * @var array()
     */
    protected array $multiline = [];

    /**
     * Constructor
     *
     * @param string|null $name 表单输入项名称
     * @param array|null $options Options array
     * @param mixed $value Form default value
     * @param string|null $label 表单Title
     * @param string|null $description 表单描述
     * @return void
     */
    public function __construct(
        ?string $name = null,
        ?array $options = null,
        $value = null,
        ?string $label = null,
        ?string $description = null
    ) {
        /** 创建html元素,并设置class */
        parent::__construct(
            'ul',
            ['class' => 'typecho-option', 'id' => 'typecho-option-item-' . $name . '-' . self::$uniqueId]
        );

        $this->name = $name;
        self::$uniqueId++;

        /** Run custom initialization function */
        $this->init();

        /** 初始化表单Title */
        if (null !== $label) {
            $this->label($label);
        }

        /** Initialize form item */
        $this->input = $this->input($name, $options);

        /** Initialize form value */
        if (null !== $value) {
            $this->value($value);
        }

        /** 初始化表单描述 */
        if (null !== $description) {
            $this->description($description);
        }
    }

    /**
     * Custom initialization function
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * 创建表单Title
     *
     * @param string $value TitleString
     * @return $this
     */
    public function label(string $value): Element
    {
        /** 创建Title元素 */
        if (empty($this->label)) {
            $this->label = new Layout('label', ['class' => 'typecho-label']);
            $this->container($this->label);
        }

        $this->label->html($value);
        return $this;
    }

    /**
     * 在容器里增加元素
     *
     * @param Layout $item 表单元素
     * @return $this
     */
    public function container(Layout $item): Element
    {
        /** 创建表单容器 */
        if (empty($this->container)) {
            $this->container = new Layout('li');
            $this->addItem($this->container);
        }

        $this->container->addItem($item);
        return $this;
    }

    /**
     * Initialize current input element
     *
     * @param string|null $name Form element name
     * @param array|null $options Options array
     * @return Layout|null
     */
    abstract public function input(?string $name = null, ?array $options = null): ?Layout;

    /**
     * Set form element value
     *
     * @param mixed $value Form element value
     * @return Element
     */
    public function value($value): Element
    {
        $this->value = $value;
        $this->inputValue($value);
        return $this;
    }

    /**
     * 设置描述信息
     *
     * @param string $description 描述信息
     * @return Element
     */
    public function description(string $description): Element
    {
        /** 创建描述元素 */
        if (empty($this->description)) {
            $this->description = new Layout('p', ['class' => 'description']);
            $this->container($this->description);
        }

        $this->description->html($description);
        return $this;
    }

    /**
     * 设置提示信息
     *
     * @param string $message 提示信息
     * @return Element
     */
    public function message(string $message): Element
    {
        if (empty($this->message)) {
            $this->message = new Layout('p', ['class' => 'message error']);
            $this->container($this->message);
        }

        $this->message->html($message);
        return $this;
    }

    /**
     * Multi-line output mode
     *
     * @return Layout
     */
    public function multiline(): Layout
    {
        $item = new Layout('span');
        $this->multiline[] = $item;
        return $item;
    }

    /**
     * Multi-line output mode
     *
     * @return Element
     */
    public function multiMode(): Element
    {
        foreach ($this->multiline as $item) {
            $item->setAttribute('class', 'multiline');
        }
        return $this;
    }

    /**
     * 增加验证器
     *
     * @param mixed ...$rules
     * @return $this
     */
    public function addRule(...$rules): Element
    {
        $this->rules[] = $rules;
        return $this;
    }

    /**
     * 统Mon设置所有输入项的属性值
     *
     * @param string $attributeName
     * @param mixed $attributeValue
     */
    public function setInputsAttribute(string $attributeName, $attributeValue)
    {
        foreach ($this->inputs as $input) {
            $input->setAttribute($attributeName, $attributeValue);
        }
    }

    /**
     * Set form element value
     *
     * @param mixed $value Form element value
     */
    abstract protected function inputValue($value);

    /**
     * filterValue
     *
     * @param string $value
     * @return string
     */
    protected function filterValue(string $value): string
    {
        if (preg_match_all('/[_0-9a-z-]+/i', $value, $matches)) {
            return implode('-', $matches[0]);
        }

        return '';
    }
}

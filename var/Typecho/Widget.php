<?php

namespace Typecho;

use Typecho\Widget\Helper\EmptyClass;
use Typecho\Widget\Request as WidgetRequest;
use Typecho\Widget\Response as WidgetResponse;
use Typecho\Widget\Terminal;

/**
 * TypechoWidget base class
 *
 * @property $sequence
 * @property $length
 * @property-read $request
 * @property-read $response
 * @property-read $parameter
 */
abstract class Widget
{
    /**
     * Widget object pool
     *
     * @var array
     */
    private static array $widgetPool = [];

    /**
     * widgetAlias
     *
     * @var array
     */
    private static array $widgetAlias = [];

    /**
     * Request object
     *
     * @var WidgetRequest
     */
    protected WidgetRequest $request;

    /**
     * Response object
     *
     * @var WidgetResponse
     */
    protected WidgetResponse $response;

    /**
     * Data stack
     *
     * @var array
     */
    protected array $stack = [];

    /**
     * Current queue pointer index, starting from 1
     *
     * @var integer
     */
    protected int $sequence = 0;

    /**
     * Queue length
     *
     * @var integer
     */
    protected int $length = 0;

    /**
     * Config object
     *
     * @var Config
     */
    protected Config $parameter;

    /**
     * Each row in the data stack
     *
     * @var array
     */
    protected array $row = [];

    /**
     * Constructor,Initialize widget
     *
     * @param WidgetRequest $request Request object
     * @param WidgetResponse $response Response object
     * @param mixed $params Parameter list
     */
    public function __construct(WidgetRequest $request, WidgetResponse $response, $params = null)
    {
        // Set internal function objects
        $this->request = $request;
        $this->response = $response;
        $this->parameter = Config::factory($params);

        $this->init();
    }

    /**
     * init method
     */
    protected function init()
    {
    }

    /**
     * widgetAlias
     *
     * @param string $widgetClass
     * @param string $aliasClass
     */
    public static function alias(string $widgetClass, string $aliasClass)
    {
        self::$widgetAlias[$widgetClass] = $aliasClass;
    }

    /**
     * Factory method; adds the class statically to the pool
     *
     * @param class-string $alias Widget alias
     * @param mixed $params Parameters to pass
     * @param mixed $request Frontend parameters
     * @param bool|callable $disableSandboxOrCallback Callback
     * @return Widget
     */
    public static function widget(
        string $alias,
        $params = null,
        $request = null,
        $disableSandboxOrCallback = true
    ): Widget {
        [$className] = explode('@', $alias);
        $key = Common::nativeClassName($alias);

        if (isset(self::$widgetAlias[$className])) {
            $className = self::$widgetAlias[$className];
        }

        $sandbox = false;

        if ($disableSandboxOrCallback === false || is_callable($disableSandboxOrCallback)) {
            $sandbox = true;
            Request::getInstance()->beginSandbox(new Config($request));
            Response::getInstance()->beginSandbox();
        }

        if ($sandbox || !isset(self::$widgetPool[$key])) {
            $requestObject = new WidgetRequest(Request::getInstance(), isset($request) ? new Config($request) : null);
            $responseObject = new WidgetResponse(Request::getInstance(), Response::getInstance());

            try {
                $widget = new $className($requestObject, $responseObject, $params);
                $widget->execute();

                if ($sandbox && is_callable($disableSandboxOrCallback)) {
                    call_user_func($disableSandboxOrCallback, $widget);
                }
            } catch (Terminal $e) {
                $widget = $widget ?? null;
            } finally {
                if ($sandbox) {
                    Response::getInstance()->endSandbox();
                    Request::getInstance()->endSandbox();

                    return $widget;
                }
            }

            self::$widgetPool[$key] = $widget;
        }

        return self::$widgetPool[$key];
    }

    /**
     * alloc widget instance
     *
     * @param mixed $params
     * @param mixed $request
     * @param bool|callable $disableSandboxOrCallback
     * @return $this
     */
    public static function alloc($params = null, $request = null, $disableSandboxOrCallback = true): Widget
    {
        return self::widget(static::class, $params, $request, $disableSandboxOrCallback);
    }

    /**
     * alloc widget instance with alias
     *
     * @param string|null $alias
     * @param mixed $params
     * @param mixed $request
     * @param bool|callable $disableSandboxOrCallback
     * @return $this
     */
    public static function allocWithAlias(
        ?string $alias,
        $params = null,
        $request = null,
        $disableSandboxOrCallback = true
    ): Widget {
        return self::widget(
            static::class . (isset($alias) ? '@' . $alias : ''),
            $params,
            $request,
            $disableSandboxOrCallback
        );
    }

    /**
     * Release widget
     *
     * @param string $alias Widget name
     * @deprecated alias for destroy
     */
    public static function destory(string $alias)
    {
        self::destroy($alias);
    }

    /**
     * Release widget
     *
     * @param string|null $alias Widget name
     */
    public static function destroy(?string $alias = null)
    {
        if (Common::nativeClassName(static::class) == 'Typecho_Widget') {
            if (isset($alias)) {
                unset(self::$widgetPool[$alias]);
            } else {
                self::$widgetPool = [];
            }
        } else {
            $alias = static::class . (isset($alias) ? '@' . $alias : '');
            unset(self::$widgetPool[$alias]);
        }
    }

    /**
     * execute function.
     */
    public function execute()
    {
    }

    /**
     * Trigger post event
     *
     * @param boolean $condition Trigger condition
     *
     * @return $this|EmptyClass
     */
    public function on(bool $condition)
    {
        if ($condition) {
            return $this;
        } else {
            return new EmptyClass();
        }
    }

    /**
     * Assign the class itself
     *
     * @param mixed $variable Variable name
     * @return $this
     */
    public function to(&$variable): Widget
    {
        return $variable = $this;
    }

    /**
     * Render by template
     *
     * @param string $template Template
     * @return string
     */
    public function template(string $template): string
    {
        return preg_replace_callback(
            "/\{([_a-z0-9]+)\}/i",
            function (array $matches) {
                return $this->{$matches[1]};
            },
            $template
        );
    }

    /**
     * Format and parse all data in the stack
     *
     * @param string $template Template
     */
    public function parse(string $template)
    {
        while ($this->next()) {
            echo $this->template($template);
        }
    }

    /**
     * @param string|array $column
     * @return array|mixed|null
     */
    public function toColumn($column)
    {
        if (is_array($column)) {
            $item = [];
            foreach ($column as $key) {
                $item[$key] = $this->{$key};
            }

            return $item;
        } else {
            return $this->{$column};
        }
    }

    /**
     * @param string|array $column
     * @return array
     */
    public function toArray($column): array
    {
        $result = [];

        while ($this->next()) {
            $result[] = $this->toColumn($column);
        }

        return $result;
    }

    /**
     * Return each row value in the stack
     *
     * @return mixed
     */
    public function next()
    {
        $key = key($this->stack);

        if ($key !== null && isset($this->stack[$key])) {
            $this->row = current($this->stack);
            next($this->stack);
            $this->sequence++;
        } else {
            reset($this->stack);
            $this->sequence = 0;
            return false;
        }

        return $this->row;
    }

    /**
     * Push each row value onto the stack
     *
     * @param array $value Row values
     * @return mixed
     */
    public function push(array $value)
    {
        // Set row data in order
        $this->row = $value;
        $this->length++;

        $this->stack[] = $value;
        return $value;
    }

    /**
     * Push all row values onto the stack
     *
     * @param array $values All row values
     */
    public function pushAll(array $values)
    {
        foreach ($values as $value) {
            $this->push($value);
        }
    }

    /**
     * Output based on remainder
     *
     * @param mixed ...$args
     */
    public function alt(...$args)
    {
        $this->altBy($this->sequence, ...$args);
    }

    /**
     * Output based on remainder
     *
     * @param int $current
     * @param mixed ...$args
     */
    public function altBy(int $current, ...$args)
    {
        $num = count($args);
        $split = $current % $num;
        echo $args[(0 == $split ? $num : $split) - 1];
    }

    /**
     * Return whether stack is empty
     *
     * @return boolean
     */
    public function have(): bool
    {
        return !empty($this->stack);
    }

    /**
     * Magic function for hooking other functions
     *
     * @param string $name Function name
     * @param array $args Function arguments
     */
    public function __call(string $name, array $args)
    {
        $method = 'call' . ucfirst($name);
        self::pluginHandle()->trigger($plugged)->call($method, $this, $args);

        if (!$plugged) {
            echo $this->{$name};
        }
    }

    /**
     * Get object plugin handle
     *
     * @return Plugin
     */
    public static function pluginHandle(): Plugin
    {
        return Plugin::factory(static::class);
    }

    /**
     * Magic function for accessing internal variables
     *
     * @param string $name Variable name
     * @return mixed
     */
    public function __get(string $name)
    {
        $method = '___' . $name;
        $key = '#' . $name;

        if (array_key_exists($key, $this->row)) {
            return $this->row[$key];
        } elseif (method_exists($this, $method)) {
            $this->row[$key] = $this->$method();
            return $this->row[$key];
        } elseif (array_key_exists($name, $this->row)) {
            return $this->row[$name];
        } else {
            $return = self::pluginHandle()->trigger($plugged)->call($method, $this);
            if ($plugged) {
                return $return;
            }
        }

        return null;
    }

    /**
     * Set the value of each row in the stack
     *
     * @param string $name Key corresponding to the value
     * @param mixed $value Corresponding value
     */
    public function __set(string $name, $value)
    {
        $method = '___' . $name;
        $key = '#' . $name;

        if (isset($this->row[$key]) || method_exists($this, $method)) {
            $this->row[$key] = $value;
        } else {
            $this->row[$name] = $value;
        }
    }

    /**
     * 验证堆栈值是否存在
     *
     * @param string $name
     * @return boolean
     */
    public function __isSet(string $name)
    {
        $method = '___' . $name;
        $key = '#' . $name;

        return isset($this->row[$key]) || method_exists($this, $method) || isset($this->row[$name]);
    }

    /**
     * 输出顺序值
     *
     * @return int
     */
    public function ___sequence(): int
    {
        return $this->sequence;
    }

    /**
     * 输出数据长度
     *
     * @return int
     */
    public function ___length(): int
    {
        return $this->length;
    }

    /**
     * @return WidgetRequest
     */
    public function ___request(): WidgetRequest
    {
        return $this->request;
    }

    /**
     * @return WidgetResponse
     */
    public function ___response(): WidgetResponse
    {
        return $this->response;
    }

    /**
     * @return Config
     */
    public function ___parameter(): Config
    {
        return $this->parameter;
    }
}

<?php

namespace Typecho;

/**
 * Configuration manager
 *
 * @category typecho
 * @package Config
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config extends \stdClass implements \Iterator, \ArrayAccess
{
    /**
     * Current configuration
     *
     * @access private
     * @var array
     */
    private array $currentConfig = [];

    /**
     * 实例化Mon个Current configuration
     *
     * @access public
     * @param array|string|null $config Configuration list
     */
    public function __construct($config = [])
    {
        /** Initialization parameters */
        $this->setDefault($config);
    }

    /**
     * 工厂模式实例化Mon个Current configuration
     *
     * @access public
     *
     * @param array|string|null $config Configuration list
     *
     * @return Config
     */
    public static function factory($config = []): Config
    {
        return new self($config);
    }

    /**
     * 设置默认的配置
     *
     * @access public
     *
     * @param mixed $config Configuration value
     * @param boolean $replace 是否替换已经存在的信息
     *
     * @return void
     */
    public function setDefault($config, bool $replace = false)
    {
        if (empty($config)) {
            return;
        }

        /** Initialization parameters */
        if (is_string($config)) {
            parse_str($config, $params);
        } else {
            $params = $config;
        }

        /** 设置默认参数 */
        foreach ($params as $name => $value) {
            if ($replace || !array_key_exists($name, $this->currentConfig)) {
                $this->currentConfig[$name] = $value;
            }
        }
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->currentConfig);
    }

    /**
     * 重设指针
     *
     * @access public
     * @return void
     */
    public function rewind(): void
    {
        reset($this->currentConfig);
    }

    /**
     * 返回当前值
     *
     * @access public
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->currentConfig);
    }

    /**
     * 指针后移Mon位
     *
     * @access public
     * @return void
     */
    public function next(): void
    {
        next($this->currentConfig);
    }

    /**
     * 获取当前指针
     *
     * @access public
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->currentConfig);
    }

    /**
     * 验证当前值是否到达最后
     *
     * @access public
     * @return boolean
     */
    public function valid(): bool
    {
        return false !== $this->current();
    }

    /**
     * 魔术函数获取Mon个配置值
     *
     * @access public
     * @param string $name Configuration name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->offsetGet($name);
    }

    /**
     * 魔术函数设置Mon个配置值
     *
     * @access public
     * @param string $name Configuration name
     * @param mixed $value 配置值
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * 直接输出默认配置值
     *
     * @access public
     * @param string $name Configuration name
     * @param array|null $args 参数
     * @return void
     */
    public function __call(string $name, ?array $args)
    {
        echo $this->currentConfig[$name];
    }

    /**
     * 判断Current configuration值是否存在
     *
     * @access public
     * @param string $name Configuration name
     * @return boolean
     */
    public function __isSet(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * 魔术方法,打印当前Configuration array
     *
     * @access public
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->currentConfig);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->currentConfig;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->currentConfig[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->currentConfig[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->currentConfig[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->currentConfig[$offset]);
    }
}

<?php

namespace Typecho;

/**
 * Cookie support
 *
 * @author qining
 * @category typecho
 * @package Cookie
 */
class Cookie
{
    /**
     * Prefix
     *
     * @var string
     * @access private
     */
    private static string $prefix = '';

    /**
     * 路径
     *
     * @var string
     * @access private
     */
    private static string $path = '/';

    /**
     * @var string
     * @access private
     */
    private static string $domain = '';

    /**
     * @var bool
     * @access private
     */
    private static bool $secure = false;

    /**
     * @var bool
     * @access private
     */
    private static bool $httponly = false;

    /**
     * 获取Prefix
     *
     * @access public
     * @return string
     */
    public static function getPrefix(): string
    {
        return self::$prefix;
    }

    /**
     * 设置Prefix
     *
     * @param string $url
     *
     * @access public
     * @return void
     */
    public static function setPrefix(string $url)
    {
        self::$prefix = md5($url);
        $parsed = parse_url($url);

        self::$domain = $parsed['host'];
        /** 在路径后面强制加上斜杠 */
        self::$path = empty($parsed['path']) ? '/' : Common::url(null, $parsed['path']);
    }

    /**
     * 获取目录
     *
     * @access public
     * @return string
     */
    public static function getPath(): string
    {
        return self::$path;
    }

    /**
     * @access public
     * @return string
     */
    public static function getDomain(): string
    {
        return self::$domain;
    }

    /**
     * @access public
     * @return bool
     */
    public static function getSecure(): bool
    {
        return self::$secure ?: false;
    }

    /**
     * 设置额外的选项
     *
     * @param array $options
     * @return void
     */
    public static function setOptions(array $options)
    {
        self::$domain = $options['domain'] ?: self::$domain;
        self::$secure = !!$options['secure'];
        self::$httponly = !!$options['httponly'];
    }

    /**
     * 获取指定的COOKIE值
     *
     * @param string $key Specified parameter
     * @param string|null $default 默认的参数
     * @return mixed
     */
    public static function get(string $key, ?string $default = null)
    {
        $key = self::$prefix . $key;
        $value = $_COOKIE[$key] ?? $default;
        return is_array($value) ? $default : $value;
    }

    /**
     * Set specified cookie value
     *
     * @param string $key Specified parameter
     * @param mixed $value Value to set
     * @param integer $expire Expiry time,默认为0,表示随会话时间结束
     */
    public static function set(string $key, $value, int $expire = 0)
    {
        $key = self::$prefix . $key;
        $_COOKIE[$key] = $value;
        Response::getInstance()->setCookie(
            $key,
            $value,
            $expire,
            self::$path,
            self::$domain,
            self::$secure,
            self::$httponly
        );
    }

    /**
     * 删除指定的COOKIE值
     *
     * @param string $key Specified parameter
     */
    public static function delete(string $key)
    {
        $key = self::$prefix . $key;
        if (!isset($_COOKIE[$key])) {
            return;
        }

        Response::getInstance()->setCookie($key, '', -1, self::$path, self::$domain, self::$secure, self::$httponly);
        unset($_COOKIE[$key]);
    }
}

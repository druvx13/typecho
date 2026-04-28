<?php

namespace Typecho;

/**
 * Date processing
 *
 * @author qining
 * @category typecho
 * @package Date
 */
class Date
{
    /**
     * 期望时区偏移
     *
     * @access public
     * @var integer
     */
    public static int $timezoneOffset = 0;

    /**
     * 服务器时区偏移
     *
     * @access public
     * @var integer
     */
    public static int $serverTimezoneOffset = 0;

    /**
     * 当前的服务器Timestamp
     *
     * @access public
     * @var integer
     */
    public static int $serverTimeStamp = 0;

    /**
     * 可以被直接转换的Timestamp
     *
     * @access public
     * @var integer
     */
    public int $timeStamp = 0;

    /**
     * @var string
     */
    public string $year;

    /**
     * @var string
     */
    public string $month;

    /**
     * @var string
     */
    public string $day;

    /**
     * Initialization parameters
     *
     * @param integer|null $time Timestamp
     */
    public function __construct(?int $time = null)
    {
        $this->timeStamp = (null === $time ? self::time() : $time)
            + (self::$timezoneOffset - self::$serverTimezoneOffset);

        $this->year = date('Y', $this->timeStamp);
        $this->month = date('m', $this->timeStamp);
        $this->day = date('d', $this->timeStamp);
    }

    /**
     * 设置当前期望的时区偏移
     *
     * @param integer $offset
     */
    public static function setTimezoneOffset(int $offset)
    {
        self::$timezoneOffset = $offset;
        self::$serverTimezoneOffset = idate('Z');
    }

    /**
     * Get formatted time
     *
     * @param string $format 时间格式
     * @return string
     */
    public function format(string $format): string
    {
        return date($format, $this->timeStamp);
    }

    /**
     * 获取国际化偏移时间
     *
     * @return string
     */
    public function word(): string
    {
        return I18n::dateWord($this->timeStamp, self::time() + (self::$timezoneOffset - self::$serverTimezoneOffset));
    }

    /**
     * 获取GMT时间
     *
     * @deprecated
     * @return int
     */
    public static function gmtTime(): int
    {
        return self::time();
    }

    /**
     * 获取服务器时间
     *
     * @return int
     */
    public static function time(): int
    {
        return self::$serverTimeStamp ?: (self::$serverTimeStamp = time());
    }
}

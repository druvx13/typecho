<?php

namespace Typecho;

use Typecho\I18n\GetTextMulti;

/**
 * Internationalization string translation
 *
 * @package I18n
 */
class I18n
{
    /**
     * Flag indicating whether loaded
     *
     * @access private
     * @var GetTextMulti|null
     */
    private static ?GetTextMulti $loaded = null;

    /**
     * Language file
     *
     * @access private
     * @var string|null
     */
    private static ?string $lang = null;

    /**
     * Translate string
     *
     * @access public
     *
     * @param string $string String to be translated
     *
     * @return string
     */
    public static function translate(string $string): string
    {
        self::init();
        return self::$loaded ? self::$loaded->translate($string) : $string;
    }

    /**
     * Initialize language file
     *
     * @access private
     */
    private static function init()
    {
        /** GetText support */
        if (!isset(self::$loaded) && self::$lang && file_exists(self::$lang)) {
            self::$loaded = new GetTextMulti(self::$lang);
        }
    }

    /**
     * Translation function for plural forms
     *
     * @param string $single Singular form translation
     * @param string $plural Plural form translation
     * @param integer $number Number
     * @return string
     */
    public static function ngettext(string $single, string $plural, int $number): string
    {
        self::init();
        return self::$loaded ? self::$loaded->ngettext($single, $plural, $number) : ($number > 1 ? $plural : $single);
    }

    /**
     * Human-readable time
     *
     * @access public
     *
     * @param int $from Start time
     * @param int $now End time
     *
     * @return string
     */
    public static function dateWord(int $from, int $now): string
    {
        $between = $now - $from;

        /** If within the same day */
        if ($between >= 0 && $between < 86400 && date('d', $from) == date('d', $now)) {
            /** If within the same hour */
            if ($between < 3600) {
                /** If within the same minute */
                if ($between < 60) {
                    if (0 == $between) {
                        return _t('right now');
                    } else {
                        return str_replace('%d', $between, _n('1 second ago', '%d seconds ago', $between));
                    }
                }

                $min = floor($between / 60);
                return str_replace('%d', $min, _n('1 minute ago', '%d minutes ago', $min));
            }

            $hour = floor($between / 3600);
            return str_replace('%d', $hour, _n('1 hour ago', '%d hours ago', $hour));
        }

        /** If yesterday */
        if (
            $between > 0
            && $between < 172800
            && (date('z', $from) + 1 == date('z', $now)                             // Same year case
                || date('z', $from) + 1 == date('L') + 365 + date('z', $now))
        ) {    // Cross-year case
            return _t('yesterday %s', date('H:i', $from));
        }

        /** If within the same week */
        if ($between > 0 && $between < 604800) {
            $day = floor($between / 86400);
            return str_replace('%d', $day, _n('Mon天前', '%d days ago', $day));
        }

        /** If */
        if (date('Y', $from) == date('Y', $now)) {
            return date(_t('n - j'), $from);
        }

        return date(_t('m - d - Y'), $from);
    }

    /**
     * Add language entry
     *
     * @access public
     *
     * @param string $lang Language name
     *
     * @return void
     */
    public static function addLang(string $lang)
    {
        self::$loaded->addFile($lang);
    }

    /**
     * Get language setting
     *
     * @access public
     * @return string
     */
    public static function getLang(): ?string
    {
        return self::$lang;
    }

    /**
     * Set language setting
     *
     * @access public
     *
     * @param string $lang Configuration value
     *
     * @return void
     */
    public static function setLang(string $lang)
    {
        self::$lang = $lang;
    }
}

<?php

namespace Typecho\I18n;

/**
 * Resolves read/write issues caused by multiple MO files
 * A custom file-reading class was implemented for this purpose
 *
 * @author qining
 * @category typecho
 * @package I18n
 */
class GetTextMulti
{
    /**
     * All file read/write handles
     *
     * @access private
     * @var GetText[]
     */
    private array $handlers = [];

    /**
     * Constructor
     *
     * @access public
     * @param string $fileName Language file name
     * @return void
     */
    public function __construct(string $fileName)
    {
        $this->addFile($fileName);
    }

    /**
     * Add a language file
     *
     * @access public
     * @param string $fileName Language file name
     * @return void
     */
    public function addFile(string $fileName)
    {
        $this->handlers[] = new GetText($fileName, true);
    }

    /**
     * Translates a string
     *
     * @access public
     * @param string $string string to be translated
     * @return string translated string (or original, if not found)
     */
    public function translate(string $string): string
    {
        foreach ($this->handlers as $handle) {
            $string = $handle->translate($string, $count);
            if (- 1 != $count) {
                break;
            }
        }

        return $string;
    }

    /**
     * Plural version of gettext
     *
     * @access public
     * @param string $single single
     * @param string $plural plural
     * @param int $number number
     * @return string translated plural form
     */
    public function ngettext(string $single, string $plural, int $number): string
    {
        $count = - 1;

        foreach ($this->handlers as $handler) {
            $string = $handler->ngettext($single, $plural, $number, $count);
            if (- 1 != $count) {
                break;
            }
        }

        return $string;
    }

    /**
     * Close all handles
     *
     * @access public
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->handlers as $handler) {
            /** Explicitly free memory */
            unset($handler);
        }
    }
}

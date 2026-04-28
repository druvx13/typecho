<?php

namespace IXR;

/**
 * IXR error
 *
 * @package IXR
 */
class Error
{
    /**
     * 错误代码
     *
     * @access public
     * @var integer
     */
    public int $code;

    /**
     * Error message
     *
     * @access public
     * @var string|null
     */
    public ?string $message;

    /**
     * Constructor
     *
     * @param integer $code 错误代码
     * @param string $message Error message
     */
    public function __construct(int $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * 获取xml
     *
     * @return string
     */
    public function getXml(): string
    {
        return <<<EOD
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>{$this->code}</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>{$this->message}</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>

EOD;
    }
}

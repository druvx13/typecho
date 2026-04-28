<?php

namespace Typecho\Widget;

use Typecho\Config;
use Typecho\Request as HttpRequest;

/**
 * Widget Request Wrapper
 */
class Request
{
    /**
     * List of supported filters
     *
     * @access private
     * @var string
     */
    private const FILTERS = [
        'int'     => 'intval',
        'integer' => 'intval',
        'encode'  => 'urlencode',
        'html'    => 'htmlspecialchars',
        'search'  => ['\Typecho\Common', 'filterSearchQuery'],
        'xss'     => ['\Typecho\Common', 'removeXSS'],
        'url'     => ['\Typecho\Common', 'safeUrl'],
        'slug'    => ['\Typecho\Common', 'slugName']
    ];

    /**
     * 当前过滤器
     *
     * @access private
     * @var array
     */
    private array $filter = [];

    /**
     * @var HttpRequest
     */
    private HttpRequest $request;

    /**
     * @var Config
     */
    private Config $params;

    /**
     * @param HttpRequest $request
     * @param Config|null $params
     */
    public function __construct(HttpRequest $request, ?Config $params = null)
    {
        $this->request = $request;
        $this->params = $params ?? new Config();
    }

    /**
     * 设置http传递参数
     *
     * @access public
     *
     * @param string $name 指定的参数
     * @param mixed $value 参数值
     *
     * @return void
     */
    public function setParam(string $name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * 设置多个参数
     *
     * @access public
     *
     * @param mixed $params Parameter list
     *
     * @return void
     */
    public function setParams($params)
    {
        $this->params->setDefault($params);
    }

    /**
     * Add filter to request
     *
     * @param string|callable ...$filters
     * @return $this
     */
    public function filter(...$filters): Request
    {
        foreach ($filters as $filter) {
            $this->filter[] = $this->wrapFilter(
                is_string($filter) && isset(self::FILTERS[$filter])
                ? self::FILTERS[$filter] : $filter
            );
        }

        return $this;
    }

    /**
     * Get actual passed parameters (magic)
     *
     * @deprecated ^1.3.0
     * @param string $key Specified parameter
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Check whether parameter exists
     *
     * @deprecated ^1.3.0
     * @param string $key Specified parameter
     * @return boolean
     */
    public function __isset(string $key)
    {
        $this->get($key, null, $exists);
        return $exists;
    }

    /**
     * @param string $key
     * @param null $default
     * @param bool|null $exists detect exists
     * @return mixed
     */
    public function get(string $key, $default = null, ?bool &$exists = true)
    {
        return $this->applyFilter($this->request->proxy($this->params)->get($key, $default, $exists));
    }

    /**
     * @param $key
     * @return array
     */
    public function getArray($key): array
    {
        return $this->applyFilter($this->request->proxy($this->params)->getArray($key));
    }

    /**
     * @param ...$params
     * @return array
     */
    public function from(...$params): array
    {
        return $this->applyFilter(call_user_func_array([$this->request->proxy($this->params), 'from'], $params));
    }

    /**
     * Check whether input meets requirements
     *
     * @param mixed $query Condition
     * @return boolean
     */
    public function is($query): bool
    {
        $result = $this->request->proxy($this->params)->is($query);
        $this->request->endProxy();
        return $result;
    }

    /**
     * @return string
     */
    public function getRequestRoot(): string
    {
        return $this->request->getRequestRoot();
    }

    /**
     * 获取当前完整的请求url
     *
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->request->getRequestUrl();
    }

    /**
     * Get request resource URL
     *
     * @return string|null
     */
    public function getRequestUri(): ?string
    {
        return $this->request->getRequestUri();
    }

    /**
     * Get current path info
     *
     * @return string|null
     */
    public function getPathInfo(): ?string
    {
        return $this->request->getPathInfo();
    }

    /**
     * Get URL prefix
     *
     * @return string|null
     */
    public function getUrlPrefix(): ?string
    {
        return $this->request->getUrlPrefix();
    }

    /**
     * Build a URI with specified parameters from the current URI
     *
     * @param mixed $parameter Specified parameter
     * @return string
     */
    public function makeUriByRequest($parameter = null): string
    {
        return $this->request->makeUriByRequest($parameter);
    }

    /**
     * Get request content type
     *
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->request->getContentType();
    }

    /**
     * Get environment variable
     *
     * @param string $name Environment variable name
     * @param string|null $default
     * @return string|null
     */
    public function getServer(string $name, ?string $default = null): ?string
    {
        return $this->request->getServer($name, $default);
    }

    /**
     * Get IP address
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->request->getIp();
    }

    /**
     * get header value
     *
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getHeader(string $key, ?string $default = null): ?string
    {
        return $this->request->getHeader($key, $default);
    }

    /**
     * Get client
     *
     * @return string
     */
    public function getAgent(): ?string
    {
        return $this->request->getAgent();
    }

    /**
     * Get client
     *
     * @return string|null
     */
    public function getReferer(): ?string
    {
        return $this->request->getReferer();
    }

    /**
     * Check whether request is HTTPS
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->request->isSecure();
    }

    /**
     * Check whether request method is GET
     *
     * @return boolean
     */
    public function isGet(): bool
    {
        return $this->request->isGet();
    }

    /**
     * Check whether request method is POST
     *
     * @return boolean
     */
    public function isPost(): bool
    {
        return $this->request->isPost();
    }

    /**
     * Check whether request method is PUT
     *
     * @return boolean
     */
    public function isPut(): bool
    {
        return $this->request->isPut();
    }

    /**
     * Check whether request is Ajax
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        return $this->request->isAjax();
    }

    /**
     * 判断是否为json
     *
     * @return boolean
     */
    public function isJson(): bool
    {
        return $this->request->isJson();
    }

    /**
     * 应用过滤器
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function applyFilter($value)
    {
        if ($this->filter) {
            foreach ($this->filter as $filter) {
                $value = is_array($value) ? array_map($filter, $value) :
                    call_user_func($filter, $value);
            }

            $this->filter = [];
        }

        $this->request->endProxy();
        return $value;
    }

    /**
     * Wrap a filter to make sure it always receives a string.
     *
     * @param callable $filter
     *
     * @return callable
     */
    private function wrapFilter(callable $filter): callable
    {
        return function ($value) use ($filter) {
            return call_user_func($filter, $value ?? '');
        };
    }
}

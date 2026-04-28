<?php

namespace Widget;

/**
 * Interface callable by Widget\Action
 */
interface ActionInterface
{
    /**
     * 接口需要实现的入口函数
     */
    public function action();
}

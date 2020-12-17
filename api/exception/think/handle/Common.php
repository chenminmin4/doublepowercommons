<?php

namespace fulicommons\api\exception\think\handle;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;

/**
 * 默认错误处理
 */
class Common extends Handle
{
    /**
     * 错误记录
     * @param Exception $exception 错误对象
     */
    public function report(Exception $exception)
    {
        if ($exception instanceof HttpException) {
            log_exception($exception, 'http');
            return;
        }
        log_exception($exception, 'error');
    }
}

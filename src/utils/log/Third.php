<?php


namespace fulicommons\util\log;

/**
 * 请求第三方时的HTTP错误日志
 */
class Third
{
    /**
     * 写入错误日志
     * @param string $url 访问的URL
     * @param string $errmsg 错误描述
     * @param int $errcode 错误代码
     * @param array $post_data 提交的POST数据
     * @param array $headers_data 提交的HEADERS
     * @param string $content 响应内容
     * @param int $http_code 返回的HTTP CODE
     */
    public static function recordLog($url, $errmsg, $errcode, array $post_data, array $headers_data, $content, $http_code)
    {
        $log = "--------------------\n";
        $log .= "错误描述：{$errmsg}\n";
        $log .= "错误代码：{$errcode}\n";
        $log .= "URL：{$url}\n";
        $log .= "HTTP CODE：{$http_code}\n";
        $log .= "POST参数：" . var_export($post_data, true) . "\n";
        $log .= "HEADERS：" . var_export($headers_data, true) . "\n";
        $log .= "**********响应内容**********\n";
        $log .= "{$content}\n";
        $log .= "**********响应内容**********\n";
        $log = str_replace("\n", PHP_EOL, $log);
        Logger::write($log,'fulicommons/third/'.date('Ymd')."/error");
    }

    /**
     * 第三方回调日志
     * @param $param string 回调的信息
     * @param $type  string 类型
     * @param $describe string 描述
     */
    public static function notifyLog($param, $type, $describe)
    {
        $log = "**********'.$describe.'**********\n";
        $log .= "--------------------\n";
        $log .= "回调返回的信息：" . $param . "\n";
        $log .= "时间:" . date('Y-m-d H:i:s') . "\n";
        $log = str_replace("\n", PHP_EOL, $log);
        Logger::write($log,'fulicommons/third/'.date('Ymd')."/".$type);
    }
}

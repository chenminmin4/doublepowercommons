<?php

namespace fulicommons\util\net;

use fulicommons\util\io\File;
use CURLFile;

/**
 * Http工具类
 */
class Http
{

    /**
     * 最后的错误代码
     * @var int
     */
    private $_errCode = 0;

    /**
     * 最后的错误描述
     * @var string
     */
    private $_errMsg = "";

    /**
     * 最后的HTTP状态码
     * @var int
     */
    private $_httpCode = 200;

    /**
     * 最后返回的请求头
     * @var array
     */
    private $_headers = [];

    /**
     * 保存COOKIE的文件夹路径,为null时表示不使用COOKIE
     * @var string
     */
    private $_cookieFileDir;

    /**
     * 设定超时时间，单位秒
     * @var int
     */
    private $_timeOut;

    /**
     * 最后获取的信息列表
     * @var array
     */
    private $_info = [];

    /**
     * CURL重试次数
     * @var int
     */
    private $_retries;

    /**
     * @var string 响应内容
     */
    private $_content = '';

    /**
     * @var string 主体内容
     */
    private $_body = '';

    /**
     * 初始化
     * @param string $cookie_dir 指定保存COOKIE文件的路径，默认null表示不使用COOKIE
     * @param int $time_out 设定超时时间,默认30秒
     * @param int $retries curl重试次数
     */
    public function __construct($cookie_dir = null, $time_out = 30, $retries = 3)
    {
        $this->_cookieFileDir = $cookie_dir;
        $this->_timeOut = $time_out;
        $this->_retries = $retries;
    }

    /**
     * 获取最后的错误代码
     * @return int
     */
    public function lastErrCode()
    {
        return $this->_errCode;
    }

    /**
     * 获取最后的错误描述
     * @return string
     */
    public function lastErrMsg()
    {
        return $this->_errMsg;
    }

    /**
     * 获取最后的信息列表
     * @return array
     */
    public function lastInfo()
    {
        return $this->_info;
    }

    /**
     * 获取最后的HTTP状态码
     * @return int
     */
    public function lastHttpCode()
    {
        return $this->_httpCode;
    }

    /**
     * 返回最后响应主体内容
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * 返回最后的响应头
     * @param string $key 如果传入该值则返回该响应头键值
     * @return mixed
     */
    public function responseHeaders($key = null)
    {
        if (is_null($key)) {
            return $this->_headers;
        } elseif (isset($this->_headers[$key])) {
            return $this->_headers[$key];
        } else {
            return null;
        }
    }

    /**
     * 返回最后的 响应主体内容
     * @return string
     */
    public function responseBody()
    {
        return $this->_body;
    }


    /**
     * 解析响应头成数组
     * @param string $headers 响应头字符串
     * @return array
     */
    private function analysisHeaders($headers)
    {
        $arr_out = [];
        $headers = explode("\r\n", $headers);
        foreach ($headers as $header) {
            $items = explode(": ", $header, 2);
            if (count($items) == 1) {
                if ($items[0] !== '') {
                    $arr_out[] = $items[0];
                }
            } else {
                $arr_out[$items[0]] = $items[1];
            }
        }
        return $arr_out;
    }

    /**
     * 底层发起HTTP请求
     * @param string $url 指定URL
     * @param array $headers 设置请求头
     * @param array $opts 设置CURL选项
     * @param bool $domain_empty 指明该链接是否是无主域链接
     * @return mixed 成功时返回主体内容，失败时返回false
     */
    protected function http($url, array $headers = [], array $opts = [], $domain_empty = false)
    {
        //默认Headers
        $def_headers = [
            // 'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            // 'Accept-Encoding' => 'gzip, deflate, sdch, br',
            // 'Charset' => 'UTF-8',
            // 'Accept-Language' => 'zh-CN,zh;q=0.8',
            // 'Cache-Control' => 'max-age=0',
            // 'Connection' => 'keep-alive',
            // 'Upgrade-Insecure-Requests' => '1',
            // 'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36',
        ];

        //分析URL，同一个一级域名cookie保存在同一个cookie文件
        $url_info = parse_url($url);
        if (isset($url_info['host'])) {
            $def_headers['Host'] = $url_info['host'];
        }

        $headers = array_merge($def_headers, $headers);
        $curl_headers = [];
        foreach ($headers as $key => $value) {
            $curl_headers[] = "{$key}: {$value}";
        }

        //默认配置
        $def_opts = [
            CURLOPT_TIMEOUT => $this->_timeOut,
            CURLOPT_TIMEOUT_MS => $this->_timeOut * 1000,
            CURLOPT_CONNECTTIMEOUT => $this->_timeOut,
            CURLOPT_CONNECTTIMEOUT_MS => $this->_timeOut * 1000,

            CURLOPT_AUTOREFERER => true, //根据 Location: 重定向时，自动设置 header 中的Referer:信息。
            CURLOPT_FILETIME => true, //尝试获取远程文档中的修改时间信息
            CURLOPT_FOLLOWLOCATION => true, //根据服务器返回 HTTP 头中的 "Location: " 重定向

            CURLOPT_SSL_VERIFYPEER => false, //禁止cURL验证对等证书
            CURLOPT_SSL_VERIFYHOST => false, //不检查服务器SSL证书中是否存在一个公用名
            CURLOPT_SSLVERSION => 1, //使用CURL_SSLVERSION_TLSv1，在 SSLv2 和 SSLv3 中有弱点存在。

            CURLOPT_HTTPHEADER => $curl_headers, //设置HEADER

            CURLOPT_URL => $url, //指定访问链接
            CURLOPT_ENCODING => 'gzip, deflate, sdch, br', //指定gzip解释器
            CURLOPT_HEADER => true, //返回响应头
            CURLOPT_RETURNTRANSFER => true, //指定返回结果而不直接输出
        ];

        if (!is_null($this->_cookieFileDir)) {
            //COOKIE全程跟踪

            if ($domain_empty) {
                $zhu_host = $url_info['host'];
            } else {
                $zhu_host = substr($url_info['host'], stripos($url_info['host'], '.') + 1);
            }

            $cookie_file = $this->_cookieFileDir . "{$zhu_host}.cookie";
            new File($cookie_file, true); //自动创建文件

            $pls_opts = [
                CURLOPT_COOKIEJAR => $cookie_file, //调用后保存cookie的文件
                CURLOPT_COOKIEFILE => $cookie_file, //要一起传送的cookie的文件
            ];
            $def_opts = $def_opts + $pls_opts;
        }

        $opts = $opts + $def_opts; //本处由于是数字键名，所以不能使用array_merge

        $curl = new Curl();

        $curl->setoptArray($opts);
        $content = $curl->exec();
        $status = $curl->getinfo();

        $not_ok_http_codes = ['0'];
        while (in_array($status["http_code"], $not_ok_http_codes) && (--$this->_retries > 0)) {
            $content = $curl->exec();
            $status = $curl->getinfo();
        }
        $this->_content = $content;
        $this->_info = $status;
        $headerSize = $curl->getinfo(CURLINFO_HEADER_SIZE);
        $curl_errno = $curl->errno();
        $curl_error = $curl->error();
        $curl->close();
        $http_code = intval($status["http_code"]);
        $this->_httpCode = $http_code;
        if ($http_code == 200) {
            $response_headers = substr($content, 0, $headerSize);
            $this->_headers = $this->analysisHeaders($response_headers);
            if (isset($opts[CURLOPT_FOLLOWLOCATION]) && $opts[CURLOPT_FOLLOWLOCATION] && isset($this->_headers['Location']) && !empty($this->_headers['Location'])) {
                // 是否需要加上跳转来源URL
                if ($this->_headers['Location'] == $url) {
                    //如果Location就是本页面，则直接返回body
                    $body = substr($content, $headerSize);
                    $this->_body = $body;
                    return $body;
                }

                return $this->http($this->_headers['Location'], $headers, $opts, $domain_empty);
            } else {
                $body = substr($content, $headerSize);
                $this->_body = $body;
                return $body;
            }
        } elseif ($http_code == 301 || $http_code == 302) {
            return $this->http($status['redirect_url'], $headers, $opts, $domain_empty);
        } else {
            $response_headers = substr($content, 0, $headerSize);
            $this->_headers = $this->analysisHeaders($response_headers);
            $body = substr($content, $headerSize);
            $this->_body = $body;

            $this->_errCode = $curl_errno;
            // $this->_errMsg = "请求URL时发生错误[{$http_code}]";
            $this->_errMsg = $curl_error;
            return false;
        }
    }

    /**
     * GET请求
     * 如果有GET参数需要附加请自行构建最终URL
     * @param string $url 指定链接
     * @param array $headers 附加的文件头
     * @param array $opts 参数配置数组
     * @param bool $domain_empty 该链接是否是无主域链接
     * @return string 返回响应内容，失败是返回false
     */
    public function get($url, array $headers = [], array $opts = [], $domain_empty = false)
    {
        $add_opts = [
            CURLOPT_HTTPGET => true, //设置 HTTP 的 method 为 GET
            CURLOPT_UPLOAD => false, //GET模式默认不上传文件
        ];
        $opts = $opts + $add_opts;
        return $this->http($url, $headers, $opts, $domain_empty);
    }

    /**
     * 判断上传的东西是否包含文件上传
     * @param $data
     * @return bool
     */
    private function isUploadFile($data)
    {
        if (!is_array($data)) {
            return false;
        }
        foreach ($data as $val) {
            if ($val instanceof CURLFile) {
                return true;
            }
        }
        return false;
    }

    /**
     * POST请求
     * @param string $url 指定链接
     * @param mixed $data 可以是数组(推荐)或者请求字符串。
     * @param array $headers 设定请求头设置
     * @param array $opts 参数配置数组
     * @param bool $domain_empty 该链接是否是无主域链接
     * @return string 返回响应内容，失败是返回false
     */
    public function post($url, $data, array $headers = [], array $opts = [], $domain_empty = false)
    {
        if (is_string($data)) {
            $strPOST = $data;
        } elseif ($this->isUploadFile($data)) {
            $strPOST = $data;  // 需要POST上传文件时直接传递数组
        } else {
            $strPOST = http_build_query($data);
        }
        $add_opts = [
            CURLOPT_POST => true,  // 设置 HTTP 的 method 为 POST
            //CURLOPT_UPLOAD => false,  // POST模式默认准备上传文件
            CURLOPT_POSTFIELDS => $strPOST,  // 要传递的参数
        ];
        $opts = $opts + $add_opts;
        return $this->http($url, $headers, $opts, $domain_empty);
    }

    /**
     * OPTIONS请求
     * @param string $url 指定链接
     * @param array $headers 设定请求头设置
     * @param array $opts 参数配置数组
     * @param bool $domain_empty 该链接是否是无主域链接
     * @return string 返回响应内容，失败是返回false
     */
    public function options($url, array $headers = [], array $opts = [], $domain_empty = false)
    {
        $add_opts = [
            CURLOPT_CUSTOMREQUEST => "OPTIONS", //设置 HTTP 的 method 为 OPTIONS
            CURLOPT_UPLOAD => false, //OPTIONS模式默认不上传文件
        ];
        $opts = $opts + $add_opts;
        return $this->http($url, $headers, $opts, $domain_empty);
    }

    /**
     * HEAD请求
     * @param string $url 指定链接
     * @param array $headers 设定请求头设置
     * @param array $opts 参数配置数组
     * @param bool $domain_empty 该链接是否是无主域链接
     * @return string 返回响应内容，失败是返回false
     */
    public function head($url, array $headers = [], array $opts = [], $domain_empty = false)
    {
        $add_opts = [
            CURLOPT_CUSTOMREQUEST => "HEAD", //设置 HTTP 的 method 为 HEAD
            CURLOPT_UPLOAD => false, //HEAD模式默认不上传文件
        ];
        $opts = $opts + $add_opts;
        return $this->http($url, $headers, $opts, $domain_empty);
    }

    /**
     * DELETE请求
     * @param string $url 指定链接
     * @param array $headers 设定请求头设置
     * @param array $opts 参数配置数组
     * @param bool $domain_empty 该链接是否是无主域链接
     * @return string 返回响应内容，失败是返回false
     */
    public function delete($url, array $headers = [], array $opts = [], $domain_empty = false)
    {
        $add_opts = [
            CURLOPT_CUSTOMREQUEST => "DELETE", //设置 HTTP 的 method 为 DELETE
            CURLOPT_UPLOAD => false, //DELETE模式默认不上传文件
        ];
        $opts = $opts + $add_opts;
        return $this->http($url, $headers, $opts, $domain_empty);
    }

    /**
     * PATCH请求
     * @param string $url 指定链接
     * @param array $headers 设定请求头设置
     * @param array $opts 参数配置数组
     * @param bool $domain_empty 该链接是否是无主域链接
     * @return string 返回响应内容，失败是返回false
     */
    public function patch($url, array $headers = [], array $opts = [], $domain_empty = false)
    {
        $add_opts = [
            CURLOPT_CUSTOMREQUEST => "PATCH", //设置 HTTP 的 method 为 PATCH
            CURLOPT_UPLOAD => false, //PATCH模式默认不上传文件
        ];
        $opts = $opts + $add_opts;
        return $this->http($url, $headers, $opts, $domain_empty);
    }

    /**
     * PUT请求
     * @param string $url 指定链接
     * @param mixed $data 可以是数组(推荐)或者请求字符串。
     * @param array $headers 设定请求头设置
     * @param array $opts 参数配置数组
     * @param bool $domain_empty 该链接是否是无主域链接
     * @return string 返回响应内容，失败是返回false
     */
    public function put($url, $data, array $headers = [], array $opts = [], $domain_empty = false)
    {
        $add_opts = [
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_UPLOAD => false,
            CURLOPT_POSTFIELDS => $data,
        ];
        $opts = $opts + $add_opts;
        return $this->http($url, $headers, $opts, $domain_empty);
    }

    /**
     * json请求
     * @param $url
     * @param $data
     * @param $header
     * @return mixed
     * @todo 待删除
     */
    public function httpPostJson($url, $data = [], $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }
}

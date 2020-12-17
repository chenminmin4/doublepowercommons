<?php

namespace fulicommons\util\net;

use CURLFile;

/**
 * Curl对象
 */
class Curl
{

    /**
     *
     * @var string 当前会话链接
     */
    private $_url = null;

    /**
     *
     * @var resource 当前会话句柄
     */
    private $_handle = null;

    /**
     * 当前会话设置数组
     * @var array
     */
    private $_opt = [];

    /**
     * 当前护花是否可分享
     * @var bool
     */
    private $_share = false;


    /**
     * 构造函数
     * @param string $url 指定会话链接
     * @param array $opt 指定选项
     * @param bool $share 指明是否使用share
     */
    public function __construct($url = null, array $opt = [], $share = false)
    {
        $this->_share = $share;
        $this->_handle = $this->init($url);
        if (!empty($url)) {
            $this->_url = $url;
            $this->setopt(CURLOPT_URL, $url);
        }
        if (!empty($opt)) {
            $this->setoptArray($opt);
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        if ($this->_handle && get_resource_type($this->_handle) == "curl") {
            $this->close();
        }
    }

    /**
     * 获取当前会话句柄
     * @return resource
     */
    public function getHandle()
    {
        return $this->_handle;
    }

    /**
     * 关闭当前会话
     */
    public function close()
    {
        if ($this->_share) {
            curl_share_close($this->_handle);
        } else {
            curl_close($this->_handle);
        }
        $this->_handle = null;
        $this->_opt = [];
    }

    /**
     * 复制当前CURL句柄和其所有选项
     * @return resource
     */
    public function copyHandle()
    {
        return curl_copy_handle($this->_handle);
    }

    /**
     * 返回最后一次的错误号
     * @return int
     */
    public function errno()
    {
        return curl_errno($this->_handle);
    }

    /**
     * 返回最近一次错误的字符串
     * @return string
     */
    public function error()
    {
        return curl_error($this->_handle);
    }

    /**
     * 使用 URL 编码给定的字符串
     * @param string $str 给定的字符串
     * @return string
     */
    public function escape($str)
    {
        return curl_escape($this->_handle, $str);
    }

    /**
     * 执行当前会话
     * @return mixed 执行结果，错误返回false
     */
    public function exec()
    {
        return curl_exec($this->_handle);
    }

    /**
     * 创建一个用于上传的CURLFile对象
     * @param string $filename 文件路径
     * @param string $mimetype MIME
     * @param string $postname 文件域表单名称
     * @return CURLFile
     */
    public static function fileCreate($filename, $mimetype, $postname)
    {
        return curl_file_create($filename, $mimetype, $postname);
    }

    /**
     * 获取当前cURL连接资源句柄的信息
     * @param int $opt 参数常量
     * @return mixed
     */
    public function getinfo($opt = null)
    {
        if (is_null($opt)) {
            return curl_getinfo($this->_handle);
        } else {
            return curl_getinfo($this->_handle, $opt);
        }
    }

    /**
     * 返回一个CURL句柄
     * @param string $url 指定链接URL
     * @return resource
     */
    public function init($url = null)
    {
        if ($this->_share) {
            return curl_share_init();
        } else {
            return curl_init($url);
        }
    }

    /**
     * 以新句柄方式设置当前句柄
     * @param resource $handle 要设置的句柄
     */
    public function setHandle(&$handle)
    {
        $this->_handle = $handle;
        $this->_opt = []; //使用此方法则无法获取到已有设置，只能重新设置了。
    }

    /**
     * 暂停或解除暂停当前会话，官方文档不齐全，不建议使用
     * @param int $bitmask 参数意义未知
     * @return int
     */
    public function pause($bitmask)
    {
        return curl_pause($this->_handle, $bitmask);
    }

    /**
     * 重置当前会话的所有设置
     */
    public function reset()
    {
        curl_reset($this->_handle);
        $this->_opt = [];
    }

    /**
     * 为当前传输会话批量设置选项
     * @param array $options 要设置的选项数组
     * @return bool
     */
    public function setoptArray($options)
    {
        $rst = curl_setopt_array($this->_handle, $options);
        if ($rst) {
            $this->_opt = $options + $this->_opt; //因为是数字键名，不能使用array_merge
        }
        return $rst;
    }

    /**
     * 为当前传输会话设置选项
     * @param int $option 需要设置的CURLOPT_XXX选项。
     * @param mixed $value 将设置在option选项上的值。
     * @return bool
     */
    public function setopt($option, $value)
    {
        if ($this->_share) {
            $rst = curl_share_setopt($this->_handle, $option, $value);
        } else {
            $rst = curl_setopt($this->_handle, $option, $value);
        }
        if ($rst) {
            $this->_opt[$option] = $value;
        }
        return $rst;
    }

    /**
     * 获取当前会话的所有设置选项
     * @return array
     */
    public function getopt()
    {
        return $this->_opt;
    }

    /**
     * 根据错误码返回错误描述
     * @param int $errornum 返回的错误码
     * @return string
     */
    public static function strError($errornum)
    {
        return curl_strerror($errornum);
    }

    /**
     * 解码给定的 URL 编码的字符串
     * @param string $str 待解码字符串
     * @return string
     */
    public function unescape($str)
    {
        return curl_unescape($this->_handle, $str);
    }

    /**
     * 获取cURL版本信息
     * @param int $age 参数意义未知
     * @return array
     */
    public static function version($age = 3)
    {
        return curl_version($age);
    }
}

<?php

namespace fulicommons\util\crypt;

use Exception;

/**
 * Base64编码解码类
 */
class Base64
{
    /**
     * Base64解码
     * @param string $data   待解码字符串
     * @param bool   $strict 是否忽略无法识别的字符
     * @return string 返回string
     * @throws Exception
     */
    public static function decode($data, $strict = false)
    {
        $result = base64_decode($data, $strict);
        if (false === $result) {
            throw new Exception('error on Base64::decode');
        }

        return $result;
    }

    /**
     * Base64编码
     * @param string $data 待编码字符串
     * @return string
     * @throws Exception
     */
    public static function encode($data)
    {
        $result = base64_encode($data);
        if (false === $result) {
            throw new Exception('error on Base64::encode');
        }

        return $result;
    }

    /**
     * 安全的URL字符串Base64编码
     * @param string $string    待编码字符串
     * @param bool   $filter_eq 是否过滤等于号
     * @return string
     */
    public static function encodeURLSafe($string, $filter_eq = true)
    {
        $data = base64_encode($string);
        if (false === $data) {
            throw new Exception('error on Base64::encode');
        }
        if ($filter_eq) {
            $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
        } else {
            $data = str_replace(['+', '/'], ['-', '_'], $data);
        }
        return $data;
    }

    /**
     * 安全的URL字符串Base64解码
     * @param string $string 待解码字符串
     * @return string
     */
    public static function decodeURLSafe($string)
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        $result = base64_decode($data);

        if (false === $result) {
            throw new Exception('error on Base64::decode');
        }
        return $result;
    }
}

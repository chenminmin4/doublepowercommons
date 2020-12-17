<?php

namespace fulicommons\util\log;

class Logger
{
    /**
     * 直接写入日志
     * @param string $content 文件内容
     * @param string $filepath 文件相对路径
     */
    public static function write($content, $filepath = "",$log_path = 'runtime_path/log/')
    {
        if (empty($filepath)) {
            $filepath = date('Ym/d') . ".log";
        } elseif (strlen($filepath) < 4 || substr($filepath, -4) != ".log") {
            $filepath .= ".log";
        }
        $filepath = str_replace("../", "/", str_replace("\\", "/", $filepath));
        $ary = explode('/', $filepath);
        $path = $log_path;
        for ($i = 0; $i < count($ary) - 1; $i++) {
            $path .= '/' . $ary[$i];
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
        $myfile = fopen($log_path . $filepath, "a+");
        fwrite($myfile, $content . "\n");
        fclose($myfile);
    }
}

<?php

namespace fulicommons\util\io;

use Directory as Dir;

/**
 * 目录操作类
 */
class Directory
{

    /**
     * 当前目录路径
     * @var string
     */
    private $_path = '';

    /**
     * 当前Directory实例
     * @var Dir
     */
    private $_directory = null;

    /**
     * 当前目录句柄
     * @var resource
     */
    private $_handle = null;

    /**
     * windows环境下的UTF8字符串转GBK
     */
    const WIN_UTF8_2_GBK = 0;

    /**
     * windows环境下的GBK字符串转UTF8
     */
    const WIN_GBK_2_UTF8 = 1;

    /**
     * 构造函数
     * @param string $path 指定目录路径
     * @param boolean $auto_build 如果指定路径不存在，是否自动创建
     * @param boolean $handle 是否创建句柄，默认true，注意在本处创建句柄后当前工作目录将相应跟随
     */
    public function __construct($path, $auto_build = false, $handle = true)
    {
        if ($auto_build) {
            $this->makeDir($path);
        }
        $this->_path = $path;
        if ($handle) {
            $this->changeTo($path, true);
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        if ($this->_handle) {
            $this->close();
        }
    }

    /**
     * 对要使用的路径进行中文兼容性处理
     * Windows、Linux系统针对中文字符创的兼容性处理
     * Windows由于使用GBK编码会导致中文路径乱码，进行UTF-8字符串转GBK字符串后再建立
     * @param string $path 待处理字符串
     * @param string $direction 方向 WIN_UTF8_2_GBK,WIN_GBK_2_UTF8
     * @return string 处理后字符串
     */
    private static function stringSerialize($path, $direction)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if ($direction == self::WIN_UTF8_2_GBK) {
                $path = iconv('UTF-8', 'GBK', $path);
            } else if ($direction == self::WIN_GBK_2_UTF8) {
                $path = iconv('GBK', 'UTF-8', $path);
            }
        }
        return $path;
    }

    /**
     * 创建目录,可递归创建多层目录
     * @param string $path 要创建的目录，已存在则不进行处理
     * @return boolean
     */
    private function makeDir($path)
    {
        $path = self::stringSerialize($path, self::WIN_UTF8_2_GBK);
        if (is_dir($path)) {
            return true;
        } else {
            return mkdir($path, 0777, true);
        }
    }

    /**
     * 打开指定目录，返回Directory实例
     * @param string $path 指定目录
     * @return Dir
     */
    public function open($path)
    {
        $path = self::stringSerialize($path, self::WIN_UTF8_2_GBK);
        $this->_handle = opendir($path);
        $directory = dir($path);
        $this->_directory = $directory;
        return $directory;
    }

    /**
     * 关闭当前目录
     */
    public function close()
    {
        closedir($this->_handle);
        $this->_handle = null;
    }

    /**
     * 获取当前的Directory对象
     * @return Dir
     */
    public function getDirectory()
    {
        return $this->_directory;
    }

    /**
     * 改变当前目录
     * @param string $path 指定目录
     * @param mixed $handle 是否为目标目录创建句柄，默认true
     * @return boolean 如果指定目录不存在也返回false
     */
    public function changeTo($path, $handle = true)
    {
        $this->makeDir($this->_path);
        if (!is_dir(self::stringSerialize($path, self::WIN_UTF8_2_GBK))) {
            return false;
        }
        if ($handle) {
            if ($this->_handle) {
                $this->close();
            }
            $this->open($path);
        }
        $this->_path = $path;
        return chdir(self::stringSerialize($path, self::WIN_UTF8_2_GBK));
    }

    /**
     * 改变根目录
     * @param string $path 指定目录
     * @return boolean 此函数未在 Windows 平台下实现，故也返回false
     */
    public static function changeRoot($path)
    {
        if (!function_exists('chroot')) {

            dirname(__FILE__); //当前文件路径

            return false;
        }
        return chroot($path);
    }

    /**
     * 取得当前工作目录
     * @return string
     */
    public static function getNowPath()
    {
        return self::stringSerialize(getcwd(), self::WIN_GBK_2_UTF8);
    }

    /**
     * 遍历当前目录的文件条目
     * @param callable $func 遍历函数，参数($file);$file:
     * @param boolean $filter_base 是否剔除.和..，默认true
     */
    public function read(callable $func, $filter_base = true)
    {
        while (($file = readdir($this->_handle)) !== false) {
            if ($filter_base && ($file == "." || $file == "..")) {
                continue;
            }
            $file = self::stringSerialize($file, self::WIN_GBK_2_UTF8);
            $func($file);
        }
    }

    /**
     * 将当前目录流重置到目录的开头。
     */
    public function rewind()
    {
        rewinddir($this->_handle);
    }

    /**
     * 列出指定路径中的文件和目录 (含.和..)
     * @param int $sorting_order 排序
     * @param string $path 指定相对于当前工作目录的路径，不指定则为当前目录下
     * @return array
     */
    public function scan($sorting_order = 0, $path = null)
    {
        if ($path == null) {
            $path = "./"; //未指定表示当前工作目录下
        } else {
            $path = self::stringSerialize($path, self::WIN_UTF8_2_GBK);
        }
        $arr = scandir($path, $sorting_order);

        //针对Windows下的GBK编码处理
        foreach ($arr as $key => $value) {
            $arr[$key] = self::stringSerialize($value, self::WIN_GBK_2_UTF8);
        }

        return $arr;
    }

    /**
     * 在当前创建一个文件，返回结果
     * @param string $name 不含路径的文件名
     * @return bool
     */
    public function createFile($name)
    {
        return touch($name, time(), null);
    }

    /**
     * 在本目录下新建目录
     * @param string $name 新建目录名
     * @param int $mode 设置访问权
     * @return bool
     */
    public function createDir($name, $mode = 0777)
    {
        $name = self::stringSerialize($name, self::WIN_UTF8_2_GBK);

        if (is_dir($name)) { //已有该目录则直接返回true
            return true;
        }

        return mkdir($name, $mode, true);
    }

    /**
     * 删除当前目录下的指定文件
     * 虽然该方法也可以用来删除文件夹，但不建议如此使用
     * @param string $name 不含路径的文件名
     * @return bool
     */
    public function deleteFile($name)
    {
        $name = self::stringSerialize($name, self::WIN_UTF8_2_GBK);
        if (is_file($name)) {
            return unlink($name);
        } else {
            return true; //没有该文件则返回true
        }
    }

    /**
     * 删除目录及目录下所有文件或删除指定文件(即强制删除非空目录)
     * @param string $path 待删除目录路径
     * @return bool
     */
    private function delDir($path)
    {
        $handle = false;
        if (is_dir($path)) {
            $handle = opendir($path);
        }
        if ($handle) {
            while (false !== ($item = readdir($handle))) { //删除文件夹内的文件及文件夹
                clearstatcache();
                if ($item != "." && $item != "..") {
                    is_dir("{$path}/{$item}") ? $this->delDir("{$path}/{$item}") : unlink("{$path}/{$item}");
                }
            }
            closedir($handle);
            if ($path == '.') {
                return true;
            }
            return rmdir($path);
        } else {
            if (file_exists($path)) {
                return unlink($path);
            } else {
                return false;
            }
        }
    }

    /**
     * 删除当前目录下的指定文件夹
     * @param string $name 要删除的目录名， 可以指定多级目录
     * @param boolean $force 如果目录不为空时是否强制删除
     * @return bool
     */
    public function deleteDir($name, $force = false)
    {
        $name = self::stringSerialize($name, self::WIN_UTF8_2_GBK);
        if (!is_dir($name)) {
            return true;
        }
        if ($force) {
            return $this->delDir($name);
        } else {
            return rmdir($name);
        }
    }

    /**
     * 清理当前文件夹，即删除里面的所有文件及文件夹
     */
    public function clear()
    {
        $this->delDir('.');
    }

    /**
     * 判断给定文件名是否是一个目录
     * 注意当前工作目录的指向会影响相对路径
     * @param string $path 指定目录
     * @return bool
     */
    public static function isDir($path)
    {
        return is_dir($path);
    }

    /**
     * 在当前工作文件夹建立一个具有唯一文件名的文件，返回其文件名
     * @param string $prefix 产生临时文件的前缀
     * @return string
     */
    public static function createTempFile($prefix = '')
    {
        $file = tempnam(getcwd(), $prefix);
        $file = self::stringSerialize($file, self::WIN_GBK_2_UTF8);
        return $file;
    }
}

<?php

namespace fulicommons\util\io;

/**
 * 文件操作类
 */
class File
{

    /**
     * 当前文件完整路径
     * @var string
     */
    private $_file_path;

    /**
     * 当前文件句柄
     * @var resource
     */
    private $_file_resource;

    /**
     * windows环境下的UTF8字符串转GBK
     */
    const WIN_UTF8_2_GBK = 0;

    /**
     * windows环境下的GBK字符串转UTF8
     */
    const WIN_GBK_2_UTF8 = 1;

    /**
     * 构造
     * @param string $filepath 完整含目录文件名
     * @param boolean $auto_build 是否自动创建，默认false
     * @param boolean $handle 是否创建句柄，默认false
     * @param string $mode 打开模式，默认r
     */
    public function __construct($filepath, $auto_build = false, $handle = false, $mode = "r")
    {
        $this->_file_path = self::stringSerialize($filepath, self::WIN_UTF8_2_GBK);
        if ($auto_build) {
            $dir = self::stringSerialize($this->getDirName(), self::WIN_UTF8_2_GBK);
            $this->makeDir($dir);
            $this->setTouch();
        }
        if ($handle) {
            $this->open($mode);
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
     * 递归创建目录
     * @param string $path 要创建的目录，已存在则不进行处理
     * @return boolean
     */
    private function makeDir($path)
    {
        if (is_dir($path)) {
            return true;
        } else {
            return mkdir($path, 0777, true);
        }
    }

    /**
     * 返回路径中的文件名部分
     * @param string $suffix 如果文件名是以 suffix 结束的，那这一部分也会被去掉。
     * @return string
     */
    public function getBaseName($suffix = null)
    {
        if ($suffix != null) {
            $suffix = self::stringSerialize($suffix, self::WIN_UTF8_2_GBK); //针对$suffix可能存在中文的处理
        }
        $string = basename($this->_file_path, $suffix);
        return self::stringSerialize($string, self::WIN_GBK_2_UTF8);
    }

    /**
     * 改变当前文件所属的组
     * @param mixed $group 组的名称或数字。
     * @return boolean
     */
    public function changeGroup($group)
    {
        if ($this->isLink()) {
            return lchgrp($this->_file_path, $group);
        } else {
            return chgrp($this->_file_path, $group);
        }
    }

    /**
     * 改变当前文件模式
     * @param int $mode 注意 mode 不会被自动当成八进制数值，而且也不能用字符串（例如 "g+w"）。要确保正确操作，需要给 mode 前面加上 0：
     * @return boolean
     */
    public function changeMode($mode)
    {
        return chmod($this->_file_path, $mode);
    }

    /**
     * 改变当前文件的所有者
     * @param mixed $user 用户名或数字。
     * @return boolean
     */
    public function changeOwner($user)
    {
        if ($this->isLink()) {
            return lchown($this->_file_path, $user);
        } else {
            return chown($this->_file_path, $user);
        }
    }

    /**
     * 清除当前文件状态缓存
     */
    public function clearStatCache()
    {
        clearstatcache(true, $this->_file_path);
    }

    /**
     * 将当前文件拷贝到路径dest
     * @param string $dest 指定路径
     * @param string $name 指定文件名，不指定则为原文件名
     * @param boolean $build_dir 如果指定目录不存在，是否自动建立
     * @param boolean $cover 如果指定文件存在，是否覆盖
     * @return boolean
     */
    public function copyTo($dest, $name = "", $build_dir = true, $cover = false)
    {
        if ($build_dir) {
            $this->makeDir(self::stringSerialize($dest, self::WIN_UTF8_2_GBK));
        }
        if (empty($name)) {
            $name = $this->getBaseName();
        }
        $full_dest = self::stringSerialize($dest . "/" . $name, self::WIN_UTF8_2_GBK);
        if (!$cover && is_file($full_dest)) {
            return false; //文件已存在，且不允许覆盖
        }
        if (is_dir(self::stringSerialize($dest, self::WIN_UTF8_2_GBK))) {
            return copy($this->_file_path, $full_dest);
        } else {
            return false; //文件夹不存在
        }
    }

    /**
     * 删除当前文件
     * @return boolean
     */
    public function delete()
    {
        if (is_file($this->_file_path)) {
            return unlink($this->_file_path);
        } else {
            return true; //没有该文件则返回true
        }
    }

    /**
     * 返回当前文件路径中的目录部分
     * @return string
     */
    public function getDirName()
    {
        return self::stringSerialize(dirname($this->_file_path), self::WIN_GBK_2_UTF8);
    }

    /**
     * 关闭当前文件
     * @param bool $progress
     * @return bool
     */
    public function close($progress = false)
    {
        if ($this->_file_resource) {
            if ($progress) {
                $result = pclose($this->_file_resource);
            } else {
                $result = fclose($this->_file_resource);
            }
        } else {
            $result = true; //已关闭则返回true
        }

        if ($result) {
            $this->_file_resource = null; //如果正确关闭了则清空当前对象的file_resource
        }

        return $result;
    }

    /**
     * 测试当前文件指针是否到了文件结束的位置,如果文件未打开则eof为true
     * @return boolean
     */
    public function eof()
    {
        if ($this->_file_resource) {
            return feof($this->_file_resource);
        } else {
            return true;
        }
    }

    /**
     * 将缓冲内容输出到文件
     * @return boolean
     */
    public function flush()
    {
        if ($this->_file_resource) {
            return fflush($this->_file_resource);
        } else {
            return false;
        }
    }

    /**
     * 从文件指针中读取一个字符。 碰到 EOF 则返回 FALSE 。
     * @return string 如果碰到 EOF 则返回 FALSE。
     */
    public function getC()
    {
        if ($this->_file_resource) {
            return fgetc($this->_file_resource);
        } else {
            return '';
        }
    }

    /**
     * 从文件指针中读入一行并解析 CSV 字段
     * @param int $length 规定行的最大长度。必须大于 CVS 文件内最长的一行。
     * @param string $delimiter 设置字段分界符（只允许一个字符），默认值为逗号。
     * @param string $enclosure 设置字段环绕符（只允许一个字符），默认值为双引号。
     * @param string $escape 设置转义字符（只允许一个字符），默认是一个反斜杠。
     * @return array 如果碰到 EOF 则返回 FALSE。
     */
    public function getCSV($length = 0, $delimiter = ",", $enclosure = '"', $escape = "\\")
    {
        if ($this->_file_resource) {
            return fgetcsv($this->_file_resource, $length, $delimiter, $enclosure, $escape);
        } else {
            return [];
        }
    }

    /**
     * 从文件指针中读取一行
     * @param int $length 规定要读取的字节数。默认是 1024 字节。
     * @return string 若失败，则返回 false。
     */
    public function getS($length = null)
    {
        if ($this->_file_resource) {
            if (is_null($length)) {
                $rst = fgets($this->_file_resource);
            } else {
                $rst = fgets($this->_file_resource, $length);
            }
            return $rst;
        } else {
            return '';
        }
    }

    /**
     * 从文件指针中读取一行并过滤掉HTML和PHP标记。
     * @param int $length 规定要读取的字节数。默认是 1024 字节
     * @param string $allowable_tags 规定不会被删除的标签。形如“<p>,<b>”
     * @return string
     */
    public function getSS($length = null, $allowable_tags = null)
    {
        if ($this->_file_resource) {
            if (is_null($length)) {
                $rst = fgetss($this->_file_resource);
            } else {
                $rst = fgetss($this->_file_resource, $length, $allowable_tags);
            }
            return $rst;
        } else {
            return '';
        }
    }

    /**
     * 检查当前文件是否存在
     * @return boolean
     */
    public function exists()
    {
        return file_exists($this->_file_path);
    }

    /**
     * 将整个文件读入一个字符串
     * @param int $offset 插入位置偏移量，默认为0表示最开始地方
     * @param int $maxlen 指定读取长度，超过该长度则不读取，默认不指定全部读取
     * @return string
     */
    public function getContents($offset = 0, $maxlen = null)
    {
        if (is_null($maxlen)) {
            return file_get_contents($this->_file_path, false, null, $offset);
        } else {
            return file_get_contents($this->_file_path, false, null, $offset, $maxlen);
        }
    }

    /**
     * 将一个字符串写入文件
     * @param mixed $data 要写入的数据。类型可以是 string ， array 或者是 stream 资源
     * @param int $flags [FILE_USE_INCLUDE_PATH|FILE_APPEND|LOCK_EX] 指定配置
     * @return int
     */
    public function putContents($data, $flags = 0)
    {
        return file_put_contents($this->_file_path, $data, $flags);
    }

    /**
     * 把整个文件读入一个数组中
     * @param int $flags [FILE_USE_INCLUDE_PATH|FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES] 指定配置
     * @return array
     */
    public function getContentsOnArray($flags = 0)
    {
        return file($this->_file_path, $flags);
    }

    /**
     * 获取文件信息
     * @param string $key 信息名
     * @return mixed
     */
    public function getInfo($key)
    {
        switch ($key) {
            case 'atime' : //上次访问时间
                $rst = fileatime($this->_file_path);
                break;
            case 'ctime' : //inode修改时间
                $rst = filectime($this->_file_path);
                break;
            case 'group' : //文件的组
                $rst = filegroup($this->_file_path);
                break;
            case 'inode' : //文件的inode
                $rst = fileinode($this->_file_path);
                break;
            case 'mtime' : //文件修改时间
                $rst = filemtime($this->_file_path);
                break;
            case 'owner' : //文件的所有者
                $rst = fileowner($this->_file_path);
                break;
            case 'perms' : //文件的权限
                $rst = fileperms($this->_file_path);
                break;
            case 'size' : //文件大小
                $rst = filesize($this->_file_path);
                break;
            case 'type' : //文件类型
                $rst = filetype($this->_file_path);
                break;
            default :
                $rst = false;
        }
        return $rst;
    }

    /**
     * 轻便的咨询文件锁定
     * @param int $operation [LOCK_SH|LOCK_EX|LOCK_UN]
     * @param int $wouldblock 如果锁定会堵塞的话返回1
     * @return boolean
     */
    public function lock($operation, &$wouldblock = null)
    {
        if ($this->_file_resource) {
            return flock($this->_file_resource, $operation, $wouldblock);
        } else {
            return false;
        }
    }

    /**
     * 用模式匹配文件名
     * @param string $pattern shell 统配符
     * @param int $flags [FNM_NOESCAPE|FNM_PATHNAME|FNM_PERIOD|FNM_CASEFOLD] 指定配置
     * @return boolean
     */
    public function nameMatch($pattern, $flags = 0)
    {
        return fnmatch($pattern, $this->getBaseName(), $flags);
    }

    /**
     * 打开当前文件用于读取和写入
     * @param string $mode 访问类型
     * @param boolean $progress 指向进程文件
     * @param string $command 命令
     * @return resource
     * @todo 针对popen执行进程文件还存在问题，待修复
     */
    public function open($mode, $progress = false, $command = '')
    {
        if ($progress) {
            $res = popen($command, $mode);
            var_dump($res);
        } else {
            if (is_file($this->_file_path)) {
                $res = fopen($this->_file_path, $mode);
            } else {
                $res = false;
            }
        }
        if ($res) {
            $this->_file_resource = $res;
        }
        return $res;
    }

    /**
     * 输出文件指针处的所有剩余数据
     * @return int 返回剩余数据字节数
     */
    public function passthru()
    {
        if ($this->_file_resource) {
            return fpassthru($this->_file_resource);
        } else {
            return 0;
        }
    }

    /**
     * 将行格式化为 CSV 并写入文件指针
     * @param array $fields 要写入的数组数据
     * @param string $delimiter 分隔符
     * @param string $enclosure 界限符
     * @param string $escape_char 转义符
     * @return int 如果失败返回false
     */
    public function putCSV(array $fields, $delimiter = ",", $enclosure = '"', $escape_char = "\\")
    {
        if ($this->_file_resource) {
            return fputcsv($this->_file_resource, $fields, $delimiter, $enclosure, $escape_char);
        } else {
            return false;
        }
    }

    /**
     * 写入文件（可安全用于二进制文件）
     * @param string $string 要写入的字符串
     * @param int $length 指定写入长度
     * @return int 如果失败返回false
     */
    public function putS($string, $length = null)
    {
        if ($this->_file_resource) {
            if (is_null($length)) {
                $rst = fputs($this->_file_resource, $string);
            } else {
                $rst = fputs($this->_file_resource, $string, $length);
            }
            return $rst;
        } else {
            return false;
        }
    }

    /**
     * 读取文件（可安全用于二进制文件）
     * @param int $length
     * @return string
     */
    public function read($length)
    {
        if ($this->_file_resource) {
            return fread($this->_file_resource, $length);
        } else {
            return '';
        }
    }

    /**
     * 从文件中格式化输入
     * @param string $format
     * @return array
     */
    public function scanf($format)
    {
        if ($this->_file_resource) {
            return fscanf($this->_file_resource, $format);
        } else {
            return [];
        }
    }

    /**
     * 在文件指针中定位
     * @param int $offset 偏移量
     * @param int $whence 设置偏移量类型[SEEK_SET|SEEK_CUR|SEEK_END]
     * @return int
     */
    public function seek($offset, $whence = 0)
    {
        if ($this->_file_resource) {
            return fseek($this->_file_resource, $offset, $whence);
        } else {
            return 0;
        }
    }

    /**
     * 通过已打开的文件指针取得文件信息
     * @return array
     */
    public function getStat()
    {
        if ($this->isLink()) {
            return lstat($this->_file_path);
        } else {
            if ($this->_file_resource) {
                return fstat($this->_file_resource);
            } else {
                return stat($this->_file_path);
            }
        }
    }

    /**
     * 返回文件指针读/写的位置
     * @return int
     */
    public function tell()
    {
        if ($this->_file_resource) {
            return ftell($this->_file_resource);
        } else {
            return false;
        }
    }

    /**
     * 将文件截断到给定的长度
     * @param int $size 指定长度
     * @return boolean
     */
    public function truncate($size)
    {
        if ($this->_file_resource) {
            return ftruncate($this->_file_resource, $size);
        } else {
            return false;
        }
    }

    /**
     * 写入文件（可安全用于二进制文件）
     * @param string $string 要写入的字符串
     * @param int $length 指定写入长度
     * @return int 失败时返回false
     */
    public function write($string, $length = null)
    {
        if ($this->_file_resource) {
            if (is_null($length)) {
                $rst = fwrite($this->_file_resource, $string);
            } else {
                $rst = fwrite($this->_file_resource, $string, $length);
            }
            return $rst;
        } else {
            return false;
        }
    }

    /**
     * 判断给定文件名是否可执行
     * @return boolean
     */
    public function isExecutable()
    {
        return is_executable($this->_file_path);
    }

    /**
     * 判断给定文件名是否为一个正常的文件
     * @return boolean
     */
    public function isFile()
    {
        return is_file($this->_file_path);
    }

    /**
     * 判断给定文件名是否为一个符号连接
     * @return boolean
     */
    public function isLink()
    {
        return is_link($this->_file_path);
    }

    /**
     * 判断给定文件名是否可读
     * @return boolean
     */
    public function isReadable()
    {
        return is_readable($this->_file_path);
    }

    /**
     * 判断当前文件是否是通过 HTTP POST 上传的
     * @return boolean
     */
    public function isUploadedFile()
    {
        return is_uploaded_file($this->_file_path);
    }

    /**
     * 判断当前文件是否可写
     * @return boolean
     */
    public function isWritable()
    {
        return is_writable($this->_file_path);
    }

    /**
     * 判断当前文件是否可写
     * @return boolean
     */
    public function isWriteable()
    {
        return is_writeable($this->_file_path);
    }

    /**
     * 建立一个硬连接(不能运行在windows环境下)
     * @param string $target 要链接的目标
     * @return boolean
     */
    public function linkTo($target)
    {
        return link($target, $this->_file_path);
    }

    /**
     * 获取一个连接的信息(不能运行在windows环境下)
     * @return int
     */
    public function getLinkInfo()
    {
        return linkinfo($this->_file_path);
    }

    /**
     * 返回文件路径的信息
     * @param int $options 如果没有传入 options ，将会返回包括以下单元的数组 array ：dirname，basename和 extension（如果有），以 及filename。
     * @return mixed
     */
    public function getPathInfo($options = null)
    {
        if (is_null($options)) {
            $arr = pathinfo($this->_file_path);
            foreach ($arr as $key => $value) {
                $arr[$key] = self::stringSerialize($value, self::WIN_GBK_2_UTF8);
            }
            return $arr;
        } else {
            return self::stringSerialize(pathinfo($this->_file_path, $options), self::WIN_GBK_2_UTF8);
        }
    }

    /**
     * 读取文件并写入到输出缓冲。
     * @return int
     */
    public function echoReadFile()
    {
        return readfile($this->_file_path);
    }

    /**
     * 返回符号连接指向的目标(不能运行在windows环境下)
     * @return string
     */
    public function returnReadLink()
    {
        return readlink($this->_file_path);
    }

    /**
     * 返回规范化的绝对路径名
     * @return string
     */
    public function getRealPath()
    {
        return self::stringSerialize(realpath($this->_file_path), self::WIN_GBK_2_UTF8);
    }

    /**
     * 重命名一个文件,可用于移动文件
     * @param string $newname 要移动到的目标位置路径
     * @param boolean $auto_build 如果指定的路径不存在，是否创建，默认true
     * @return boolean
     */
    public function reName($newname, $auto_build = true)
    {
        $newname = self::stringSerialize($newname, self::WIN_UTF8_2_GBK);
        if ($auto_build) {
            $dir = dirname($newname);
            $this->makeDir($dir);
        }
        return rename($this->_file_path, $newname);
    }

    /**
     * 重置文件指针的位置
     * @return boolean
     */
    public function reWind()
    {
        return rewind($this->_file_resource);
    }

    /**
     * 对于已有的 target 建立一个名为 link 的符号连接。(不能运行在windows环境下)
     * @param string $target 目标路径
     * @return boolean
     */
    public function symLinkTo($target)
    {
        return symlink($target, $this->_file_path);
    }

    /**
     * 设定文件的访问和修改时间
     * 注意，如果文件不存在则尝试创建
     * @param int $time 要设定的修改时间
     * @param int $atime 要设定的访问时间
     * @return boolean
     */
    public function setTouch($time = null, $atime = null)
    {
        if (is_null($time)) {
            $time = time();
        }
        return touch($this->_file_path, $time, $atime);
    }

    /**
     * 改变当前的 umask
     * @param int $mask
     * @return int
     */
    public static function changeUmask($mask)
    {
        return umask($mask);
    }

    /**
     * 获取当前的 umask
     * @return int
     */
    public static function getUmask()
    {
        return umask();
    }

    /**
     * 设置当前打开文件的缓冲大小。
     * @param int $buffer 规定缓冲大小，以字节计。
     * @return mixed 为启动句柄时返回false；否则如果成功，该函数返回 0，否则返回 EOF。
     */
    public function setBuffer($buffer)
    {
        if ($this->_file_resource) {
            return set_file_buffer($this->_file_resource, $buffer);
        } else {
            return false;
        }
    }
}

<?php

namespace fulicommons\util\io;

use Exception;
use fulicommons\util\image\Image;

/**
 * Plupload上传
 */
class Plupload
{

    /**
     * @var array 配置
     */
    private $conf;

    /**
     * @var resource 输出文件
     */
    private $out;

    /**
     * @var int 错误码
     */
    protected $error = null;

    /**
     * @var string 错误信息
     */
    protected $errmsg = '';

    public function __construct($conf = [])
    {
        $this->conf = array_merge(
            [
                'file_data_name'             => 'file',
                'tmp_dir'                    => ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload",
                'target_dir'                 => false,
                'cleanup'                    => true,
                'max_file_age'               => 5 * 3600, // in hours
                'max_execution_time'         => 5 * 60, // in seconds (5 minutes by default)
                'chunk'                      => isset($_REQUEST['chunk']) ? intval($_REQUEST['chunk']) : 0,
                'chunks'                     => isset($_REQUEST['chunks']) ? intval($_REQUEST['chunks']) : 0,
                'append_chunks_to_target'    => true,
                'combine_chunks_on_complete' => true,
                'file_name'                  => isset($_REQUEST['name']) ? $_REQUEST['name'] : false,
                'allow_extensions'           => false,
                'delay'                      => 0, // in seconds
                'cb_sanitize_file_name'      => [$this, 'sanitize_file_name'],
                'cb_check_file'              => false,
                'cb_filesize'                => [$this, 'filesize'],
                'error_strings'              => [
                    103     => "Failed to move uploaded file.",
                    101    => "Failed to open input stream.",
                    102   => "Failed to open output stream.",
                    100   => "Failed to open temp directory.",
                    104     => "File type not allowed.",
                    111  => "Failed due to unknown error.",
                    105 => "File didn't pass security check.",
                ],
                'debug'                      => false,
                'log_path'                   => "error.log",
            ],
            $conf
        );
    }

    public function __destruct()
    {
        $this->reset();
    }

    public function handleUpload()
    {
        $conf = $this->conf;

        set_time_limit($conf['max_execution_time']);

        try {
            // Start fresh
            $this->reset();

            // Cleanup outdated temp files and folders
            if ($conf['cleanup']) {
                $this->cleanup();
            }

            // Fake network congestion
            if ($conf['delay']) {
                sleep($conf['delay']);
            }

            if (!$conf['file_name']) {
                if (!empty($_FILES)) {
                    $conf['file_name'] = $_FILES[$conf['file_data_name']]['name'];
                } else {
                    throw new Exception('', 101);
                }
            }

            if (is_callable($conf['cb_sanitize_file_name'])) {
                $file_name = call_user_func($conf['cb_sanitize_file_name'], $conf['file_name']);
            } else {
                $file_name = $conf['file_name'];
            }

            // Check if file type is allowed
            if ($conf['allow_extensions']) {
                if (is_string($conf['allow_extensions'])) {
                    $conf['allow_extensions'] = preg_split('{\s*,\s*}', $conf['allow_extensions']);
                }

                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (!in_array($ext, $conf['allow_extensions'])) {
                    throw new Exception("File type not allowed:" . $ext, 104);
                }

                if(Image::isImg($ext)){
                    if (!empty($_FILES)) {
                        //处理图片旋转问题
                        Image::newPictureSteeringCorrection($_FILES[$conf['file_data_name']]['tmp_name']);
                        //Image::pictureSteeringCorrection($_FILES[$conf['file_data_name']]['tmp_name']);
                    }
                }
            }

            $this->lockTheFile($file_name);

            $this->log("$file_name received" . ($conf['chunks'] ? ", chunks enabled: {$conf['chunk']} of {$conf['chunks']}" : ''));

            // Write file or chunk to appropriate temp location
            if ($conf['chunks']) {
                $result = $this->handleChunk($conf['chunk'], $file_name);
            } else {
                $result = $this->handleFile($file_name);
            }

            $this->unlockTheFile($file_name);
            return $result;
        } catch (Exception $ex) {
            $this->error = $ex->getCode();
            $this->errmsg = $ex->getMessage();
            $this->log("ERROR: " . $this->getErrorMessage());
            $this->unlockTheFile($file_name);
            return false;
        }
    }

    /**
     * 错误码
     * @return int
     */
    public function getErrorCode()
    {
        if (!$this->error) {
            return null;
        }

        if (!isset($this->conf['error_strings'][$this->error])) {
            return 111;
        }

        return $this->error;
    }

    /**
     * 错误信息
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->errmsg) {
            return $this->errmsg;
        }

        if ($code = $this->getErrorCode()) {
            return $this->conf['error_strings'][$code];
        } else {
            return '';
        }
    }

    /**
     * 合并文件分块
     * @param $file_name
     * @return array|false
     */
    public function combineChunksFor($file_name)
    {
        $file_path = $this->getTargetPathFor($file_name);
        if (!$tmp_path = $this->writeChunksToFile("$file_path.dir.part", "$file_path.part")) {
            return false;
        }
        return $this->rename($tmp_path, $file_path);
    }

    protected function handleChunk($chunk, $file_name)
    {
        $file_path = $this->getTargetPathFor($file_name);

        $this->log($this->conf['append_chunks_to_target']
            ? "chunks being appended directly to the target $file_path.part"
            : "standalone chunks being written to $file_path.dir.part"
        );

        if ($this->conf['append_chunks_to_target']) {
            $chunk_path = $this->writeUploadTo("$file_path.part", false, 'ab');

            if ($this->isLastChunk($file_name)) {
                return $this->rename($chunk_path, $file_path);
            }
        } else {
            $chunk_path = $this->writeUploadTo("$file_path.dir.part" . DIRECTORY_SEPARATOR . "$chunk.part");

            if ($this->conf['combine_chunks_on_complete'] && $this->isLastChunk($file_name)) {
                return $this->combineChunksFor($file_name);
            }
        }

        return [
            'name'  => $file_name,
            'path'  => $chunk_path,
            'chunk' => $chunk,
            'size'  => call_user_func($this->conf['cb_filesize'], $chunk_path),
        ];
    }

    protected function handleFile($file_name)
    {
        $file_path = $this->getTargetPathFor($file_name);
        $tmp_path = $this->writeUploadTo($file_path . ".part");
        return $this->rename($tmp_path, $file_path);
    }

    protected function rename($tmp_path, $file_path)
    {
        // Upload complete write a temp file to the final destination
        if (!$this->fileIsOK($tmp_path)) {
            if ($this->conf['cleanup']) {
                @unlink($tmp_path);
            }
            throw new Exception('', 105);
        }

        if (rename($tmp_path, $file_path)) {
            $this->log("$tmp_path successfully renamed to $file_path");

            return [
                'name' => basename($file_path),
                'path' => $file_path,
                'size' => call_user_func($this->conf['cb_filesize'], $file_path),
            ];
        } else {
            return false;
        }
    }

    /**
     * @param   string     $file_path The path to write the file to
     * @param string|false  $file_data_name The name of the multipart field
     * @param string $mode mode
     * @return string
     */
    protected function writeUploadTo($file_path, $file_data_name = false, $mode = 'wb')
    {
        if (!$file_data_name) {
            $file_data_name = $this->conf['file_data_name'];
        }

        $base_dir = dirname($file_path);
        if (!file_exists($base_dir) && !@mkdir($base_dir, 0777, true)) {
            throw new Exception('', 100);
        }

        if (!empty($_FILES)) {
            if (!isset($_FILES[$file_data_name]) || $_FILES[$file_data_name]["error"] || !is_uploaded_file($_FILES[$file_data_name]["tmp_name"])) {
                throw new Exception('', 101);
            }
            return $this->writeToFile($_FILES[$file_data_name]["tmp_name"], $file_path, $mode);
        } else {
            return $this->writeToFile("php://input", $file_path, $mode);
        }
    }

    /**
     * Write source or set of sources to the specified target. Depending on the mode
     * sources will either overwrite the content in the target or will be appended to
     * the target.
     *
     * @param array|string $source_paths
     * @param string       $target_path
     * @param string [$mode='wb'] Mode to use (to append use 'ab')
     * @return string Path to the written target file
     */
    protected function writeToFile($source_paths, $target_path, $mode = 'wb')
    {
        if (!is_array($source_paths)) {
            $source_paths = [$source_paths];
        }

        if (!$out = fopen($target_path, $mode)) {
            throw new Exception('', 102);
        }

        foreach ($source_paths as $source_path) {
            if (!$in = fopen($source_path, "rb")) {
                throw new Exception('', 101);
            }

            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }

            fclose($in);

            $this->log("$source_path " . ($mode == 'wb' ? "written" : "appended") . " to $target_path");
        }

        fflush($out);
        fclose($out);

        return $target_path;
    }

    /**
     * Combine chunks from the specified folder into the single file.
     *
     * @param string $chunk_dir   Directory containing the chunks
     * @param string $target_path The file to write the chunks to
     * @return string File path containing combined chunks
     * @throws Exception In case of error generates exception with the corresponding code
     *
     */
    protected function writeChunksToFile($chunk_dir, $target_path)
    {
        $chunk_paths = [];

        for ($i = 0; $i < $this->conf['chunks']; $i++) {
            $chunk_path = $chunk_dir . DIRECTORY_SEPARATOR . "$i.part";
            if (!file_exists($chunk_path)) {
                throw new Exception('', 103);
            }
            $chunk_paths[] = $chunk_path;
        }

        $this->writeToFile($chunk_paths, $target_path, 'ab');

        $this->log("$chunk_dir combined into $target_path");

        // Cleanup
        if ($this->conf['cleanup']) {
            $this->rrmdir($chunk_dir);
        }

        return $target_path;
    }

    /**
     * Checks if currently processed chunk for the given filename is the last one.
     *
     * @param string $file_name
     * @return bool
     */
    protected function isLastChunk($file_name)
    {
        if ($this->conf['append_chunks_to_target']) {
            if ($result = $this->conf['chunks'] && $this->conf['chunks'] == $this->conf['chunk'] + 1) {
                $this->log("last chunk received: {$this->conf['chunks']} out of {$this->conf['chunks']}");
            }
        } else {
            $file_path = $this->getTargetPathFor($file_name);
            $chunks = sizeof(glob("$file_path.dir.part/*.part"));
            if ($result = $chunks == $this->conf['chunks']) {
                $this->log("seems like last chunk ({$this->conf['chunk']}), 'cause there are $chunks out of {$this->conf['chunks']} *.part files in $file_path.dir.part.");
            }
        }

        return $result;
    }

    /**
     * Runs cb_check_file filter on the file if defined in config.
     *
     * @param string $file_path Path to the file to check
     * @return bool
     */
    protected function fileIsOK($path)
    {
        return !is_callable($this->conf['cb_check_file']) || call_user_func($this->conf['cb_check_file'], $path);
    }

    /**
     * Returns the size of the file in bytes for the given filename. Filename will be resolved
     * against target_dir value defined in the config.
     *
     * @param string $file_name
     * @return number|false
     */
    function getFileSizeFor($file_name)
    {
        return call_user_func($this->conf['cb_filesize'], getTargetPathFor($file_name));
    }

    /**
     * Resolves given filename against target_dir value defined in the config.
     *
     * @param string $file_name
     * @return string Resolved file path
     */
    function getTargetPathFor($file_name)
    {
        $target_dir = str_replace(array("/", "\/"), DIRECTORY_SEPARATOR, rtrim($this->conf['target_dir'], "/\\"));

        return $target_dir . DIRECTORY_SEPARATOR . $file_name;
    }

    /**
     * Sends out headers that prevent caching of the output that is going to follow.
     */
    function sendNoCacheHeaders()
    {
        // Make sure this file is not cached (as it might happen on iOS devices, for example)
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Handles CORS.
     *
     * @param array $headers Additional headers to send out
     * @param string [$origin='*'] Allowed origin
     */
    function sendCORSHeaders($headers = [], $origin = '*')
    {
        $allow_origin_present = false;

        if (!empty($headers)) {
            foreach ($headers as $header => $value) {
                if (strtolower($header) == 'access-control-allow-origin') {
                    $allow_origin_present = true;
                }
                header("$header: $value");
            }
        }

        if ($origin && !$allow_origin_present) {
            header("Access-Control-Allow-Origin: $origin");
        }

        // other CORS headers if any...
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }
    }

    /**
     * Cleans up outdated *.part files and directories inside target_dir.
     * Files are considered outdated if they are older than max_file_age hours.
     * (@see config options)
     */
    private function cleanup()
    {
        // Remove old temp files
        if (file_exists($this->conf['target_dir'])) {
            foreach (glob($this->conf['target_dir'] . '/*.part') as $tmpFile) {
                if (time() - filemtime($tmpFile) < $this->conf['max_file_age']) {
                    continue;
                }
                if (is_dir($tmpFile)) {
                    self::rrmdir($tmpFile);
                } else {
                    @unlink($tmpFile);
                }
            }
        }
    }

    /**
     * Sanitizes a filename replacing whitespace with dashes
     *
     * Removes special characters that are illegal in filenames on certain
     * operating systems and special characters requiring special escaping
     * to manipulate at the command line. Replaces spaces and consecutive
     * dashes with a single dash. Trim period, dash and underscore from beginning
     * and end of filename.
     *
     * @param string $filename The filename to be sanitized
     * @return string The sanitized filename
     * @author WordPress
     *
     */
    protected function sanitizeFileName($filename)
    {
        $special_chars = ["?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}"];
        $filename = str_replace($special_chars, '', $filename);
        $filename = preg_replace('/[\s-]+/', '-', $filename);
        $filename = trim($filename, '.-_');
        return $filename;
    }

    /**
     * Concise way to recursively remove a directory
     * @see http://www.php.net/manual/en/function.rmdir.php#108113
     *
     * @param string $dir Directory to remove
     */
    private function rrmdir($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->rrmdir($file);
            } else {
                unlink($file);
            }

        }
        rmdir($dir);
    }

    /**
     * PHPs filesize() fails to measure files larger than 2gb
     * @see http://stackoverflow.com/a/5502328/189673
     *
     * @param string $file Path to the file to measure
     * @return int
     */
    protected function filesize($file)
    {
        if (!file_exists($file)) {
            $this->log("cannot measure $file, 'cause it doesn't exist.");
            return false;
        }

        static $iswin;
        if (!isset($iswin)) {
            $iswin = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
        }

        static $exec_works;
        if (!isset($exec_works)) {
            $exec_works = (function_exists('exec') && !ini_get('safe_mode') && @exec('echo EXEC') == 'EXEC');
        }

        // try a shell command
        if ($exec_works) {
            $cmd = ($iswin) ? "for %F in (\"$file\") do @echo %~zF" : "stat -c%s \"$file\"";
            @exec($cmd, $output);
            if (is_array($output) && is_numeric($size = trim(implode("\n", $output)))) {
                $this->log("filesize obtained via exec.");
                return $size;
            }
        }

        // try the Windows COM interface
        if ($iswin && class_exists("COM")) {
            try {
                $fsobj = new COM('Scripting.FileSystemObject');
                $f = $fsobj->GetFile(realpath($file));
                $size = $f->Size;
            } catch (Exception $e) {
                $size = null;
            }
            if (ctype_digit($size)) {
                $this->log("filesize obtained via Scripting.FileSystemObject.");
                return $size;
            }
        }

        // if everything else fails
        $this->log("filesize obtained via native filesize.");
        return @filesize($file);
    }

    /**
     * Obtain the blocking lock on the specified file. All processes looking to work with
     * the same file will have to wait, until we release it (@param string $file_name File to lock
     * @see unlockTheFile).
     *
     */
    private function lockTheFile($file_name)
    {
        $file_path = $this->getTargetPathFor($file_name);
        $this->out = @fopen("$file_path.lock", 'w');
        @flock($this->out, LOCK_EX); // obtain blocking lock
    }

    /**
     * Release the blocking lock on the specified file.
     *
     * @param string $file_name File to lock
     */
    private function unlockTheFile($file_name)
    {
        $file_path = $this->getTargetPathFor($file_name);
        @fclose($this->out);
        @unlink("$file_path.lock");
    }

    /**
     * Reset private variables to their initial values.
     */
    private function reset()
    {
        $conf = $this->conf;
        $this->error = null;

        if (is_resource($this->out)) {
            fclose($this->out);
        }
    }

    /**
     * Log the message to the log_path, but only if debug is set to true.
     * Each message will get prepended with the current timestamp.
     *
     * @param string $msg
     */
    protected function log($msg)
    {
        if (!$this->conf['debug']) {
            return;
        }
        $msg = date("Y-m-d H:i:s") . ": $msg\n";
        file_put_contents($this->conf['log_path'], $msg, FILE_APPEND);
    }

}

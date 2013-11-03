<?php

/**
* 文件处理相关类
* @author lizhicheng <li_zhicheng@126.com>
*/
class CFilehelper
{

    public static function mkdir_r($dirName, $rights = 0777)
    {
        $dirs = explode('/', $dirName);
        $dir = '';
        
        foreach ($dirs as $part) {
            $dir .= $part . '/';
            if (! is_dir($dir) && strlen($dir) > 0)
                mkdir($dir, $rights);
        }
    }
    
    // suggested mode 'a' for writing to the end of the file
    public static function writeData($path, $mode, $data)
    {
        $fp = fopen($path, $mode);
        $retries = 0;
        $max_retries = 100;
        
        if (! $fp) {
            // failure
            return false;
        }
        
        // keep trying to get a lock as long as possible
        do {
            if ($retries > 0) {
                usleep(rand(1, 10000));
            }
            $retries += 1;
        } while (! flock($fp, LOCK_EX) and $retries <= $max_retries);
        
        // couldn't get the lock, give up
        if ($retries == $max_retries) {
            // failure
            return false;
        }
        
        // got the lock, write the data
        fwrite($fp, $data);
        // release the lock
        flock($fp, LOCK_UN);
        fclose($fp);
        // success
        return true;
    }
}

?>
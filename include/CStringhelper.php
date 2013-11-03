<?php

/**
 * 字符串处理相关类
 * @author lizhicheng <li_zhicheng@126.com>
 */
class CStringhelper
{

    /**
     * addslashes扩展
     *
     * @param $sting 字符串
     * @param $force 强制增加\
     */
    public static function addslashesex($string, $force = 0)
    {
        ! defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
        if (! MAGIC_QUOTES_GPC || $force) {
            if (is_array($string)) {
                foreach ($string as $key => $val) {
                    $string[$key] = self::addslashesex($val, $force);
                }
            } else {
                $string = addslashes($string);
            }
        }
        return $string;
    }

    public static function escapestr($string)
    {
        ! defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
        
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = self::escapestr($val);
            }
        } else {
            MAGIC_QUOTES_GPC && $string = stripslashes($string);
            $string = mysql_real_escape_string($string);
        }
        return $string;
    }

    public static function strauthcode($string, $operation, $key = '')
    {
        $key = md5($key ? $key : 'default key of strauthcode');
        $key_length = strlen($key);
        $string = $operation == 'DECODE' ? base64_decode($string) : substr(md5($string . $key), 0, 8) . $string;
        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        
        for ($i = 0; $i <= 255; $i ++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        
        for ($j = $i = 0; $i < 256; $i ++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        
        for ($a = $j = $i = 0; $i < $string_length; $i ++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        
        if ($operation == 'DECODE') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return str_replace('=', '', base64_encode($result));
        }
    }

    public static function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }

    public static function fixempty($str = null)
    {
        return empty($str) ? '&nbsp;' : htmlspecialchars(strtr($str, '`', ''));
    }

    public static function the_iconv($source_lang, $target_lang, $source_string = '')
    {
        static $chs = NULL;
        
        if ($source_lang == $target_lang || $source_string == '' || preg_match("/[\x80-\xFF]+/", $source_string) == 0) {
            return $source_string;
        }
        
        if ($chs === NULL) {
            require_once (APP_ROOT . '/include/iconv.inc.php');
            $chs = new Chinese(APP_ROOT);
        }
        
        return $chs->Convert($source_lang, $target_lang, $source_string);
    }

    public function breakWordsUtf8($string)
    {
        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string);
        return $t_string[0];
    }

    public static function cnSubStr($string, $sublen = 20, $start = 0, $code = 'UTF-8', $more = '...')
    {
        if ($code == 'UTF-8') {
            
            $t_string = self::breakWordsUtf8($string);
            
            if (count($t_string) - $start > $sublen)
                return join('', array_slice($t_string, $start, $sublen)) . $more;
            return join('', array_slice($t_string, $start, $sublen));
        } else {
            $start = $start * 2;
            $sublen = $sublen * 2;
            $strlen = strlen($string);
            $tmpstr = '';
            
            for ($i = 0; $i < $strlen; $i ++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    if (ord(substr($string, $i, 1)) > 129) {
                        $tmpstr .= substr($string, $i, 2);
                    } else {
                        $tmpstr .= substr($string, $i, 1);
                    }
                }
                if (ord(substr($string, $i, 1)) > 129)
                    $i ++;
            }
            if (strlen($tmpstr) < $strlen)
                $tmpstr .= $more;
            return $tmpstr;
        }
    }

    public static function cut_str($string, $sublen = 20)
    {
        if ($sublen >= strlen($string)) {
            return $string;
        }
        $s = "";
        for ($i = 0; $i < $sublen; $i ++) {
            if (ord($string{$i}) > 127) {
                $s .= $string{$i} . $string{++ $i};
                continue;
            } else {
                $s .= $string{$i};
                continue;
            }
        }
        return $s;
    }

    public static function getrandstr($length = 6, $mode = 0)
    {
        $str1 = '1234567890';
        $str2 = 'abcdefghijklmnopqrstuvwxyz';
        $str3 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str4 = '_';
        $str5 = '`~!@#$%^&*()-+=\\|{}[];:\'",./?';
        
        switch ($mode) {
            case '0':
                $str = $str1 . $str2 . $str3 . $str4;
                break;
            case '1':
                $str = $str1;
                break;
            case '2':
                $str = $str2;
                break;
            case '3':
                $str = $str3;
                break;
            case '4':
                $str = $str2 . $str3;
                break;
            case '5':
                $str = $str1 . $str2;
                break;
            case '6':
                $str = $str1 . $str3;
                break;
            case '7':
                $str = $str1 . $str2 . $str3;
                break;
            case '8':
                $str = $str1 . $str2 . $str3 . $str4 . $str5;
                break;
            default:
                $str = $str1 . $str2 . $str3 . $str4;
                break;
        }
        $string = '';
        for (; $length >= 1; $length --) {
            $position = rand() % strlen($str);
            $string .= substr($str, $position, 1);
        }
        return $string;
    }

    public static function replacebadwords(&$str)
    {
        $badwordsfile = APP_ROOT . '/include/badwords.php';
        
        if (file_exists($badwordsfile)) {
            $str = strtr($str, $badwordsfile);
        }
    }

    public static function delSpecialChars($str)
    {
        // 删除特殊字符        
        return $str;
    }

    /**
     * 获取一句话对应的拼音
     *
     * @param
     *            $words必须是utf8编码，如果是其他编码，请先转换成utf8
     *            
     * @return string $words对应的拼音
     *        
     */
    public static function pinyinUtf8($words = null)
    {
        if (empty($words))
            return null;
        $words = trim($words);
        // 忽略标点符号
        // 忽略特殊字符
        // $words = CStringhelper::delSpecialChars($words);
        // 处理多音词
        $duoyin = require APP_ROOT . '/include/duoyin.inc.php';
        foreach ($duoyin as $yin => $ci) {
            $words = strtr($words, array(
                $ci => $yin
            ));
        }
        // 分词成字
        $breakwords = CStringhelper::breakWordsUtf8($words);
        foreach ($breakwords as $n => $breakword) {
            // 忽略非中文字符
            if (! CValidator::isChinese($breakword)) {
                continue;
            }
            
            // 查找字典
            $lines = file(APP_ROOT . '/include/zidian.inc');
            foreach ($lines as $line_num => $line) {
                $info = explode(' ', $line);
                $pinyin = $info[0];
                $string = trim($info[1]);
                if (strstr($string, $breakword)) {
                    // 找到对应的拼音
                    $words = strtr($words, array(
                        $breakword => $pinyin
                    ));
                }
            }
        }
        return $words;
    }
}

?>
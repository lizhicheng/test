<?php

/**
* 验证处理类
* @author lizhicheng <li_zhicheng@126.com>
*/
class CValidator
{

    /**
     * 验证是否为Email
     *
     * @param $var
     * @param $allowName false 不允许<name>xxx@xxx.com格式 true 允许
     * @return bool
     */
    public static function isEmail($var, $allowName = false)
    {
        $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
        $fullPattern = '/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';
        $result = is_string($var) && strlen($var) <= 254 && (preg_match($pattern, $var) || $allowName && preg_match($fullPattern, $var));
        
        return $result;
    }
    
    /**
     * 验证是否为中文
     *
     * @param $string
     * @param $all 1 全部为中文 其他 包含中文
     * @return bool
     */
    public static function isChinese($string, $all = 1)
    {
        $pattern = $all == 1 ? '/^[\x7f-\xff]+$/' : '/[\x7f-\xff]/';
        if (preg_match($pattern, $string))
            return true;
        return false;
    }
}

?>
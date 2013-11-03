<?php

/**
* HTTP相关类
* @author lizhicheng <li_zhicheng@126.com>
*/
class CHttphelper
{

    public static function iptype1()
    {
        if (getenv("HTTP_CLIENT_IP")) {
            return getenv("HTTP_CLIENT_IP");
        } else {
            return "none";
        }
    }

    public static function iptype2()
    {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            return getenv("HTTP_X_FORWARDED_FOR");
        } else {
            return "none";
        }
    }

    public static function iptype3()
    {
        if (getenv("REMOTE_ADDR")) {
            return getenv("REMOTE_ADDR");
        } else {
            return "none";
        }
    }

    public static function ip()
    {
        $ip1 = self::iptype1();
        $ip2 = self::iptype2();
        $ip3 = self::iptype3();
        if (isset($ip1) && $ip1 != "none" && $ip1 != "unknown") {
            return $ip1;
        } elseif (isset($ip2) && $ip2 != "none" && $ip2 != "unknown") {
            return $ip2;
        } elseif (isset($ip3) && $ip3 != "none" && $ip3 != "unknown") {
            return $ip3;
        } else {
            return "none";
        }
    }

    public static function getsitehost()
    {
        $host = strtr(isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''), array(
            'www.' => ''
        ));
        return $host;
    }

    public static function getparam($name, $default = null)
    {
        return ! empty($_GET[$name]) ? $_GET[$name] : (! empty($_POST[$name]) ? $_POST[$name] : $default);
    }

    public static function getcookieex($name, $prefix = 1)
    {
        $cookievar = $prefix ? Li::config('cookie_prefix') . $name : $name;
        return $_COOKIE[$cookievar];
    }

    public static function setcookieex($name, $value, $life = 0, $prefix = 1)
    {
        $cookievar = $prefix ? Li::config('cookie_prefix') . $name : $name;
        setcookie($cookievar, $value, $life ? time() + $life : 0, Li::config('cookie_path'), Li::config('cookie_domain'), $_SERVER['SERVER_PORT'] == 443 ? 1 : 0);
        $_COOKIE[$cookievar] = $value;
    }
}

?>
<?php

/**
* 时间日期相关功能类
* @author lizhicheng <li_zhicheng@126.com>
*/
class CDatehelper
{
    public static function getmicrotime()
    {
    	  list($usec, $sec) = explode(' ',microtime());
    	  return ((float)$usec + (float)$sec);
    }	

    public static function time2date($time)
    {
        if (empty($time))
            return null;
        return date("Y-m-d H:i:s", $time);
    }

    public static function format_date($date = '0000-00-00', $dateformat = '%s&nbsp;%s&nbsp;%s')
    {
        $date = explode('-', $date);
        $y = $date[0];
        $m = $date[1];
        $d = $date[2];
        
        $datetext = sprintf($dateformat, $y, $m, $d);
        
        return $datetext;
    }

    public static function format_time($time = '00:00:00')
    {
        $time = substr($time, 0, 5);
        return $time;
    }

    /**
     * 根据年龄计算生日范围
     *
     * @param $age 年龄
     * @param $n=1 范围上限 $n=2范围下限
     * @return string
     */
    public static function age2birth($age, $n = 1)
    {
        $time = getdate();
        $year = $time['year'] - $age;
        $month = $time['mon'];
        $day = $time['mday'];
        $n == 1 && $str = "{$year}-{$month}-{$day}";
        $year --;
        $n == 2 && $str = "{$year}-{$month}-{$day}";
        return $str;
    }

    /**
     * 根据生日计算年龄
     *
     * @param $birth 生日
     * @return int
     */
    public static function birth2age($birth = '0000-00-00')
    {
        if (empty($birth))
            return '*';
        
        $year = explode('-', $birth);
        $time = getdate();
        $a = @mktime(0, 0, 0, $time[mon], $time[mday], $time[year]);
        $b = @mktime(0, 0, 0, $year[1], $year[2], $time[year]);
        $age = $time[year] - $year[0];
        if ($a < $b) {
            $age --;
        }
        return $age;
    }

    /**
     * 计算某个日期至今的天数
     *
     * @param $date 日期
     * @return int
     */
    public static function date_day($date = '0000-00-00')
    {
        $day = explode('-', $date);
        $time = @mktime(0, 0, 0, $day[1], $day[2], $day[0]);
        $nowtime = time();
        $days = floor(($nowtime - $time) / (24 * 3600));
        return $days;
    }

    /**
     * 计算星座
     * 
     * @param $bm 出生月份
     * @param $bd 出生日
     * @return int
     */
    public static function calcastro($bm, $bd)
    {
        $astro = $bm + $bd * 0.01;
        
        $user_astro = 0;
        // if($astro >= 3.21 && $astro <= 4.19)$user_astro = 0;
        if ($astro >= 4.20 && $astro <= 5.20)
            $user_astro = 1;
        if ($astro >= 5.21 && $astro <= 6.21)
            $user_astro = 2;
        if ($astro >= 6.22 && $astro <= 7.22)
            $user_astro = 3;
        if ($astro >= 7.23 && $astro <= 8.22)
            $user_astro = 4;
        if ($astro >= 8.23 && $astro <= 9.22)
            $user_astro = 5;
        if ($astro >= 9.23 && $astro <= 10.22)
            $user_astro = 6;
        if ($astro >= 10.23 && $astro <= 11.21)
            $user_astro = 7;
        if ($astro >= 11.22 && $astro <= 12.21)
            $user_astro = 8;
        if (($astro >= 12.22 && $astro <= 12.31) || ($astro >= 1.1 && $astro <= 1.19))
            $user_astro = 9;
        if ($astro >= 1.20 && $astro <= 2.18)
            $user_astro = 10;
        if ($astro >= 2.19 && $astro <= 3.20)
            $user_astro = 11;
        
        return $user_astro;
    }

    /**
     * 计算冒泡时间
     *
     * @param $post_time 冒泡unix时间戳
     * @param $format 显示格式
     * @return string
     */
    public static function popo($post_time, $format = array(
			'popo_years' => 'about %f years ago',
			'popo_months' => 'about %f months ago',
			'popo_weeks' => 'about %f weeks ago',
			'popo_days' => 'about %f days ago',
			'popo_hours' => 'about %f hours ago',
			'popo_minutes' => 'about %f minutes ago',
			'popo_seconds' => 'about %f seconds ago'))
    {
        $now_time = time();
        $times = $now_time - $post_time;
        
        $seconds = $times;
        $minutes = $seconds / 60;
        $hours = $minutes / 60;
        $days = $hours / 24;
        $weeks = $days / 7;
        $months = $days / 30;
        $years = $days / 365;
        
        if ($years > 1) {
            $str = sprintf($format['popo_years'], ceil($years));
            return $str;
        }
        
        if ($months > 1) {
            $str = sprintf($format['popo_months'], ceil($months));
            return $str;
        }
        
        if ($weeks > 1) {
            $str = sprintf($format['popo_weeks'], ceil($weeks));
            return $str;
        }
        
        if ($days > 1) {
            $str = sprintf($format['popo_days'], ceil($days));
            return $str;
        }
        
        if ($hours > 1) {
            $str = sprintf($format['popo_hours'], ceil($hours));
            return $str;
        }
        
        if ($minutes > 1) {
            $str = sprintf($format['popo_minutes'], ceil($minutes));
            return $str;
        }
        
        if ($seconds > 1) {
            $str = sprintf($format['popo_second'], ceil($seconds));
            return $str;
        }
        
        return '';
    }
}

?>
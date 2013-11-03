<?php

/**
* Ajax APP
* @author lizhicheng <li_zhicheng@126.com>
*/
class CAjaxApp extends CApplication
{

    public function __construct()
    {
        $errors = array(
            0 => 'action not allowed',
            - 1 => 'invalid security code',
            - 2 => 'post var needed'
        );
        
        $checksubmit = $this->checkSubmit($_POST['action']);
        
        if ($checksubmit != 1) {
            exit($errors[$checksubmit]);
        }
        
        $this->pdo->setTableName('members');
        
        parent::__construct();
    }

    /**
     * 检查用户名是否存在
     *
     * @param $uname 用户名            
     * @return void
     */
    public function Onckuname($uname)
    {
        $u = $this->pdo->getOneRow('`username` = ?', array(
            $uname
        ), '`user_id`');
        echo intval($u['user_id']);
    }

    /**
     * 检查email是否存在
     *
     * @param $email 邮件地址            
     * @return void
     */
    public function Onckemail($email)
    {
        $u = $this->pdo->getOneRow('`user_email` = ?', array(
            $email
        ), '`user_id`');
        echo intval($u['user_id']);
    }

    public function Onckvcode()
    {
        echo '0';
    }

    public function Onsignup($email, $username, $loginpwd, $gender, $birth, $province, $city, $unionid, $tj)
    {
        $timestamp = time();
        $ipadd = CHttphelper::ip();
        if ($ipadd != 'unknown' && $ipadd != 'none') {
            $predaytime = $timestamp - 86400;
            $ips = $this->pdo->getOneRow('`ipadd` = ? and `jointime` > ?', array(
                $ipadd,
                $predaytime
            ), 'count(*) as total');
            if ($ips['total'] > Li::config('maxsignupnumperday')) {
                echo 'one ip too much regs one day';
                return;
            }
        }
        
        $valid = true;
        $email = trim($email);
        $loginpwd = trim($loginpwd);
        $username = trim($username);
        $valid &= ! empty($email);
        $valid &= ! empty($loginpwd);
        $valid &= ! empty($username);
        $valid &= !empty($gender);
        $valid &= !empty($birth);
        $valid &= !empty($province);
        $valid &= !empty($city);
                
        $u = $this->pdo->getOneRow('`username` = ?', array(
            $username
        ), '`user_id`');
        $valid &= (intval($u['user_id']) == 0);
        
        $u = $this->pdo->getOneRow('`user_email` = ?', array(
            $email
        ), '`user_id`');
        $valid &= (intval($u['user_id']) == 0);
        
        if (! $valid) {
            echo '1';
            return;
        }
        
        $username = htmlspecialchars($username);
        
        $unioncheck = 0;
        $unionid = intval($unionid);
        if (! empty($unionid)) {
            $tablename = $this->pdo->getTablePrefix() . 'union_members';
            $unioninf = $this->pdo->getOneRow('`union_id`=?', array(
                $unionid
            ), '`union_id`,`union_type`,`checkresult`,`regtime`', $tablename);
            ! empty($unioninf['union_id']) && $unioninf['checkresult'] == 1 && $unioncheck = 1;
        }
        
        if ($unioncheck == 1) {
            // 增加CPA数据
        } else {
            $unionid = 0;
        }
        
        $salt = substr(uniqid(rand()), - 6);
        $password = md5(md5($loginpwd) . $salt);
        
        $tablename = $this->pdo->getTableName();
        
        $sql = "insert into `$tablename` (`username`,`password`,`pwdsalt`,`user_gender`,`user_birth`,`user_inplace`,`user_incity`,`user_email`,`jointime`,`tj`,`unionid`,`ipadd`) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->pdo->pquery($sql, array(
            $username,
            $password,
            $salt,
            $gender,
            $birth,
            $province,
            $city,
            $email,
            $timestamp,
            $tj,
            $unionid,
            $ipadd
        ));
        $uid = $this->pdo->lastInsertId('user_id');
        
        CHttphelper::setcookieex('logininf', CStringhelper::strauthcode("$uid\t$password", 'ENCODE'), Li::config('cookie_time'));
        
        $uinf = array(
            'user_id' => $uid,
            'username' => $username,
            'face' => '',
            'gender' => $gender,
            'birth' => $birth,
            'province' => $province,
            'city' => $city,
            'money' => 0,
            'vip' => 0
        );
        
        $json = json_encode($uinf);
        echo $json;
    }
    
    public function Onlogin($loginname, $loginpwd)
    {
        $password = md5($loginpwd);
        $checklogin = false;
        
        $uinf = $this->pdo->getOneRow('`user_email` = ?', array($loginname), '`user_id`,`username`,`password`,`pwdsalt`,`user_gender`,`user_birth`,`user_inplace`,`user_incity`,`user_face`,`user_money`,`vip`,`viptime`,`vipdays`');
        
        $upass = $uinf['password'];
        $salt  = $uinf['pwdsalt'];
        $uid   = $uinf['user_id'];
        
        if(!empty($salt) && $upass == md5(md5($loginpwd).$salt)) $checklogin = true;
        if(empty($salt) && $upass == $password) $checklogin = true;
        
        unset($uinf['password'],$uinf['pwdsalt']);
        
        if(!$checklogin) {
            echo '1';
            return;
        }
        
        $timestamp = time();
        $ipadd = CHttphelper::ip();
        
        $tablename = $this->pdo->getTableName();
        
        $sql = "UPDATE `$tablename` SET `last_visit_time`=?,`ipadd`=?,`loginnum`=`loginnum`+1 WHERE user_id=?";
        $this->pdo->pquery($sql, $timestamp, $ipadd, $uid);
        
        CHttphelper::setcookieex("logininf", CStringhelper::strauthcode("$uid\t$upass", 'ENCODE'), Li::config('cookie_time'));
        
        $m = new CMembers();
        $m->setdata($uinf);
        $vip = $m->isvip() ? 1 : 0;
        
        $u = array(
            'user_id' => $uid,
            'username' => $uinf['username'],
            'face' => $uinf['user_face'],
            'gender' => $uinf['user_gender'],
            'birth' => $uinf['user_birth'],
            'province' => $uinf['user_inplace'],
            'city' => $uinf['user_incity'],
            'money' => $uinf['user_money'],
            'vip' => $vip,
        );
        
        $json = json_encode($u);
        echo $json;        
    }
    
    public function Onlogout()
    {
        CHttphelper::setcookieex('logininf', '', 0-Li::config('cookie_time'));
        echo '0';
    }
}
?>
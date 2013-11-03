<?php

class CMembers
{

    private $id;

    private $data;
    
    private $pdo;

    public function __construct(& $pdo)
    {
        $this->id = - 1;
        $this->data = array(
            'user_id' => - 1,
            'username' => 'guest',
            'face' => '',
            'gender' => '',
            'birth' => '',
            'province' => '',
            'city' => '',
            'money' => 0,
            'vip' => 0
        );
        $this->pdo = $pdo;
        $this->pdo->setTableName('members');
    }

    public function setdata($data)
    {
        $this->data = $data;
        $this->id = $data['user_id'];
    }

    public function getdata()
    {
        if ($this->id == - 1) {
            $logininf = CHttphelper::getcookieex('logininf');
            
            list ($user_id, $password) = empty($logininf) ? array(
                - 1,
                ''
            ) : explode("\t", CStringhelper::strauthcode($logininf, 'DECODE'));
            
            if ($user_id != - 1) {
                $u = $this->pdo->getOneRow('`user_id=? and password=?`', array(
                    $user_id,
                    $password
                ), '`user_id`,`username`,`user_face`,`user_gender`,`user_birth`,`user_inplace`,`user_incity`,`user_money`,`vip`,`viptime`,`vipdays`');
                $this->id = $user_id;
                $this->data = array(
                    'user_id' => $u['user_id'],
                    'username' => $u['username'],
                    'face' => $u['user_face'],
                    'gender' => $u['user_gender'],
                    'birth' => $u['user_birth'],
                    'province' => $u['user_inplace'],
                    'city' => $u['user_incity'],
                    'money' => $u['user_money'],
                    'vip' => $u['vip']
                );
            }
        }
        return $this->data;
    }

    public function islogin()
    {
        if ($this->id == - 1)
            return false;
        return true;
    }

    public function isvip()
    {
        if ($this->data['vip'] == 0)
            return false;
        
        if ($this->data['viptime'] + $this->data['vipdays'] * 24 * 3600 - time() < 0) {
            $this->data['vip'] = $this->data['viptime'] = $this->data['vipdays'] = 0;
            $tablename = $this->pdo->getTableName();
            $sql = "update `$tablename` set `vip`='0', `viptime`='0', `vipdays`='0' where `user_id`=?";
            $this->pdo->pquery($sql, $this->data['user_id']);
            return false;
        }
        return true;
    }
}
?>
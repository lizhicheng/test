<?php
class CTestApp extends CApplication {
    
    public function Ondefault($myname)
    {
        echo $myname, '<br />';
        echo 'test app default action';
        echo '<br />', $_SERVER['QUERY_STRING'], '<br />', print_r($_GET, true);
    }
    
    public function Ontest()
    {
    	echo ip2long('127.0.0.1');exit;
    	$pdo = new CMyPDO(1);
    	$step = CHttphelper::getparam('step');
    	$size = 500;
    	$offset = intval($size * $step);
    	if($offset == 1460500) exit('done!');
    	$sql = "select * from yuehui_users where user_id>=
    	(select user_id from yuehui_users order by user_id asc limit $offset,1) limit 0,$size";
    	$q = $pdo->pquery($sql);
      $result = $q->fetchAll(PDO::FETCH_ASSOC);
    	foreach($result as $line) {
    		$fields = '';
    		$values = '';
    		foreach($line as $field => $value) {
    			$fields .= $field . ',';
    			$values .= '\'' . CStringhelper::addslashesex($value) . '\',';
    		}
    		$fields = substr($fields,0,strlen($fields)-1);
    		$values = substr($values,0,strlen($values)-1);
    		$newsql = "insert into yuehui_users2 ($fields) values ($values)";
    		$pdo->pquery($newsql);
    	}
    	echo $step;
    	$step++;
    	$link = 'index.php?app=test&action=test&step='.$step;
    	echo '<META HTTP-EQUIV="Refresh" CONTENT="2; URL=' . $link . '">';
    	exit;
    	
    	$h = new Flexihash();
    	$h->addTarget('192.168.0.1');
    	$h->addTarget('192.168.0.2');
    	$h->addTarget('192.168.0.3');
    	
    	echo $h->lookup('a'),'<br/>';
    	echo $h->lookup('b'),'<br/>';
    	echo $h->lookup('c'),'<br/>';
    	echo $h->lookup('1001'),'<br/>';
    	echo $h->lookup('1002'),'<br/>';
    	echo $h->lookup('1003'),'<br/>';    	
    	
    	exit;
        if ($this->checkSubmit($_POST['submit']) == 1) {
            $title = CHttphelper::getparam('title');
            $title_pinyin = CStringhelper::pinyinUtf8($title);
            $sql = "insert into table_test (title,title_pinyin) values (?, ?)";
            $q = $this->pdo->pquery($sql, array($title, $title_pinyin));
            echo $this->pdo->lastInsertId('id');
        }
    
        //Li::log('test log', __FILE__, __LINE__);
    
        $this->tpl->set('test', array('str' => '我是www"'));
    
        $sql = 'select * from table_test where id > ? ';
        $p = $this->pdo->getPage($sql, 2, array(2));
    
        $this->tpl->set(array(
            'contents' => $p->field,
            'navbar' => $p->navbar())
        );
    
        /*
         * $id = CHttphelper::getparam('id'); $q = $this->pdo->pquery("select * from table_test where id='?'", $id); var_dump($q->fetch(PDO::FETCH_ASSOC));
        */
        $this->tpl->parse('test.html');
        
        $start_time = CDatehelper::getmicrotime();
        
        $pdo = new CMyPDO(1);
        $sql = 'select user_id from yuehui_users where user_gender = ? 
            and user_inplace=? 
            and user_incity=?
            limit 0,100000';
        $q = $pdo->pquery($sql, array(1,1,101));
        $result = $q->fetchAll(PDO::FETCH_ASSOC);
        
        $end_time = CDatehelper::getmicrotime();
        
        echo $end_time-$start_time, '<br />';
        
        echo memory_get_usage() , '<br />';
        
        echo time();
    }
        
}
?>
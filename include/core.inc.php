<?php
@error_reporting(E_ALL & ~ E_NOTICE);
@set_magic_quotes_runtime(0);

define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

Li::init();

/**
 * 基类
 *
 * @author lizhicheng <li_zhicheng@126.com>
 *        
 */
class CBase
{

    protected static $config = array();

    protected static $lang = array();
    
    /**
     * 自动加载类
     *
     * @param $classname 类名            
     * @return void
     */
    public static function autoload($classname)
    {
        $folds['core'] = '/include/';
        $folds['apps'] = '/class/';
        
        foreach ($folds as $key => $fold) {
            $filepath = APP_ROOT . $fold . $classname . '.php';
            if (file_exists($filepath)) {
                require $filepath;
            }
        }
    }
    
    /**
     * 读取配置
     *
     * @param $name 配置名称            
     * @return mix 配置值
     */
    public static function config($name)
    {
        if (empty(self::$config[$name]))
            self::$config = require APP_ROOT . '/config.php';
        return self::$config[$name];
    }
    
    /**
     * 加载语言
     *
     * @param $key 键名
     * @param $page 页面
     * @return string
     */
    public static function lang($key, $page = 'core')
    {
        if (empty(self::$lang[$key])) {
            $userlang = CHttphelper::getparam('lang', 'zh-cn');
            self::$lang = require APP_ROOT . '/lang/lang_' . $userlang . '_' . $page . '.php';
        }
        return self::$lang[$key];
    }
    
    /**
     * 记录日志
     *
     * @param $data 日志数据
     * @param $filename 脚本文件名
     * @param $linenum 程序所在行
     * @return void
     */
    public static function log($data, $filename, $linenum)
    {
        $dirfromdate = CDatehelper::format_date(date('Y-m-d'), '%s/%s/%s');
        $logdir = 'data/log/' . $dirfromdate;
        CFilehelper::mkdir_r($logdir);
        $logpath = $logdir . '/log.txt';
        $data = 'Time:' . date('Y-m-d H:i:s') . ' File:' . $filename . ' Line:' . $linenum . ' Url:' . $_SERVER['REQUEST_URI'] . ' ' . $data . PHP_EOL;
        
        CFilehelper::writeData($logpath, 'a', $data);
    }

    /**
     * 创建一个Application
     *
     * @param $appname application名
     * @return object
     */
    public static function createApp($appname = null)
    {
        if (empty($appname)) {
            
            $apps = self::config('apps');
            
            $app = CHttphelper::getparam('app', 'default');
            
            $appname = ! empty($apps[$app]) ? $apps[$app] : $apps['default'];
        }
        return new $appname();
    }

    /**
     * 一些初始化操作
     */
    public static function init()
    {
        if (PHP_VERSION < '5.1.2') {
            exit('PHP version >= 5.1.2 needed');
        }
        // 使用PDO时请关闭MAGIC_QUOTES_GPC
        if (MAGIC_QUOTES_GPC) {
            exit('please turn off MAGIC_QUOTES_GPC in php.ini');
        }
        
        // PHP5 >= PHP5.1.2
        spl_autoload_register('CBase::autoload');
        
        // 设置时区
        @ini_set('date.timezone', Li::config('datetimezone'));        
        
        if (CHttphelper::getparam('nocacheheaders') == 1) {
            @header("Expires: 0");
            @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0");
            @header("Pragma: no-cache");
        } else {
            @header("Expires: -1");
            @header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
            @header("Pragma: no-cache");
        }
        
        @header("Content-Type: text/html; charset=utf-8");
        
        if (function_exists('ob_gzhandler')) {
            ob_end_clean();
            ob_start('ob_gzhandler');
        }
        
        // 使用PDO无需以下addslashes操作
    }
}

class Li extends CBase
{
}

/**
 * Application类
 *
 * @author lizhicheng <li_zhicheng@126.com>
 *        
 */
abstract class CApplication
{

    protected $tpl;

    protected $pdo;

    public function __construct()
    {
        require APP_ROOT . '/include/vant.inc.php';
        $this->tpl = new Vant();
        $this->pdo = new CMyPDO();
    }
    
    /**
    * 运行Application
    *  
    * @return void
    */
    public function run()
    {
        $this->getVarsFromUrl();
        
        $action = CHttphelper::getparam('action', 'default');
        
        $actionMethod = 'On' . $action;
        
        if (is_callable(array(
            $this,
            $actionMethod
        ))) {
            $className = get_class($this);
            $classReflector = new ReflectionClass($className);
            $methodReflector = $classReflector->getMethod($actionMethod);
            if ($methodReflector->getNumberOfParameters() == 0) {
                $this->$actionMethod();
            } else {
                $methodParameters = $methodReflector->getParameters();
                foreach ($methodParameters as $param) {
                    $paramName = $param->getName();
                    $args[] = CHttphelper::getparam($paramName);
                }
                $methodReflector->invokeArgs($this, $args);
            }
        } else {
            include APP_ROOT . '/static/404.html';
            @header('HTTP/1.1 404 Not Found');
            @header('Status:404 Not Found');
        }
    }

    /**
     * 根据网址获取GET变量
     *
     * 需要配置rewrite规则：
     * /appName/actionName/others... /index.php?app=$appName&action=$actionName&var=$others
     * others... 格式：key1_val1/key2_val2/...
     */
    public function getVarsFromUrl()
    {
        $vars = explode('/', $_GET['var']);
        
        foreach ($vars as $var) {
            if (! empty($var)) {
                $querys = explode('_', $var);
                $getname = $querys[0];
                if (! empty($getname)) {
                    $_GET[$getname] = $querys[1];
                }
            }
        }
    }

    /**
     * 生成rewrite网址
     *
     * @return string
     */
    function genRewriteUrl()
    {
        return strtr(sprintf('/%s/%s/%s/', $_GET['app'], $_GET['action'], $_GET['var']), array(
            '//' => '/'
        ));
    }

    /**
     * 检查提交信息
     *
     * @param $var 查询变量
     * @param $isget 是否GET方式            
     * @return int 检查结果
     */
    public function checkSubmit($var, $isget = false)
    {
        if (empty($var))
            return - 2;
        
        if (isset($_POST['vcode'])) {
            require APP_ROOT . '/include/securimage/securimage.php';
            $image = new Securimage();
            if ($image->check($_POST['vcode']) == false)
                return - 1;
        }
        
        if ($isget || ($_SERVER['REQUEST_METHOD'] == 'POST' && (empty($_SERVER['HTTP_REFERER']) || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {
            return 1;
        }
        return 0;
    }

    /**
     * 输出系统提示
     *
     * @param $msg 提示内容            
     * @param $link 跳转链接            
     * @param $mode 跳转模式
     *            1直接跳转 2返回上页
     * @param $just 待定参数            
     * @return void
     */
    public function sysmsg($msg = '', $link = '', $mode = 1, $just = 0)
    {
        $exit = 0;
        if ($link != '' && $mode == 1) {
            $msg .= '<META HTTP-EQUIV="Refresh" CONTENT="3; URL=' . $link . '">';
            $exit = 1;
        }
        
        if ($link == '' && $mode == 1) {
            $exit = 1;
        }
        
        if ($mode == 2) {
            $msg .= '<a href="javascript:void();" onclick="javascript:window.history.back();">' . Li::lang('hisback') . '</a>';
            $exit = 1;
        }
        
        $this->tpl->set(array(
            'link' => $link,
            'mode' => $mode,
            'just' => $just,
            'msg' => $msg
        ));
        
        $this->tpl->parse('sysmsg.html');
        if ($exit == 1)
            exit();
    }
}

/**
 * PDO操作扩展类
 * 支持PDO需要PHP5.1以上版本
 *
 * @author lizhicheng <li_zhicheng@126.com>
 */
class CMyPDO extends PDO
{

    private $qcache; // prepared query cache
    private $table_prefix; // table prefix
    private $table_name; // table name
    
    /**
     * 创建PDO类对象
     *
     * @param $serverno 服务器编号            
     * @param $servertype 服务器类型            
     * @return object PDO对象
     */
    public function __construct($serverno = 0, $servertype = 'mysql', $driveroptions = array())
    {
        try {
            if ($servertype == 'mysql') {
                $mysqlservers = Li::config('mysqlservers');
                $dbhost = $mysqlservers[$serverno]['dbhost'];
                $dbname = $mysqlservers[$serverno]['dbname'];
                $dbuser = $mysqlservers[$serverno]['dbuser'];
                $dbpwd = $mysqlservers[$serverno]['dbpwd'];
                $this->setTablePrefix($mysqlservers[$serverno]['table_prefix']);
            }
            $dsn = $servertype . ':host=' . $dbhost . ';dbname=' . $dbname;
            parent::__construct($dsn, $dbuser, $dbpwd, $driveroptions);
            if ($servertype == 'mysql')
                $this->myinit();
        } catch (PDOException $e) {
            exit('creat PDO object error:' . $e->getMessage());
        }
        $this->querynum = 0;
    }

    public function myinit()
    {
        $dbversion = $this->dbversion();
        
        if ($dbversion > '4.1') {
            $this->exec("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
        }
        if ($dbversion > '5.0.1') {
            $this->exec("SET sql_mode=''");
        }
    }

    public function dbversion()
    {
        return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
    }
    
    public function setTablePrefix($table_prefix)
    {
        $this->table_prefix = $table_prefix;
    }

    public function getTablePrefix()
    {
        return $this->table_prefix;
    }
    
    public function setTableName($table_name)
    {
        $this->table_name = $this->table_prefix . $table_name;
    }
    
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * PDO执行SQL语句
     *
     * @param $sql 预处理的SQL语句            
     * @param $input_params 参数列表数组            
     * @return bool
     */
    public function pquery($sql, $input_params = array())
    {
        if (is_object($this->qcache[$sql])) {
            $query = $this->qcache[$sql];
        } else {
            $query = $this->prepare($sql);
            $this->qcache[$sql] = $query;
        }
        
        // $args = func_get_args();
        // array_shift($args);
        
        if (! $query->execute(/*$args*/$input_params)) {
            $errorinfo = 'SQL excute error:' . $sql . '<br />';
            $errorinfo .= print_r($query->errorInfo(), true);
            exit($errorinfo);
        }
        
        // do Transaction:
        /*
         * try { $this->beginTransaction(); if (! $query->execute($args)) { $errorinfo = print_r($query->errorInfo(), true); throw new PDOException($errorinfo); } $id = $this->lastInsertId(); $this->commit(); } catch (PDOException $e) { $this->rollback(); echo 'Transaction error:' . $e->getMessage() . '<br />'; }
         */
        return $query;
    }
    
    // count query numbers
    public function queryNum()
    {
        return count($this->qcache);
    }

    /**
     * 根据sql语句取得所有数据,取得的数据放在翻页类PageTurn中
     *
     * @param $sql sql语句            
     * @param $maxnum 每页显示数            
     * @param $query_vars 执行sql时所需变量数组            
     * @param $key 有多个翻页时作为区分标识            
     * @param $parts 总数量大时只取出部分数据            
     * @param $form_vars 翻页时需要传递的表单字段名(数组)            
     * @return object 翻页类对象
     */
    public function getPage($sql, $maxnum = 0, $query_vars = array(), $key = '', $parts = 0, $form_vars = '')
    {
        $maxnum = intval($maxnum);
        $parts = intval($parts);
        
        if ($parts > 0) {
            $totalnum = $parts;
        } elseif (! isset($_GET['totalnum' . $key])) {
            $q = $this->pquery($sql, $query_vars);
            $totalnum = $q->rowCount();
        } else {
            $totalnum = $_GET['totalnum' . $key];
        }
        
        require APP_ROOT . '/include/pageturn.inc.php';
        
        $pageturn = new PageTurn($totalnum, $maxnum, $key, $form_vars);
        
        $startnum = intval($pageturn->startnum);
        $maxnum = intval($pageturn->maxnum);
        
        $newsql = $sql . " limit $startnum , $maxnum";
        
        $query = $this->pquery($newsql, $query_vars);
        $pageturn->field = & $query->fetchAll(PDO::FETCH_ASSOC);
        
        return $pageturn;
    }
    
    /**
    * 取出一行数据  
    * @param $sql_where sql查询where子句
    * @param $query_vars sql查询变量数组
    * @param $fields sql查询字段
    * @param $table_name 数据表名
    * @return mixed
    */
    public function getOneRow($sql_where, $query_vars = array(), $fields = null, $table_name = null)
    {
        empty($table_name) && $table_name = $this->table_name;
        empty($fields) && $fields = '*';
        
        $sql = "select $fields from `$table_name` where $sql_where";
        
        $q = $this->pquery($sql, $query_vars);
        return $q->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<?php
return array(
    'datetimezone' => 'Asia/Shanghai',
    'lang' => 'zh-cn',
    'code_auth_key' => 'dsfemhylhs2sptdsr',
    'cookie_prefix' => 'cookie_',
    'cookie_path' => '/',
    'cookie_domain' => 'example.com',
    'cookie_time' => 365*24*3600,
    'mysqlservers' => array(
        array(
            'dbhost' => 'localhost',
            'dbuser' => 'root',
            'dbpwd' => 'li123456',
            'dbname' => 'test',
            'table_prefix' => 'tb_'
        ),
        array(
            'dbhost' => 'localhost',
            'dbuser' => 'root',
            'dbpwd' => 'li123456',
            'dbname' => 'yuehui0',
            'table_prefix' => ''        
        )
    ),
    'smtp' => array(
        'host' => 'smtp.yeah.net',
        'from' => 'payservice@yeah.net',
        'fromname' => 'payservice',
        'username' => 'payservice@yeah.net',
        'password' => 'n0m7aA0m8W10s5a6'
    ),
    'maxsignupnumperday' => 3,
    'apps' => array(
        'default' => 'CTestApp',
        'test' => 'CTestApp',
        'ajax' => 'CAjaxApp',
        'member' => 'CMembersApp'
    )
);

?>

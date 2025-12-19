<?php
//传奇游戏配置文件
//MySQL 配置
$mysql_host = '127.0.0.1';//MySQL IP地址
$mysql_port = '3306';//MySQL 端口
$mysql_user_name = 'root';//MySQL 本地用户名
$mysql_passwd = '123456';//MySQL 本地密码
$mysql_dbname = 'wapgame';//MySQL 账号密码数据表名
$mysql_db_user = 'wapgame';//wapgame数据库用户名
$mysql_db_pass = '123456';//wapgame数据库密码
//游戏区服配置
$mysql_game_area_db_name = array(0 => 'sg_mp001');//MySQL 区服数据表名
$mysql_game_user = 'sg_mp001';//sg_mp001数据库用户名
$mysql_game_pass = '123456';//sg_mp001数据库密码
//REDIS 配置
$re_host = "127.0.0.1";
$re_port = "6379";
$re_pass = "sants.cn";
$re_db_name = array("sg_mp001" => "11");
//游戏log记录配置
$system_log = true;
$system_func_log = false;
$system_item_log = true;
//游戏是否停机更新
$system_update = false;

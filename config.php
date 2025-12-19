<?php
//游戏配置文件
//MySQL 配置
$mysql_host = 'p:127.0.0.1';//MySQL IP地址
$mysql_port = '3306';//MySQL 端口
$mysql_user_name = 'root';//MySQL 用户名
$mysql_passwd = '123456';//MySQL 用户密码
$mysql_dbname = 'wapgame';//MySQL 账号密码数据表名
//REDIS 配置
$re_host = "127.0.0.1";
$re_port = "6379";
$re_pass = "sants.cn";


//获取来源社区
function get_community($user_id)
{
    $community = "";
    $sql = "SELECT `community` FROM `user` WHERE `id`=$user_id LIMIT 1";
    $rs = sql($sql);
    if ($rs->num_rows) {
        list($community) = $rs->fetch_row();
    }
    return $community;
}

//获取社区url
function get_community_url($user_id)
{
    $community = get_community($user_id);
    $community_arr = getConfigByName("community");
    $community_url = $community_arr[$community]['url'];
    return $community_url;
}

//获取配置文件
function getConfigByName($name, $print = false)
{
    $file_name = "config/{$name}.json";
    $json_arr = json_decode(file_get_contents($file_name), true);
    if ($print) {
        print_r($json_arr);
    }
    return $json_arr;
}

//执行sql查询
function sql($sql)
{
    $result = $GLOBALS['mysqli']->query($sql);
    return $result;
}


//需要登录
function need_login()
{
    if ($GLOBALS['uid'] || $_COOKIE['community_url']) {
        $community_url = $_COOKIE['community_url'] ? $_COOKIE['community_url'] : get_community_url($GLOBALS['uid']);
        echo "<span>你好,游戏尚未登录!</span><br><a href='{$community_url}'>现在登录</a>";
    } else {
        echo "<span>你好,请返回社区登录!</span>";
    }
}

//数字转汉字
function ToChineseNum($num)
{
    $char = array("零","一","二","三","四","五","六","七","八","九");
    $dw = array("","十","百","千","万","亿","兆");
    $retval = "";
    $proZero = false;
    for($i = 0;$i < strlen($num);$i++)
    {
        if($i > 0)    $temp = (int)(($num % pow (10,$i+1)) / pow (10,$i));
        else $temp = (int)($num % pow (10,1));

        if($proZero == true && $temp == 0) continue;

        if($temp == 0) $proZero = true;
        else $proZero = false;

        if($proZero)
        {
            if($retval == "") continue;
            $retval = $char[$temp].$retval;
        }
        else $retval = $char[$temp].$dw[$i].$retval;
    }
    if($retval == "一十") $retval = "十";
    return $retval;
}

//POST类
class post
{
    static function get($value_name)
    {
        return self::real_escape_string($_POST[$value_name]);
    }

    private static function real_escape_string($string)
    {
        return $GLOBALS['mysqli']->real_escape_string($string);
    }
}

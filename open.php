<?php
//忽略危险
error_reporting(E_ALL || ~E_NOTICE);
//开始游戏会话
session_start();
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>绿色传奇仙盟会</title>
    <link rel="icon" href="favicon.ico"/>
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        body {
            font: Normal 18px "Microsoft YaHei";
            text-align: center;
        }

        @media (min-width: 768px) {
            body {
                margin: 0 auto;
                width: 414px;
            }
        }

        .logo {
            margin-top: 5px;
        }
    </style>
</head>
<body>
<?php
//引入游戏文件
//配置文件
require_once('config.php');
//连接mysql数据库
$mysqli = new mysqli($mysql_host, $mysql_user_name, $mysql_passwd, $mysql_dbname, $mysql_port);
//设置连接字符集
$sql = "set names utf8mb4";
$mysqli->query($sql);
//Redis数据库操作
$redis = new Redis();
$redis->open($re_host, $re_port);
$redis->auth($re_pass);
//用户数据
$user_name = "";
$error = "";
//来源社区
$source = $_GET['source'];
//登陆参数
$game = $_GET['game'];
$area = $_GET['area'];
$user = $_GET['user'];
$time = $_GET['time'];
$hash = $_GET['hash'];
$from = $_GET['from'];
$open_id = $_GET['open_id'];
//获取社区数据数组
$community_arr = getConfigByName("community");
//平台密钥
$community_game_key = $community_arr[$source]["key"];
//游戏编号
$community_game_num = $community_arr[$source]["game"];
//社区名称
$community_name = $community_arr[$source]["name"];
//社区网址
$community_url = $community_arr[$source]["url"];
//开始验证
$game_ok = false;
foreach ($community_game_num as $game_num => $game_key) {
    if ($game == $game_num) {
        $community_game_key = $game_key;
        $game_ok = true;
        break;
    }
}
//验证hash
$community_user_hash = eval("return " . $community_arr[$source]["hash"] . ";");
if ($game_ok && $area && $user && $time && $hash) {
    if ($hash == $community_user_hash && substr($time, 0, 10) > 0) {
        $login_success = true;
        switch ($from) {
            case "qq":
                if ($open_id) {
                    $sql = "SELECT `name` FROM `user` WHERE `open_id`='$open_id' LIMIT 1";
                    $result = $mysqli->query($sql);
                    list($user_name) = $result->fetch_row();
                    if (!$user_name) {
                        $user_name = $source . "_qq_" . $redis->incr('mwmm_qq_num');
                    }
                } else {
                    $login_success = false;
                }
                break;
            default:
                $user_name = $user;
                break;
        }
    } else {
        $error = <<<error
<p>登录失败!</p>
<a href="{$community_url}">返回{$community_name}</a>
error;
    }
} else {
    $error = <<<error
<p>参数错误!</p>
<a href="{$community_url}">返回{$community_name}</a>
error;
}
//登录结果
if ($login_success) {
    //获取用户IP地址
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    if (strstr($ip, ",")) {
        $ip_arr = explode(",", $ip);
        $ip = $ip_arr[0];
    }
    //查询用户是否存在
    $sql = "SELECT `id` FROM `user` WHERE `id`='$user' LIMIT 1";
    $result = $mysqli->query($sql);
    list($is_reg) = $result->fetch_row();
    if (!$is_reg) {
        $password = mt_rand(100000, 999999);
        $result = $mysqli->query("INSERT INTO `user` (`id`,`name`, `password`,`community`,`open_from`,`open_id`) VALUES ('" . $user . "','" . $user . "', '" . $password . "', '" . $source . "', '" . $from . "', '" . $open_id . "')");
    }
    //提取用户id
    $result = $mysqli->query("SELECT `id`,`password` FROM `user` WHERE `id` = '" . $user . "' LIMIT 1");
    list($uid, $pwd) = $result->fetch_row();
    //获取登录时间
    $login_time = date('Y-m-d H:i:s');
    //记录会话id
    $sid = md5($uid . $login_time . $pwd);
    $sql = "UPDATE `user` SET `sid` = '$sid', `ip` = '$ip' WHERE `id` = $uid LIMIT 1";
    $mysqli->query($sql);
    //进入游戏链接
    if ($mysqli->affected_rows) {
        echo "<img src='logo2.png' class='logo'><br><span>欢迎ST" . $user . ",登录成功!</span><br>";
        echo "<a class='btn btn-success' href='wapgame.php?sid={$sid}&game={$game}&area={$area}' style='margin-top: 5px;'>进入游戏</a>";
        echo "<br>保存本页书签下次直接进入游戏<br>";
    } else {
        $error = <<<error
<p>未知错误!</p>
<a href="{$community_url}">返回{$community_name}</a>
error;
    }
} else {
    echo $error;
}
//关闭数据库
$mysqli->close();
?>
</body>
<script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.slim.min.js"
        integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
        crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/popper.js/1.12.9/umd/popper.min.js"
        integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
        crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/bootstrap/4.0.0/js/bootstrap.min.js"
        integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
        crossorigin="anonymous"></script>
</html>

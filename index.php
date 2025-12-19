<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>绿色传奇-wap游戏 wap网游 wap文字游戏（仙盟会）</title>
    <link rel="icon" href="favicon.ico"/>
    <link href="res/css/bootstrap.min.css" rel="stylesheet">
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
//忽略危险
error_reporting(E_ALL || ~E_NOTICE);
//引入游戏文件
require('config.php');
session_start();
$login_success = false;
//获取用户数据
$user_name = $_POST['user_name'];
$password = $_POST['password'];
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
if ($user_name && $password) {
    //连接mysql数据库
    $mysqli = new mysqli($mysql_host, $mysql_user_name, $mysql_passwd, $mysql_dbname, $mysql_port);
    //编码语句
    $query = "set names utf8mb4";
    //执行编码
    $mysqli->query($query);
    //开始数据库操作
    $user_name = $mysqli->real_escape_string($user_name);
    $password = $mysqli->real_escape_string($password);
    $result = $mysqli->query("SELECT `password`,`id` FROM `user` WHERE `name` = '" . $user_name . "' LIMIT 1");
    //查询是否出错
    if (!$mysqli->errno) {
        //提取密码 用户id
        list($pwd, $uid) = $result->fetch_row();
        //验证成功
        if ($pwd == $password) {
            //登录时间
            $login_time = date('Y-m-d H:i:s');
            //记录会话id
            $sid = md5($uid . $login_time . $pwd);
            $mysqli->query("UPDATE `user` SET `sid`='" . $sid . "',`ip`='{$ip}' WHERE `id` = " . $uid . " LIMIT 1");
            //输出进入游戏链接
            echo "<img src='logo2.png' class='logo'><br><span>欢迎" . $user_name . ",登录成功!</span><br>";
            echo "<a class='btn btn-success' href='wapgame.php?sid={$sid}' style='margin-top: 5px;'>进入游戏</a>";
            //登陆成功
            $login_success = true;
        } else {
            if ($pwd) {
                echo "<span>用户密码错误!</span><br>";
            } else {
                echo "<span>用户名不存在!</span><br>";
            }
        }
    }
    //关闭数据库
    $mysqli->close();
}
if (!$login_success) {
    echo <<<loginform
<img src="res/img/logo.png" class="logo">
<h4>最原汁原味的传奇游戏,最经典的版本。 不断加入全新特色玩法,沙巴克城战、魔龙城国战…… 最经典的传奇,最精彩的玩法,只为让你... </h4>
<form action="login.php" method="post">
    <div class="form-group">
        <label for="login_user_name">账号</label>
        <input type="text" class="form-control" id="login_user_name" name="user_name" maxlength="16" placeholder="账号">
    </div>
    <div class="form-group">
        <label for="login_password">密码</label>
        <input type="password" class="form-control" id="login_password" name="password" maxlength="16" placeholder="密码">
    </div>
    <button type="submit" class="btn btn-success">登录游戏</button>
</form>
<a class="btn btn-danger" href="reg.php" style="margin-top: 5px;">注册账号</a>
loginform;
}
?>
<h4>© 2019-2021 <a href="http:仙盟会">绿色传奇</a></h4>
</body>
<script src="res/js/jquery.min.js"></script>
<script src="res/js/bootstrap.min.js"></script>
</html>

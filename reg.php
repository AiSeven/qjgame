<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>绿色传奇-游戏注册</title>
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
//获取用户数据
$user_name = $_POST['user_name'];
$password = $_POST['password'];
if ($user_name && $password) {
    if (ctype_alnum($user_name) && ctype_alnum($password)) {
        //连接mysql数据库
        $mysqli = new mysqli($mysql_host, $mysql_user_name, $mysql_passwd, $mysql_dbname, $mysql_port);
        $query = "set names utf8mb4";//查询语句
        $mysqli->query($query);//执行编码
        //开始数据库操作
        $user_name = $mysqli->real_escape_string($user_name);
        $password = $mysqli->real_escape_string($password);
        $result = $mysqli->query("INSERT INTO `{$mysql_dbname}`.`user` (`name`, `password`) VALUES ('" . $user_name . "', '" . $password . "')");
        if (!$mysqli->errno) {//查询是否出错
            echo "<img src='res/img/logo.png' class='logo'><br><span>恭喜" . $user_name . "注册成功!</span><br><a href='login.php' class='btn btn-success'>现在登录</a>";
        } else {
            echo "<span>用户名已存在!</span><br><a href='reg.php'>重新注册</a>";
        }
        //关闭数据库
        $mysqli->close();
    } else {
        echo "<span>账号与密码仅允许字母和数字!</span><br><a href='reg.php'>重新注册</a>";
    }
} else {
    echo <<<regform
<img src="res/img/logo.png" class="logo">
<h4>最原汁原味的传奇游戏,最经典的版本。 不断加入全新特色玩法,沙巴克城战、魔龙城国战…… 最经典的传奇,最精彩的玩法,只为让你... </h4>
<form action="reg.php" method="post">
    <div class="form-group">
        <label for="reg_user_name">账号</label>
        <input type="text" class="form-control" id="reg_user_name" name="user_name" maxlength="16" placeholder="账号">
    </div>
    <div class="form-group">
        <label for="reg_password">密码</label>
        <input type="password" class="form-control" id="reg_password" name="password" maxlength="16" placeholder="密码">
    </div>
    <button type="submit" class="btn btn-success">注册账号</button>
</form>
<a class="btn btn-danger" href="login.php" style="margin-top: 5px;">登录游戏</a>
regform;
}
?>
<h4>© 2016-2017 <a href="index.php">绿色传奇</a></h4>
</body>
<script src="res/js/jquery.min.js"></script>
<script src="res/js/bootstrap.min.js"></script>
</html>

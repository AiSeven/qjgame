<?php
//后台管理登录页面
//忽略危险
error_reporting(E_ALL || ~E_NOTICE);
//引入配置文件
require('config.php');
//开始会话
session_start();

//检查是否已登录
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_panel.php');
    exit;
}

//默认管理账户配置（可以在实际使用时修改）
$ADMIN_USERNAME = '123456a'; // 默认管理员用户名
$ADMIN_PASSWORD = '123456a'; // 默认管理员密码（建议使用后修改）

$login_error = '';

//处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    //验证用户名和密码
    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
        //登录成功
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin_panel.php');
        exit;
    } else {
        $login_error = '用户名或密码错误';
    }
}
?>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>绿色传奇-后台管理登录</title>
    <link rel="icon" href="favicon.ico"/>
    <style>
        body {
            font: Normal 18px "Microsoft YaHei";
            text-align: center;
            margin-top: 50px;
        }
        
        @media (min-width: 768px) {
            body {
                margin: 50px auto;
                width: 414px;
            }
        }
        
        .logo {
            margin-top: 20px;
        }
        
        .form-group {
            margin: 15px 0;
        }
        
        input {
            font-size: 18px;
            padding: 8px;
            width: 80%;
            margin: 5px 0;
        }
        
        button {
            font-size: 18px;
            padding: 10px 20px;
            margin: 10px 0;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .error {
            color: red;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <img src="logo2.png" class="logo"><br>
    <h3>绿色传奇 后台管理系统</h3>
    <p>管理员权限管理界面</p>
    
    <?php if ($login_error): ?>
        <div class="error"><?php echo $login_error; ?></div>
    <?php endif; ?>
    
    <form action="admin_login.php" method="post">
        <div class="form-group">
            <div>用户名</div>
            <input type="text" name="username" placeholder="请输入管理员用户名" required>
        </div>
        <div class="form-group">
            <div>密码</div>
            <input type="password" name="password" placeholder="请输入管理员密码" required>
        </div>
        <button type="submit">登录</button>
    </form>
    
    <p style="font-size: 14px; margin-top: 30px; color: #666;">
        注意：默认账号为admin，密码为admin123<br>
        登录后请谨慎操作，避免误操作影响游戏数据
    </p>
</body>
</html>
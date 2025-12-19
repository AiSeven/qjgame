<?php
//后台管理面板
//忽略危险
error_reporting(E_ALL || ~E_NOTICE);
//引入配置文件
require('config.php');
//开始会话
session_start();

//验证是否登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

//设置操作成功或失败的消息
$message = '';
$message_type = ''; // success 或 error

//获取游戏区服数据库连接
function get_game_db_connection() {
    global $mysql_host, $mysql_user_name, $mysql_passwd, $mysql_port;
    
    //连接默认游戏区服数据库 (sg_mp001)
    $game_dbname = 'sg_mp001';
    $mysqli = new mysqli($mysql_host, $mysql_user_name, $mysql_passwd, $game_dbname, $mysql_port);
    
    //检查连接是否成功
    if ($mysqli->connect_error) {
        die('数据库连接失败: ' . $mysqli->connect_error);
    }
    
    //设置编码
    $mysqli->query("SET NAMES UTF8MB4");
    
    return $mysqli;
}

//获取用户ID对应的用户名
function get_user_name_by_id($uid) {
    global $mysql_host, $mysql_user_name, $mysql_passwd, $mysql_port, $mysql_dbname;
    
    //连接用户数据库
    $mysqli = new mysqli($mysql_host, $mysql_user_name, $mysql_passwd, $mysql_dbname, $mysql_port);
    
    if ($mysqli->connect_error) {
        return '未知用户';
    }
    
    $uid = $mysqli->real_escape_string($uid);
    $sql = "SELECT `name` FROM `user` WHERE `id` = '{$uid}' LIMIT 1";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['name'];
    } else {
        $name = '未知用户';
    }
    
    $mysqli->close();
    return $name;
}

//检查并清理过期的管理员权限
function check_expired_admins() {
    try {
        $mysqli = get_game_db_connection();
        $current_time = time();
        
        //删除已过期的管理员权限 - MySQL 5.7兼容版本
        $sql = "DELETE FROM `game_user_value` 
                WHERE `valuename` LIKE '%.game_master' 
                AND `valuename` NOT LIKE '%.game_master_expire' 
                AND EXISTS (
                    SELECT 1 FROM (
                        SELECT g2.`valuename`, g2.`value` 
                        FROM `game_user_value` AS g2 
                    ) AS temp_g2 
                    WHERE temp_g2.`valuename` = CONCAT(SUBSTRING_INDEX(game_user_value.`valuename`, '.', 1), '.game_master_expire') 
                    AND temp_g2.`value` > 0 
                    AND temp_g2.`value` < '{$current_time}'
                )";
        
        $mysqli->query($sql);
        
        //删除过期的有效期记录 - MySQL 5.7兼容版本
        $sql = "DELETE FROM `game_user_value` 
                WHERE `valuename` LIKE '%.game_master_expire' 
                AND `value` > 0 
                AND `value` < '{$current_time}'
                AND NOT EXISTS (
                    SELECT 1 FROM (
                        SELECT g2.`valuename` 
                        FROM `game_user_value` AS g2 
                    ) AS temp_g2 
                    WHERE temp_g2.`valuename` = CONCAT(SUBSTRING_INDEX(game_user_value.`valuename`, '.', 1), '.game_master')
                )";
        
        $mysqli->query($sql);
        
        $mysqli->close();
    } catch (Exception $e) {
        //忽略异常
    }
}

//执行过期检查
check_expired_admins();

//处理增加管理员请求
if (isset($_POST['add_admin'])) {
    $user_id = trim($_POST['user_id']);
    $expire_days = intval($_POST['expire_days']);
    
    if (empty($user_id)) {
        $message = '用户ID不能为空';
        $message_type = 'error';
    } else {
        try {
            $mysqli = get_game_db_connection();
            $user_id = $mysqli->real_escape_string($user_id);
            
            //检查用户ID是否存在于game_user表中
            $check_sql = "SELECT COUNT(*) FROM `game_user` WHERE `user_id` = '{$user_id}'";
            $check_result = $mysqli->query($check_sql);
            $check_row = $check_result->fetch_row();
            
            if ($check_row[0] == 0) {
                $message = '该用户ID不存在于游戏中';
                $message_type = 'error';
            } else {
                //计算过期时间
                $expire_time = 0;
                if ($expire_days > 0) {
                    $expire_time = time() + ($expire_days * 86400); // 86400秒 = 1天
                }
                
                //检查是否已经是管理员
                $check_admin_sql = "SELECT COUNT(*) FROM `game_user_value` WHERE `valuename` = '{$user_id}.game_master'";
                $check_admin_result = $mysqli->query($check_admin_sql);
                $check_admin_row = $check_admin_result->fetch_row();
                
                //事务开始
                $mysqli->begin_transaction();
                
                try {
                    //设置管理员权限
                    if ($check_admin_row[0] > 0) {
                        //已经是管理员，更新值为1
                        $sql = "UPDATE `game_user_value` SET `value` = '1' WHERE `valuename` = '{$user_id}.game_master'";
                    } else {
                        //不是管理员，插入新记录
                        $sql = "INSERT INTO `game_user_value` (`userid`, `petid`, `valuename`, `value`) VALUES ('{$user_id}', '0', '{$user_id}.game_master', '1')";
                    }
                    
                    $mysqli->query($sql);
                    
                    //设置有效期
                    if ($expire_time > 0) {
                        //检查是否已有有效期记录
                        $check_expire_sql = "SELECT COUNT(*) FROM `game_user_value` WHERE `valuename` = '{$user_id}.game_master_expire'";
                        $check_expire_result = $mysqli->query($check_expire_sql);
                        $check_expire_row = $check_expire_result->fetch_row();
                        
                        if ($check_expire_row[0] > 0) {
                            //已存在，更新
                            $sql = "UPDATE `game_user_value` SET `value` = '{$expire_time}' WHERE `valuename` = '{$user_id}.game_master_expire'";
                        } else {
                            //不存在，插入
                            $sql = "INSERT INTO `game_user_value` (`userid`, `petid`, `valuename`, `value`) VALUES ('{$user_id}', '0', '{$user_id}.game_master_expire', '{$expire_time}')";
                        }
                        
                        $mysqli->query($sql);
                    } else {
                        //如果设置为永久有效，则删除有效期记录
                        $sql = "DELETE FROM `game_user_value` WHERE `valuename` = '{$user_id}.game_master_expire'";
                        $mysqli->query($sql);
                    }
                    
                    //提交事务
                    $mysqli->commit();
                    
                    if ($expire_time > 0) {
                        $expire_date = date('Y-m-d', $expire_time);
                        $message = "管理员权限添加成功，有效期至{$expire_date}";
                    } else {
                        $message = '管理员权限添加成功，永久有效';
                    }
                    $message_type = 'success';
                } catch (Exception $e) {
                    //回滚事务
                    $mysqli->rollback();
                    $message = '操作失败: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
            
            $mysqli->close();
        } catch (Exception $e) {
            $message = '操作异常: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

//处理删除管理员请求
if (isset($_POST['remove_admin'])) {
    $user_id = trim($_POST['user_id']);
    
    if (empty($user_id)) {
        $message = '用户ID不能为空';
        $message_type = 'error';
    } else {
        try {
            $mysqli = get_game_db_connection();
            $user_id = $mysqli->real_escape_string($user_id);
            
            //事务开始
            $mysqli->begin_transaction();
            
            try {
                //删除管理员记录
                $sql = "DELETE FROM `game_user_value` WHERE `valuename` = '{$user_id}.game_master'";
                $mysqli->query($sql);
                
                //删除有效期记录
                $sql = "DELETE FROM `game_user_value` WHERE `valuename` = '{$user_id}.game_master_expire'";
                $mysqli->query($sql);
                
                //提交事务
                $mysqli->commit();
                
                if ($mysqli->affected_rows > 0) {
                    $message = '管理员权限删除成功';
                    $message_type = 'success';
                } else {
                    $message = '该用户不是管理员';
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                //回滚事务
                $mysqli->rollback();
                $message = '操作失败: ' . $e->getMessage();
                $message_type = 'error';
            }
            
            $mysqli->close();
        } catch (Exception $e) {
            $message = '操作异常: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

//获取当前管理员列表
function get_admin_list() {
    $admins = [];
    try {
        $mysqli = get_game_db_connection();
        $current_time = time();
        
        //查询所有管理员记录和对应的有效期
        $sql = "SELECT g1.`valuename` AS master_name, g1.`value` AS master_value, 
                       g2.`value` AS expire_value 
                FROM `game_user_value` g1 
                LEFT JOIN `game_user_value` g2 
                ON g2.`valuename` = CONCAT(SUBSTRING_INDEX(g1.`valuename`, '.', 1), '.game_master_expire') 
                WHERE g1.`valuename` LIKE '%.game_master' 
                ORDER BY g1.`valuename`";
        $result = $mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $master_name = $row['master_name'];
                $master_value = $row['master_value'];
                $expire_value = $row['expire_value'];
                
                //提取用户ID
                $uid = explode('.', $master_name)[0];
                
                //获取用户名
                $username = get_user_name_by_id($uid);
                
                //计算有效期状态
                $expire_status = '永久有效';
                $status = '正常';
                if (!empty($expire_value) && $expire_value > 0) {
                    $expire_date = date('Y-m-d', $expire_value);
                    $expire_status = "至 {$expire_date}";
                    
                    //检查是否已过期
                    if ($expire_value < $current_time) {
                        $status = '已过期';
                    }
                }
                
                $admins[] = [
                    'uid' => $uid,
                    'username' => $username,
                    'value' => $master_value,
                    'expire_value' => $expire_value,
                    'expire_status' => $expire_status,
                    'status' => $status
                ];
            }
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        //忽略异常
    }
    
    return $admins;
}

$admin_list = get_admin_list();

//退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}
?>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>绿色传奇-后台管理面板</title>
    <link rel="icon" href="favicon.ico"/>
    <style>
        body {
            font: Normal 18px "Microsoft YaHei";
            text-align: center;
            margin-top: 20px;
        }
        
        @media (min-width: 768px) {
            body {
                margin: 20px auto;
                width: 414px;
            }
        }
        
        .logo {
            margin-top: 10px;
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
        
        .button-danger {
            background-color: #f44336;
        }
        
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .logout {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 14px;
            color: #f44336;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a href="admin_panel.php?action=logout" class="logout">退出登录</a>
    
    <img src="logo2.png" class="logo"><br>
    <h3>绿色传奇 后台管理系统</h3>
    <p>管理员权限管理面板</p>
    
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <!-- 增加管理员 -->
    <div class="section">
        <h4>增加管理员权限</h4>
        <form action="admin_panel.php" method="post">
            <div class="form-group">
                <div>用户ID</div>
                <input type="text" name="user_id" placeholder="请输入游戏用户ID" required>
            </div>
            <div class="form-group">
                <div>有效期（天）</div>
                <select name="expire_days">
                    <option value="0">永久有效</option>
                    <option value="7">7天</option>
                    <option value="30">30天</option>
                    <option value="90">90天</option>
                    <option value="180">180天</option>
                    <option value="365">365天</option>
                </select>
            </div>
            <button type="submit" name="add_admin">增加管理员</button>
        </form>
        <p style="font-size: 14px; color: #666; margin-top: 10px;">
            说明：添加后用户将获得游戏管理员权限
        </p>
    </div>
    
    <!-- 删除管理员 -->
    <div class="section">
        <h4>删除管理员权限</h4>
        <form action="admin_panel.php" method="post">
            <div class="form-group">
                <div>用户ID</div>
                <input type="text" name="user_id" placeholder="请输入游戏用户ID" required>
            </div>
            <button type="submit" name="remove_admin" class="button-danger">删除管理员</button>
        </form>
        <p style="font-size: 14px; color: #666; margin-top: 10px;">
            警告：删除后用户将失去游戏管理员权限，请谨慎操作
        </p>
    </div>
    
    <!-- 当前管理员列表 -->
    <div class="section">
        <h4>当前管理员列表</h4>
        <?php if (count($admin_list) > 0): ?>
            <table>
                <tr>
                    <th>用户ID</th>
                    <th>用户名</th>
                    <th>权限值</th>
                    <th>有效期</th>
                    <th>状态</th>
                </tr>
                <?php foreach ($admin_list as $admin): ?>
                    <tr>
                        <td><?php echo $admin['uid']; ?></td>
                        <td><?php echo $admin['username']; ?></td>
                        <td><?php echo $admin['value']; ?></td>
                        <td><?php echo $admin['expire_status']; ?></td>
                        <td><?php echo $admin['status']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>暂无管理员</p>
        <?php endif; ?>
    </div>
</body>
</html>
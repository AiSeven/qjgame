<?php
// 测试文件 - 检查数据库连接和清理功能
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<html><head><meta charset="utf-8"><title>测试页面</title></head><body>';
echo '<h1>🔍 系统测试</h1>';

// 测试PHP版本
echo '<h3>PHP版本: ' . phpversion() . '</h3>';

// 测试数据库连接
$host = '127.0.0.1';
$user = 'root';
$pass = '123456';
$db = 'wapgame';

$conn = @mysqli_connect($host, $user, $pass, $db);
if ($conn) {
    echo '<p style="color:green">✅ 数据库连接成功</p>';
    
    // 测试表是否存在
    $tables = ['game_prop', 'game_value'];
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo '<p style="color:green">✅ 表 ' . $table . ' 存在</p>';
        } else {
            echo '<p style="color:red">❌ 表 ' . $table . ' 不存在</p>';
        }
    }
    
    // 测试查询
    $count = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM game_prop WHERE map_id > 0");
    if ($count) {
        $row = mysqli_fetch_assoc($count);
        echo '<p>📊 当前地面物品数量: ' . $row['cnt'] . '</p>';
    }
    
    mysqli_close($conn);
} else {
    echo '<p style="color:red">❌ 数据库连接失败: ' . mysqli_connect_error() . '</p>';
}

echo '<br><a href="立即清理.html" style="background:#007bff;color:white;padding:10px 20px;border-radius:5px;text-decoration:none;">返回清理页面</a>';
echo '</body></html>';
?>
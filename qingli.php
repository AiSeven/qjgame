<?php
// 游戏物品清理工具 - 修复版本
// 访问：http://localhost/qingli.php

// 关闭错误显示
error_reporting(0);

// 数据库配置
$host = '127.0.0.1';
$user = 'root';
$pass = '123456';
$db = 'wapgame';

// 连接数据库
$conn = @mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('<html><body style="font-family:Arial;text-align:center;padding:50px;">
          <h1>❌ 数据库连接失败</h1>
          <p>请检查数据库是否正常运行</p>
          <a href="index.php" style="background:#007bff;color:white;padding:10px 20px;border-radius:5px;text-decoration:none;">返回游戏</a>
          </body></html>');
}

mysqli_set_charset($conn, "utf8mb4");

// 获取物品数量
function getCount($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM game_prop WHERE map_id > 0");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['cnt'];
    }
    return 0;
}

// 执行清理
function cleanItems($conn) {
    $total = 0;
    
    // 清理地面道具
    $r1 = mysqli_query($conn, "DELETE FROM game_prop WHERE map_id > 0 AND time < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    if ($r1) $total += mysqli_affected_rows($conn);
    
    // 清理地图物品值
    $r2 = mysqli_query($conn, "DELETE FROM game_value WHERE valuename LIKE 'map.%i.%' AND time < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    if ($r2) $total += mysqli_affected_rows($conn);
    
    return $total;
}

// 处理清理
if (isset($_GET['clean'])) {
    $cleaned = cleanItems($conn);
    header("Location: qingli.php?done=" . $cleaned);
    exit;
}

$count = getCount($conn);
$done = isset($_GET['done']) ? intval($_GET['done']) : 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>游戏物品清理</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; text-align: center; }
        .box { max-width: 400px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .msg { margin: 20px 0; font-size: 18px; }
        .btn { background: #007bff; color: white; border: none; padding: 15px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px; }
        .btn:hover { background: #0056b3; }
        .btn-green { background: #28a745; }
        .btn-green:hover { background: #1e7e34; }
        .info { color: #666; margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🗑️ 游戏物品清理</h1>
        
        <?php if ($done > 0): ?>
            <div class="msg" style="color: #28a745;">
                ✅ 清理完成！已清理 <?php echo $done; ?> 个物品<br>
                <strong>现在重新进入游戏试试</strong>
            </div>
            <a href="index.php" class="btn btn-green">立即进入游戏</a>
            <a href="qingli.php" class="btn">再次清理</a>
            
        <?php elseif ($count > 0): ?>
            <div class="msg" style="color: #ff6b35;">
                ⚠️ 发现 <?php echo $count; ?> 个地面物品<br>
                <strong>可能导致游戏卡顿</strong>
            </div>
            <a href="qingli.php?clean=1" class="btn">立即清理</a>
            <a href="index.php" class="btn">直接进入游戏</a>
            
        <?php else: ?>
            <div class="msg" style="color: #28a745;">
                ✅ 地面很干净，无需清理<br>
                <strong>游戏应该很流畅</strong>
            </div>
            <a href="index.php" class="btn btn-green">进入游戏</a>
        <?php endif; ?>
        
        <div class="info">
            💡 清理后请重新进入游戏测试<br>
            🔄 每2小时清理一次保持流畅
        </div>
    </div>
</body>
</html>
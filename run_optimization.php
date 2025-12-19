<?php
/**
 * 游戏一键优化脚本
 * 直接在浏览器中运行
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 优化步骤
$steps = [];

function addStep($title, $status, $message) {
    global $steps;
    $steps[] = [
        'title' => $title,
        'status' => $status,
        'message' => $message,
        'time' => date('H:i:s')
    ];
}

function optimizeDatabase() {
    try {
        $mysqli = new mysqli('127.0.0.1', 'root', '123456', 'wapgame', 3306);
        
        if ($mysqli->connect_error) {
            addStep('数据库连接', 'error', '连接失败: ' . $mysqli->connect_error);
            return false;
        }
        
        addStep('数据库连接', 'success', '连接成功');
        
        // 执行优化SQL
        $optimizations = [
            "ALTER TABLE `user` ADD INDEX IF NOT EXISTS `idx_name` (`name`)" => "用户表索引",
            "ALTER TABLE `user` ADD INDEX IF NOT EXISTS `idx_sid` (`sid`)" => "用户会话索引",
            "ALTER TABLE `cdk` ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`)" => "CDK用户索引",
            "ALTER TABLE `cdk` ADD INDEX IF NOT EXISTS `idx_game` (`game`)" => "CDK游戏索引",
            "ALTER TABLE `recharge` ADD INDEX IF NOT EXISTS `idx_user_name` (`user_name`)" => "充值用户索引",
            "ALTER TABLE `recharge` ADD INDEX IF NOT EXISTS `idx_time` (`time`)" => "充值时间索引",
            "OPTIMIZE TABLE `user`" => "优化用户表",
            "OPTIMIZE TABLE `cdk`" => "优化CDK表",
            "OPTIMIZE TABLE `recharge`" => "优化充值表"
        ];
        
        foreach ($optimizations as $sql => $description) {
            try {
                $result = $mysqli->query($sql);
                if ($result === false) {
                    // 索引可能已存在，跳过错误
                    if (strpos($mysqli->error, 'Duplicate key name') === false) {
                        addStep($description, 'warning', '跳过: ' . $mysqli->error);
                    } else {
                        addStep($description, 'success', '索引已存在');
                    }
                } else {
                    addStep($description, 'success', '完成');
                }
            } catch (Exception $e) {
                addStep($description, 'warning', '跳过: ' . $e->getMessage());
            }
        }
        
        $mysqli->close();
        return true;
        
    } catch (Exception $e) {
        addStep('数据库优化', 'error', '异常: ' . $e->getMessage());
        return false;
    }
}

function checkAndModifyConfig() {
    $config_files = [
        'config.php' => 'c:\\shenhau\\www\\config.php',
        'sanguo_config.php' => 'c:\\shenhau\\www\\sanguo\\app\\conf\\config.php'
    ];
    
    foreach ($config_files as $name => $path) {
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // 检查是否需要修改
            if (strpos($content, "$mysql_host = '127.0.0.1';") !== false) {
                $new_content = str_replace(
                    "$mysql_host = '127.0.0.1';",
                    "$mysql_host = 'p:127.0.0.1';",
                    $content
                );
                
                if (file_put_contents($path, $new_content)) {
                    addStep("修改{$name}", 'success', '已启用持久连接');
                } else {
                    addStep("修改{$name}", 'error', '文件写入失败');
                }
            } else {
                addStep("检查{$name}", 'success', '无需修改或已优化');
            }
        } else {
            addStep("检查{$name}", 'warning', '文件不存在');
        }
    }
}

function generateOptimizationReport() {
    global $steps;
    
    $report = "=== 游戏优化报告 ===\n";
    $report .= "执行时间: " . date('Y-m-d H:i:s') . "\n\n";
    
    $success_count = 0;
    $warning_count = 0;
    $error_count = 0;
    
    foreach ($steps as $step) {
        switch ($step['status']) {
            case 'success':
                $success_count++;
                break;
            case 'warning':
                $warning_count++;
                break;
            case 'error':
                $error_count++;
                break;
        }
    }
    
    $report .= "成功: {$success_count} | 警告: {$warning_count} | 错误: {$error_count}\n\n";
    
    foreach ($steps as $step) {
        $status_icon = $step['status'] === 'success' ? '✅' : ($step['status'] === 'warning' ? '⚠️' : '❌');
        $report .= "{$status_icon} {$step['title']}: {$step['message']}\n";
    }
    
    file_put_contents('optimization_result.txt', $report);
    return $report;
}

// 执行优化
if (isset($_GET['run'])) {
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <title>游戏一键优化</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .step { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
            .success { background: #d4edda; }
            .warning { background: #fff3cd; }
            .error { background: #f8d7da; }
            .btn { padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>🎮 游戏一键优化进行中...</h1>
        <div id="results">';
    
    // 执行优化步骤
    optimizeDatabase();
    checkAndModifyConfig();
    
    echo '</div>
        <h2>优化完成！</h2>
        <p><a href="run_optimization.php" class="btn">重新运行</a></p>
        <p><a href="index.php" class="btn">测试游戏</a></p>
        <p><a href="optimizer.html" class="btn">返回优化工具</a></p>
    </body>
    </html>';
    
    // 显示详细结果
    foreach ($steps as $step) {
        $class = $step['status'];
        echo "<div class='step {$class}'><strong>{$step['title']}</strong>: {$step['message']}</div>";
    }
    
    echo '<h3>下一步操作</h3>
    <ol>
        <li>在小皮面板重启Apache服务</li>
        <li>在小皮面板重启MySQL服务</li>
        <li>重新访问游戏测试性能</li>
    </ol>
    <p><strong>优化结果已保存到 optimization_result.txt</strong></p>';
    
} else {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <title>游戏一键优化</title>
        <style>
            body { 
                font-family: "Microsoft YaHei", Arial, sans-serif; 
                max-width: 600px; 
                margin: 50px auto; 
                padding: 20px; 
                background: #f5f5f5; 
            }
            .container { 
                background: white; 
                padding: 30px; 
                border-radius: 10px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
                text-align: center; 
            }
            .btn { 
                display: inline-block; 
                padding: 15px 30px; 
                background: #007cba; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px; 
                font-size: 18px; 
                margin: 10px; 
            }
            .btn:hover { 
                background: #005a87; 
            }
            .warning { 
                background: #fff3cd; 
                border: 1px solid #ffeaa7; 
                color: #856404; 
                padding: 15px; 
                border-radius: 5px; 
                margin: 20px 0; 
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎮 游戏一键优化</h1>
            
            <div class="warning">
                <strong>⚠️ 运行前请确保：</strong><br>
                • 已备份数据库<br>
                • 小皮面板正在运行<br>
                • MySQL服务正常
            </div>
            
            <p>这个工具将自动执行以下优化：</p>
            <ul style="text-align: left;">
                <li>✅ 添加数据库索引</li>
                <li>✅ 启用持久连接</li>
                <li>✅ 优化配置文件</li>
                <li>✅ 生成优化报告</li>
            </ul>
            
            <a href="run_optimization.php?run=1" class="btn">开始一键优化</a>
            <br><br>
            <a href="optimizer.html" class="btn">手动优化工具</a>
            <br><br>
            <a href="简单优化指南.txt" class="btn">查看文字指南</a>
        </div>
    </body>
    </html>';
}
?>
<?php
/**
 * 优化后的定时器脚本
 * 解决物品堆积导致的加载卡顿问题
 */

// 引入必要的文件
require_once 'app/sys/value.php';
require_once 'app/sys/user.php';
require_once 'app/sys/prop.php';
require_once 'app/sys/pet.php';

class OptimizedTimer {
    private $db;
    private $cleanupStats = [];
    
    public function __construct() {
        $this->connectDB();
    }
    
    private function connectDB() {
        global $mysqli;
        if (!isset($mysqli)) {
            $mysqli = new mysqli('127.0.0.1', 'root', '123456', 'wapgame', 3306);
            $mysqli->set_charset("utf8mb4");
        }
        $this->db = $mysqli;
    }
    
    /**
     * 优化的物品清理 - 更频繁的清理
     */
    public function cleanupGroundItemsOptimized() {
        $startTime = microtime(true);
        $totalCleaned = 0;
        
        // 动态清理间隔：根据物品数量调整
        $itemCount = $this->getGroundItemsCount();
        $cleanupInterval = $this->getCleanupInterval($itemCount);
        
        // 清理物品值（1分钟前）
        $sql = "DELETE FROM `game_value` 
                WHERE `valuename` LIKE 'map.%i.%' 
                AND `time` < DATE_SUB(NOW(), INTERVAL {$cleanupInterval} MINUTE)";
        $this->db->query($sql);
        $cleaned1 = $this->db->affected_rows;
        $totalCleaned += $cleaned1;
        
        // 清理地面道具（1分钟前）
        $sql = "DELETE FROM `game_prop` 
                WHERE `map_id` > 0 
                AND `prop_id` > 0 
                AND `time` < DATE_SUB(NOW(), INTERVAL {$cleanupInterval} MINUTE)";
        $this->db->query($sql);
        $cleaned2 = $this->db->affected_rows;
        $totalCleaned += $cleaned2;
        
        // 清理武将卡（特殊处理）
        $sql = "DELETE FROM `game_prop` 
                WHERE `map_id` > 0 
                AND `prop_id` = 62 
                AND `time` < DATE_SUB(NOW(), INTERVAL {$cleanupInterval} MINUTE)";
        $this->db->query($sql);
        $cleaned3 = $this->db->affected_rows;
        $totalCleaned += $cleaned3;
        
        // 记录清理统计
        $this->cleanupStats = [
            'items_cleaned' => $totalCleaned,
            'cleanup_interval' => $cleanupInterval,
            'item_count_before' => $itemCount,
            'execution_time' => microtime(true) - $startTime
        ];
        
        return $totalCleaned;
    }
    
    /**
     * 根据物品数量动态调整清理间隔
     */
    private function getCleanupInterval($itemCount) {
        if ($itemCount > 1000) return 0.5; // 30秒
        if ($itemCount > 500) return 1;    // 1分钟
        if ($itemCount > 100) return 2;    // 2分钟
        return 5; // 5分钟
    }
    
    /**
     * 获取地面物品数量
     */
    private function getGroundItemsCount() {
        $result = $this->db->query("SELECT COUNT(*) as count FROM `game_prop` WHERE `map_id` > 0");
        return $result->fetch_assoc()['count'];
    }
    
    /**
     * 优化的在线玩家处理
     */
    public function processOnlineUsersOptimized() {
        // 批量处理，减少查询次数
        $sql = "SELECT `id` FROM `game_user` WHERE `is_online` = 1";
        $result = $this->db->query($sql);
        
        $userIds = [];
        while ($row = $result->fetch_assoc()) {
            $userIds[] = $row['id'];
        }
        
        if (empty($userIds)) return;
        
        $userList = implode(',', $userIds);
        
        // 批量更新在线时间
        $sql = "UPDATE `game_user_value` 
                SET `value` = CAST(`value` AS UNSIGNED) + 1 
                WHERE `userid` IN ($userList) 
                AND `valuename` = 'onlinetime'";
        $this->db->query($sql);
        
        // 批量更新礼包时间
        $sql = "UPDATE `game_user_value` 
                SET `value` = CAST(`value` AS UNSIGNED) + 1 
                WHERE `userid` IN ($userList) 
                AND `valuename` = 'lb_zx_fz_time'";
        $this->db->query($sql);
    }
    
    /**
     * 优化的消息清理
     */
    public function cleanupMessagesOptimized() {
        // 清理不同类型的消息，分批处理
        $cleanupTypes = [
            6 => 5,  // 广播消息 - 5分钟
            8 => 5,  // 人物走向 - 5分钟
            12 => 5, // 战斗消息 - 5分钟
            17 => 2, // 组队请求 - 2分钟
            20 => 10, // 合成请求 - 10分钟
            21 => 5   // 竞技消息 - 5分钟
        ];
        
        foreach ($cleanupTypes as $mode => $minutes) {
            $sql = "DELETE FROM `game_chat` 
                    WHERE `mode` = {$mode} 
                    AND `time` < DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)";
            $this->db->query($sql);
        }
    }
    
    /**
     * 优化的定时任务主函数
     */
    public function runOptimizedTimer() {
        $startTime = microtime(true);
        
        // 1. 清理地面物品（高频）
        $cleanedItems = $this->cleanupGroundItemsOptimized();
        
        // 2. 清理消息
        $this->cleanupMessagesOptimized();
        
        // 3. 处理在线玩家（批量）
        $this->processOnlineUsersOptimized();
        
        // 4. 清理过期宠物
        $this->cleanupExpiredPets();
        
        // 5. 清理过期道具
        $this->cleanupExpiredProps();
        
        $totalTime = microtime(true) - $startTime;
        
        return [
            'success' => true,
            'items_cleaned' => $cleanedItems,
            'execution_time' => round($totalTime, 3),
            'cleanup_stats' => $this->cleanupStats
        ];
    }
    
    /**
     * 清理过期宠物
     */
    private function cleanupExpiredPets() {
        // 清理2小时前的2星宠物
        $min_120_del_time = date('Y-m-d H:i:s', strtotime('-120 minute'));
        $sql = "DELETE FROM `game_pet` 
                WHERE `cj_gw_time` < '{$min_120_del_time}' 
                AND `master_id` = 0 
                AND `star` = 2";
        $this->db->query($sql);
        
        // 清理4小时前的3星以上宠物
        $min_240_del_time = date('Y-m-d H:i:s', strtotime('-240 minute'));
        $sql = "DELETE FROM `game_pet` 
                WHERE `cj_gw_time` < '{$min_240_del_time}' 
                AND `master_id` = 0 
                AND `star` > 2";
        $this->db->query($sql);
    }
    
    /**
     * 清理过期道具
     */
    private function cleanupExpiredProps() {
        $del_time = date('Y-m-d H:i:s', strtotime('-1440 minute')); // 24小时
        $sql = "DELETE FROM `game_prop` 
                WHERE `prop_id` > 0 
                AND `cj_time` < '{$del_time}' 
                AND `zb_ls` > 0";
        $this->db->query($sql);
    }
    
    /**
     * 获取性能统计
     */
    public function getPerformanceStats() {
        $stats = [
            'ground_items' => $this->getGroundItemsCount(),
            'database_size' => $this->getDatabaseSize(),
            'last_cleanup' => $this->getLastCleanupTime()
        ];
        
        return $stats;
    }
    
    private function getDatabaseSize() {
        $result = $this->db->query("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM information_schema.tables 
            WHERE table_schema = 'wapgame'
        ");
        return $result->fetch_assoc()['size_mb'];
    }
    
    private function getLastCleanupTime() {
        $result = $this->db->query("
            SELECT cleanup_time 
            FROM game_item_cleanup_log 
            ORDER BY id DESC 
            LIMIT 1
        ");
        return $result->fetch_assoc()['cleanup_time'] ?? '从未清理';
    }
}

// 浏览器访问界面
if (isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: text/html; charset=utf-8');
    
    $timer = new OptimizedTimer();
    
    if (isset($_GET['run'])) {
        $result = $timer->runOptimizedTimer();
        
        echo '<!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <title>定时器优化结果</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .info { background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .btn { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
            </style>
        </head>
        <body>
            <h1>⚡ 定时器优化完成</h1>
            
            <div class="success">
                <strong>清理结果：</strong> ' . $result['items_cleaned'] . ' 个物品已清理
                <br><strong>执行时间：</strong> ' . $result['execution_time'] . ' 秒
            </div>
            
            <div class="info">
                <strong>清理详情：</strong>
                <br>清理间隔：' . $result['cleanup_stats']['cleanup_interval'] . ' 分钟
                <br>清理前物品数：' . $result['cleanup_stats']['item_count_before'] . ' 个
            </div>
            
            <p><a href="?" class="btn">返回</a> | <a href="?run=1" class="btn">再次运行</a></p>
        </body>
        </html>';
        exit;
    }
    
    // 显示界面
    $stats = $timer->getPerformanceStats();
    
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <title>定时器优化工具</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; max-width: 600px; }
            .container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .btn { padding: 12px 24px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
            .btn:hover { background: #005a87; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚡ 游戏定时器优化</h1>
            
            <div class="warning">
                <strong>📊 当前状态：</strong>
                <br>地面物品：' . $stats['ground_items'] . ' 个
                <br>数据库大小：' . $stats['database_size'] . ' MB
                <br>最后清理：' . $stats['last_cleanup'] . '
            </div>
            
            <form method="get">
                <button type="submit" name="run" value="1" class="btn">立即运行优化</button>
            </form>
            
            <h3>优化内容：</h3>
            <ul>
                <li>✅ 缩短物品清理间隔（动态调整）</li>
                <li>✅ 批量处理在线用户</li>
                <li>✅ 优化消息清理策略</li>
                <li>✅ 减少数据库查询次数</li>
            </ul>
            
            <p><small>建议每1-2分钟运行一次</small></p>
        </div>
    </body>
    </html>';
    
} else {
    // 命令行模式
    echo "游戏定时器优化脚本\n";
    echo "==================\n";
    
    $timer = new OptimizedTimer();
    $result = $timer->runOptimizedTimer();
    
    if ($result['success']) {
        echo "✅ 优化完成！\n";
        echo "清理物品：{$result['items_cleaned']} 个\n";
        echo "执行时间：{$result['execution_time']} 秒\n";
    } else {
        echo "❌ 优化失败\n";
    }
}
?>
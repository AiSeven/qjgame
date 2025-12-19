<?php
/**
 * 游戏快速优化脚本
 * 一键应用基础优化
 */

class QuickOptimizer {
    
    public function __construct() {
        echo "=== 游戏性能快速优化脚本 ===\n";
        echo "开始执行优化...\n\n";
    }
    
    public function runAllOptimizations() {
        $this->optimizeDatabase();
        $this->optimizeSession();
        $this->optimizeCaching();
        $this->createOptimizationFiles();
        $this->generateReport();
    }
    
    private function optimizeDatabase() {
        echo "1. 数据库优化...\n";
        
        try {
            $mysqli = new mysqli('127.0.0.1', 'root', '123456', 'wapgame', 3306);
            
            if ($mysqli->connect_error) {
                throw new Exception("数据库连接失败: " . $mysqli->connect_error);
            }
            
            $mysqli->set_charset("utf8mb4");
            
            // 执行基础优化
            $optimizations = [
                "ALTER TABLE `user` ADD INDEX IF NOT EXISTS `idx_name` (`name`)",
                "ALTER TABLE `user` ADD INDEX IF NOT EXISTS `idx_sid` (`sid`)",
                "ALTER TABLE `cdk` ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`)",
                "ALTER TABLE `cdk` ADD INDEX IF NOT EXISTS `idx_game` (`game`)",
                "ALTER TABLE `recharge` ADD INDEX IF NOT EXISTS `idx_user_name` (`user_name`)",
                "ALTER TABLE `recharge` ADD INDEX IF NOT EXISTS `idx_time` (`time`)"
            ];
            
            foreach ($optimizations as $sql) {
                try {
                    $mysqli->query($sql);
                    echo "   ✓ 执行: " . substr($sql, 0, 50) . "...\n";
                } catch (Exception $e) {
                    echo "   ⚠ 跳过: " . $e->getMessage() . "\n";
                }
            }
            
            // 优化表
            $mysqli->query("OPTIMIZE TABLE `user`");
            $mysqli->query("OPTIMIZE TABLE `cdk`");
            $mysqli->query("OPTIMIZE TABLE `recharge`");
            
            $mysqli->close();
            echo "   ✓ 数据库优化完成\n\n";
            
        } catch (Exception $e) {
            echo "   ✗ 数据库优化失败: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function optimizeSession() {
        echo "2. 会话优化...\n";
        
        try {
            // 创建会话保存目录
            $session_path = sys_get_temp_dir() . '/game_sessions';
            if (!is_dir($session_path)) {
                mkdir($session_path, 0777, true);
            }
            
            // 设置会话配置
            ini_set('session.save_path', $session_path);
            ini_set('session.gc_maxlifetime', 3600); // 1小时
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', 100);
            
            echo "   ✓ 会话优化完成\n\n";
            
        } catch (Exception $e) {
            echo "   ✗ 会话优化失败: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function optimizeCaching() {
        echo "3. 缓存优化...\n";
        
        try {
            // 检查Redis扩展
            if (!class_exists('Redis')) {
                echo "   ⚠ Redis扩展未安装，跳过Redis缓存优化\n";
                return;
            }
            
            // 测试Redis连接
            $redis = new Redis();
            if ($redis->connect('127.0.0.1', 6379)) {
                if ($redis->auth('sants.cn')) {
                    echo "   ✓ Redis连接成功\n";
                    
                    // 清除旧缓存
                    $redis->flushDB();
                    echo "   ✓ Redis缓存已清除\n";
                    
                    $redis->close();
                } else {
                    echo "   ⚠ Redis认证失败\n";
                }
            } else {
                echo "   ⚠ Redis连接失败\n";
            }
            
            echo "   ✓ 缓存优化完成\n\n";
            
        } catch (Exception $e) {
            echo "   ✗ 缓存优化失败: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function createOptimizationFiles() {
        echo "4. 创建优化配置文件...\n";
        
        // 创建PHP配置文件
        $php_ini_content = "; PHP优化配置
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 50M
upload_max_filesize = 50M

; OPcache配置
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1

; 会话配置
session.gc_maxlifetime = 3600
session.gc_probability = 1
session.gc_divisor = 100

; 错误报告
error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED
log_errors = On
error_log = php_errors.log
";
        
        file_put_contents('php_optimization.ini', $php_ini_content);
        echo "   ✓ PHP优化配置已创建\n";
        
        // 创建MySQL优化配置
        $mysql_ini_content = "# MySQL优化配置
[mysqld]
# 基础配置
innodb_buffer_pool_size = 512M
query_cache_size = 64M
query_cache_type = 1
max_connections = 200
wait_timeout = 28800
interactive_timeout = 28800

# InnoDB配置
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_file_per_table = 1

# 查询优化
tmp_table_size = 64M
max_heap_table_size = 64M
sort_buffer_size = 2M
read_buffer_size = 2M
read_rnd_buffer_size = 8M

# 日志配置
slow_query_log = 1
slow_query_log_file = slow.log
long_query_time = 2
";
        
        file_put_contents('mysql_optimization.ini', $mysql_ini_content);
        echo "   ✓ MySQL优化配置已创建\n";
        
        echo "   ✓ 配置文件创建完成\n\n";
    }
    
    private function generateReport() {
        echo "5. 生成优化报告...\n";
        
        $report = "=== 游戏性能优化报告 ===\n";
        $report .= "生成时间: " . date('Y-m-d H:i:s') . "\n\n";
        
        $report .= "已执行的优化:\n";
        $report .= "- 数据库索引优化\n";
        $report .= "- 会话配置优化\n";
        $report .= "- 缓存系统检查\n";
        $report .= "- 优化配置文件创建\n\n";
        
        $report .= "下一步建议:\n";
        $report .= "1. 重启MySQL服务应用配置\n";
        $report .= "2. 重启Apache服务应用配置\n";
        $report .= "3. 使用db_optimized.php替换原有数据库连接\n";
        $report .= "4. 监控性能改进效果\n\n";
        
        $report .= "性能监控:\n";
        $report .= "- 检查MySQL慢查询日志: slow.log\n";
        $report .= "- 检查PHP错误日志: php_errors.log\n";
        $report .= "- 使用游戏内置的性能监控\n";
        
        file_put_contents('optimization_report.txt', $report);
        echo "   ✓ 优化报告已生成: optimization_report.txt\n\n";
    }
    
    public function showHelp() {
        echo "\n=== 使用说明 ===\n";
        echo "1. 运行此脚本: php quick_optimize.php\n";
        echo "2. 查看生成的配置文件\n";
        echo "3. 根据需要应用到服务器\n";
        echo "4. 重启相关服务\n";
        echo "5. 监控性能改进\n\n";
        
        echo "重要提醒:\n";
        echo "- 运行前请备份数据库\n";
        echo "- 逐步应用优化，观察效果\n";
        echo "- 根据实际负载调整参数\n";
    }
}

// 运行优化
if (php_sapi_name() === 'cli') {
    $optimizer = new QuickOptimizer();
    
    if (isset($argv[1]) && $argv[1] === '--help') {
        $optimizer->showHelp();
    } else {
        $optimizer->runAllOptimizations();
        echo "=== 优化完成 ===\n";
        echo "请查看 optimization_report.txt 获取详细信息\n";
    }
} else {
    echo "请在命令行运行此脚本\n";
}
?>
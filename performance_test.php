<?php
/**
 * 游戏性能测试工具
 * 用于测试优化前后的性能对比
 */

class PerformanceTester {
    private $results = [];
    private $start_time;
    
    public function __construct() {
        $this->start_time = microtime(true);
    }
    
    public function runAllTests() {
        echo "=== 游戏性能测试 ===\n";
        echo "开始时间: " . date('Y-m-d H:i:s') . "\n\n";
        
        $this->testDatabaseConnection();
        $this->testQueryPerformance();
        $this->testMemoryUsage();
        $this->testPageLoadTime();
        
        $this->generateReport();
    }
    
    private function testDatabaseConnection() {
        echo "1. 数据库连接测试...\n";
        
        $start = microtime(true);
        
        try {
            $mysqli = new mysqli('127.0.0.1', 'root', '123456', 'wapgame', 3306);
            
            if ($mysqli->connect_error) {
                throw new Exception("连接失败: " . $mysqli->connect_error);
            }
            
            $connection_time = microtime(true) - $start;
            $mysqli->close();
            
            $this->results['database_connection'] = [
                'status' => 'success',
                'time' => round($connection_time * 1000, 2),
                'message' => '数据库连接正常'
            ];
            
            echo "   ✓ 连接时间: " . $this->results['database_connection']['time'] . "ms\n";
            
        } catch (Exception $e) {
            $this->results['database_connection'] = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
            echo "   ✗ " . $e->getMessage() . "\n";
        }
    }
    
    private function testQueryPerformance() {
        echo "2. 查询性能测试...\n";
        
        try {
            $mysqli = new mysqli('127.0.0.1', 'root', '123456', 'wapgame', 3306);
            
            // 测试用户表查询
            $start = microtime(true);
            $result = $mysqli->query("SELECT COUNT(*) as total FROM user");
            $count_time = microtime(true) - $start;
            
            $user_count = $result->fetch_assoc()['total'];
            
            // 测试索引查询
            $start = microtime(true);
            $result = $mysqli->query("SELECT id, name FROM user WHERE name LIKE 'test%' LIMIT 10");
            $index_time = microtime(true) - $start;
            
            $this->results['query_performance'] = [
                'user_count' => $user_count,
                'count_query_time' => round($count_time * 1000, 2),
                'index_query_time' => round($index_time * 1000, 2),
                'status' => 'success'
            ];
            
            echo "   ✓ 用户总数: " . $user_count . "\n";
            echo "   ✓ 计数查询: " . $this->results['query_performance']['count_query_time'] . "ms\n";
            echo "   ✓ 索引查询: " . $this->results['query_performance']['index_query_time'] . "ms\n";
            
            $mysqli->close();
            
        } catch (Exception $e) {
            $this->results['query_performance'] = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
            echo "   ✗ " . $e->getMessage() . "\n";
        }
    }
    
    private function testMemoryUsage() {
        echo "3. 内存使用测试...\n";
        
        $initial_memory = memory_get_usage(true);
        $initial_peak = memory_get_peak_usage(true);
        
        // 模拟游戏数据加载
        $test_data = [];
        for ($i = 0; $i < 1000; $i++) {
            $test_data[] = [
                'id' => $i,
                'name' => 'user_' . $i,
                'data' => str_repeat('x', 100)
            ];
        }
        
        $final_memory = memory_get_usage(true);
        $final_peak = memory_get_peak_usage(true);
        
        $this->results['memory_usage'] = [
            'initial_memory' => $this->formatBytes($initial_memory),
            'final_memory' => $this->formatBytes($final_memory),
            'memory_used' => $this->formatBytes($final_memory - $initial_memory),
            'peak_memory' => $this->formatBytes($final_peak),
            'status' => 'success'
        ];
        
        echo "   ✓ 初始内存: " . $this->results['memory_usage']['initial_memory'] . "\n";
        echo "   ✓ 最终内存: " . $this->results['memory_usage']['final_memory'] . "\n";
        echo "   ✓ 使用内存: " . $this->results['memory_usage']['memory_used'] . "\n";
        echo "   ✓ 峰值内存: " . $this->results['memory_usage']['peak_memory'] . "\n";
    }
    
    private function testPageLoadTime() {
        echo "4. 页面加载时间测试...\n";
        
        $test_urls = [
            'index.php' => '首页',
            'login.php' => '登录页',
            'wapgame.php' => '游戏页'
        ];
        
        $base_url = 'http://localhost/';
        
        foreach ($test_urls as $url => $name) {
            $start = microtime(true);
            
            // 使用file_get_contents测试页面加载
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET'
                ]
            ]);
            
            $content = @file_get_contents($base_url . $url, false, $context);
            $load_time = microtime(true) - $start;
            
            $this->results['page_load_time'][$url] = [
                'name' => $name,
                'load_time' => round($load_time * 1000, 2),
                'size' => $content ? strlen($content) : 0,
                'status' => $content ? 'success' : 'failed'
            ];
            
            echo "   ✓ {$name}: " . $this->results['page_load_time'][$url]['load_time'] . "ms";
            if ($content) {
                echo " (" . $this->formatBytes(strlen($content)) . ")";
            }
            echo "\n";
        }
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function generateReport() {
        $total_time = microtime(true) - $this->start_time;
        
        $report = "=== 性能测试报告 ===\n";
        $report .= "测试完成时间: " . date('Y-m-d H:i:s') . "\n";
        $report .= "总测试时间: " . round($total_time, 2) . "秒\n\n";
        
        $report .= "数据库连接: " . ($this->results['database_connection']['status'] === 'success' ? "正常" : "失败") . "\n";
        $report .= "查询性能: " . ($this->results['query_performance']['status'] === 'success' ? "正常" : "失败") . "\n";
        
        if (isset($this->results['query_performance']['user_count'])) {
            $report .= "用户总数: " . $this->results['query_performance']['user_count'] . "\n";
        }
        
        $report .= "\n性能指标:\n";
        $report .= "- 数据库连接时间: " . ($this->results['database_connection']['time'] ?? 'N/A') . "ms\n";
        $report .= "- 查询响应时间: " . ($this->results['query_performance']['count_query_time'] ?? 'N/A') . "ms\n";
        
        $report .= "\n页面加载时间:\n";
        foreach ($this->results['page_load_time'] as $url => $data) {
            $report .= "- {$data['name']}: {$data['load_time']}ms\n";
        }
        
        $report .= "\n内存使用:\n";
        $report .= "- 峰值内存: " . ($this->results['memory_usage']['peak_memory'] ?? 'N/A') . "\n";
        
        $report .= "\n优化建议:\n";
        
        if (isset($this->results['query_performance']['count_query_time']) && 
            $this->results['query_performance']['count_query_time'] > 100) {
            $report .= "- 查询响应时间较慢，建议添加更多索引\n";
        }
        
        if (isset($this->results['page_load_time'])) {
            $slow_pages = array_filter($this->results['page_load_time'], function($page) {
                return $page['load_time'] > 2000;
            });
            
            if (!empty($slow_pages)) {
                $report .= "- 页面加载时间超过2秒，建议启用缓存\n";
            }
        }
        
        $report .= "- 定期运行此测试监控性能改进\n";
        
        file_put_contents('performance_report.txt', $report);
        echo "\n" . $report;
        
        echo "\n=== 测试完成 ===\n";
        echo "详细报告已保存到: performance_report.txt\n";
    }
}

// 运行测试
if (isset($_GET['run'])) {
    $tester = new PerformanceTester();
    $tester->runAllTests();
} else {
    echo '<h1>游戏性能测试工具</h1>';
    echo '<p>点击开始测试游戏性能:</p>';
    echo '<a href="?run=1" style="padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 5px;">开始性能测试</a>';
    echo '<p><small>测试可能需要几秒钟完成</small></p>';
    
    if (file_exists('performance_report.txt')) {
        echo '<h2>上次测试结果</h2>';
        echo '<pre>' . htmlspecialchars(file_get_contents('performance_report.txt')) . '</pre>';
    }
}
?>
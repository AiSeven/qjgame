<?php
/**
 * 优化后的数据库连接类
 * 提供连接池、缓存和性能监控功能
 */

class OptimizedDatabase {
    private static $instance = null;
    private $mysqli;
    private $redis;
    private $config;
    private $query_count = 0;
    private $query_log = [];
    
    private function __construct() {
        $this->config = [
            'mysql' => [
                'host' => '127.0.0.1',
                'username' => 'root',
                'password' => '123456',
                'database' => 'wapgame',
                'port' => 3306,
                'charset' => 'utf8mb4',
                'persistent' => true
            ],
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'password' => 'sants.cn',
                'timeout' => 2.5
            ],
            'cache' => [
                'ttl' => 300, // 5分钟缓存
                'prefix' => 'game_'
            ]
        ];
        
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // MySQL连接
            $mysql_config = $this->config['mysql'];
            $this->mysqli = new mysqli(
                $mysql_config['host'],
                $mysql_config['username'],
                $mysql_config['password'],
                $mysql_config['database'],
                $mysql_config['port']
            );
            
            if ($this->mysqli->connect_error) {
                throw new Exception("MySQL连接失败: " . $this->mysqli->connect_error);
            }
            
            $this->mysqli->set_charset($mysql_config['charset']);
            
            // Redis连接
            $redis_config = $this->config['redis'];
            $this->redis = new Redis();
            $this->redis->connect(
                $redis_config['host'],
                $redis_config['port'],
                $redis_config['timeout']
            );
            
            if ($redis_config['password']) {
                $this->redis->auth($redis_config['password']);
            }
            
        } catch (Exception $e) {
            error_log("数据库连接错误: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function query($sql, $use_cache = true, $cache_ttl = null) {
        $start_time = microtime(true);
        
        try {
            if ($use_cache && $this->isSelectQuery($sql)) {
                $cache_key = $this->getCacheKey($sql);
                $cached = $this->redis->get($cache_key);
                
                if ($cached !== false) {
                    $this->logQuery($sql, microtime(true) - $start_time, true);
                    return unserialize($cached);
                }
            }
            
            $result = $this->mysqli->query($sql);
            
            if (!$result) {
                throw new Exception("查询错误: " . $this->mysqli->error);
            }
            
            if ($use_cache && $this->isSelectQuery($sql)) {
                $cache_key = $this->getCacheKey($sql);
                $ttl = $cache_ttl ?: $this->config['cache']['ttl'];
                $this->redis->setex($cache_key, $ttl, serialize($result));
            }
            
            $this->query_count++;
            $this->logQuery($sql, microtime(true) - $start_time, false);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("查询执行错误: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function prepare($sql) {
        return $this->mysqli->prepare($sql);
    }
    
    public function escape($string) {
        return $this->mysqli->real_escape_string($string);
    }
    
    public function getUserBySid($sid) {
        $cache_key = "user_sid_" . md5($sid);
        $cached = $this->redis->get($cache_key);
        
        if ($cached !== false) {
            return unserialize($cached);
        }
        
        $sid = $this->escape($sid);
        $sql = "SELECT id, name, password, mobile, last_login_time, community, open_id 
                FROM user 
                WHERE sid = '{$sid}' LIMIT 1";
        
        $result = $this->query($sql, false);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $this->redis->setex($cache_key, 600, serialize($user)); // 10分钟缓存
            return $user;
        }
        
        return null;
    }
    
    public function getUserCDKs($user_id, $game = null) {
        $cache_key = "user_cdks_" . $user_id . "_" . ($game ?: 'all');
        $cached = $this->redis->get($cache_key);
        
        if ($cached !== false) {
            return unserialize($cached);
        }
        
        $user_id = (int)$user_id;
        $where = "user_id = {$user_id}";
        
        if ($game) {
            $game = $this->escape($game);
            $where .= " AND game = '{$game}'";
        }
        
        $sql = "SELECT * FROM cdk WHERE {$where} ORDER BY use_time DESC";
        $result = $this->query($sql);
        
        $cdks = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cdks[] = $row;
            }
        }
        
        $this->redis->setex($cache_key, 300, serialize($cdks)); // 5分钟缓存
        return $cdks;
    }
    
    public function updateUserSession($user_id, $sid) {
        $user_id = (int)$user_id;
        $sid = $this->escape($sid);
        
        $sql = "UPDATE user SET sid = '{$sid}', last_login_time = NOW() WHERE id = {$user_id} LIMIT 1";
        $result = $this->query($sql, false);
        
        // 清除用户缓存
        $this->clearUserCache($user_id);
        
        return $result;
    }
    
    public function clearUserCache($user_id) {
        $patterns = [
            "user_sid_*",
            "user_cdks_{$user_id}_*"
        ];
        
        foreach ($patterns as $pattern) {
            $keys = $this->redis->keys($this->config['cache']['prefix'] . $pattern);
            foreach ($keys as $key) {
                $this->redis->del(str_replace($this->config['cache']['prefix'], '', $key));
            }
        }
    }
    
    public function getStats() {
        return [
            'query_count' => $this->query_count,
            'slow_queries' => array_filter($this->query_log, function($q) {
                return $q['time'] > 1.0; // 超过1秒的查询
            }),
            'cache_hits' => count(array_filter($this->query_log, function($q) {
                return $q['cached'];
            }))
        ];
    }
    
    private function isSelectQuery($sql) {
        return stripos(trim($sql), 'SELECT') === 0;
    }
    
    private function getCacheKey($sql) {
        return $this->config['cache']['prefix'] . md5($sql);
    }
    
    private function logQuery($sql, $time, $cached) {
        $this->query_log[] = [
            'sql' => $sql,
            'time' => $time,
            'cached' => $cached,
            'timestamp' => microtime(true)
        ];
        
        // 只保留最近100条查询
        if (count($this->query_log) > 100) {
            array_shift($this->query_log);
        }
    }
    
    public function __destruct() {
        if ($this->mysqli) {
            $this->mysqli->close();
        }
        if ($this->redis) {
            $this->redis->close();
        }
    }
}

/**
 * 简化的数据库访问类
 * 用于替换原有的全局mysqli对象
 */
class GameDB {
    private static $db = null;
    
    public static function init() {
        if (self::$db === null) {
            self::$db = OptimizedDatabase::getInstance();
        }
    }
    
    public static function query($sql) {
        self::init();
        return self::$db->query($sql);
    }
    
    public static function escape($string) {
        self::init();
        return self::$db->escape($string);
    }
    
    public static function getUserBySid($sid) {
        self::init();
        return self::$db->getUserBySid($sid);
    }
    
    public static function getStats() {
        self::init();
        return self::$db->getStats();
    }
}

// 初始化全局数据库连接
GameDB::init();
?>
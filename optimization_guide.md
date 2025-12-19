# 游戏性能优化指南

## 发现的主要性能问题

### 1. 数据库连接问题
- 每次请求都重新建立数据库连接
- 没有使用连接池
- 没有持久化连接

### 2. 查询优化问题
- 缺乏索引优化
- 查询没有优化
- 大量全表扫描
### 3. 缓存问题
- 没有使用查询缓存
- 没有使用Redis缓存常用数据
- 重复查询相同数据
### 4. 代码结构问题
- 大量同步操作
- 没有异步处理
- 资源没有复用
## 优化方案

### 1. 数据库优化

#### 添加索引
```sql
-- 用户表索引优化
ALTER TABLE `user` ADD INDEX `idx_name` (`name`);
ALTER TABLE `user` ADD INDEX `idx_sid` (`sid`);
ALTER TABLE `user` ADD INDEX `idx_community` (`community`);
ALTER TABLE `user` ADD INDEX `idx_last_login` (`last_login_time`);

-- CDK表索引优化
ALTER TABLE `cdk` ADD INDEX `idx_cdk` (`cdk`);
ALTER TABLE `cdk` ADD INDEX `idx_user_id` (`user_id`);
ALTER TABLE `cdk` ADD INDEX `idx_is_use` (`is_use`);
ALTER TABLE `cdk` ADD INDEX `idx_game` (`game`);

-- 充值表索引优化
ALTER TABLE `recharge` ADD INDEX `idx_user_name` (`user_name`);
ALTER TABLE `recharge` ADD INDEX `idx_game` (`game`);
ALTER TABLE `recharge` ADD INDEX `idx_time` (`time`);
ALTER TABLE `recharge` ADD INDEX `idx_community` (`community`);
```

#### 数据库配置优化
```ini
[mysqld]
# 内存配置
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2

# 查询缓存
query_cache_size = 64M
query_cache_type = 1

# 连接池
max_connections = 200
wait_timeout = 28800
interactive_timeout = 28800
```

### 2. PHP优化

#### 创建数据库连接池
```php
<?php
class DatabasePool {
    private static $instance = null;
    private $connections = [];
    private $config;
    
    private function __construct() {
        $this->config = [
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => '123456',
            'database' => 'wapgame',
            'port' => 3306,
            'charset' => 'utf8mb4'
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        $key = md5(serialize($this->config));
        if (!isset($this->connections[$key]) || !$this->connections[$key]->ping()) {
            $this->connections[$key] = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                $this->config['database'],
                $this->config['port']
            );
            $this->connections[$key]->set_charset($this->config['charset']);
        }
        return $this->connections[$key];
    }
}
?>
```

### 3. 缓存优化

#### Redis缓存配置
```php
<?php
class CacheManager {
    private static $redis = null;
    
    public static function getRedis() {
        if (self::$redis === null) {
            self::$redis = new Redis();
            self::$redis->connect('127.0.0.1', 6379);
            self::$redis->auth('sants.cn');
        }
        return self::$redis;
    }
    
    public static function get($key) {
        $redis = self::getRedis();
        return $redis->get($key);
    }
    
    public static function set($key, $value, $ttl = 3600) {
        $redis = self::getRedis();
        return $redis->setex($key, $ttl, $value);
    }
}
?>
```

### 4. 代码优化

#### 优化后的数据库查询类
```php
<?php
class OptimizedDB {
    private static $instance = null;
    private $mysqli;
    private $cache;
    
    private function __construct() {
        $this->mysqli = DatabasePool::getInstance()->getConnection();
        $this->cache = CacheManager::getRedis();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql, $use_cache = true) {
        $cache_key = md5($sql);
        
        if ($use_cache && $cached = $this->cache->get($cache_key)) {
            return unserialize($cached);
        }
        
        $result = $this->mysqli->query($sql);
        
        if ($use_cache && $result) {
            $this->cache->setex($cache_key, 300, serialize($result));
        }
        
        return $result;
    }
}
?>
```

### 5. 服务器优化

#### Apache配置优化
```apache
# 启用压缩
LoadModule deflate_module modules/mod_deflate.so
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/js application/javascript application/x-javascript
</IfModule>

# 启用缓存
LoadModule expires_module modules/mod_expires.so
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>

# 优化MPM
<IfModule mpm_prefork_module>
    StartServers 5
    MinSpareServers 5
    MaxSpareServers 10
    MaxRequestWorkers 256
    MaxConnectionsPerChild 1000
</IfModule>
```

### 6. 前端优化

#### 压缩和合并CSS/JS
```html
<!-- 合并所有CSS文件 -->
<link href="res/css/combined.min.css" rel="stylesheet">

<!-- 合并所有JS文件 -->
<script src="res/js/combined.min.js"></script>
```

## 实施步骤

1. **第一步：数据库优化**
   - 执行索引优化SQL
   - 调整MySQL配置
   - 重启MySQL服务

2. **第二步：PHP代码优化**
   - 实施数据库连接池
   - 添加Redis缓存
   - 优化查询逻辑

3. **第三步：服务器优化**
   - 配置Apache优化
   - 启用压缩和缓存
   - 调整MPM设置

4. **第四步：监控和测试**
   - 使用性能监控工具
   - 测试响应时间
   - 监控数据库查询

## 性能监控

使用以下工具监控性能：
- MySQL慢查询日志
- Apache访问日志
- PHP错误日志
- Redis监控工具

## 预期效果

实施这些优化后，预期能够：
- 减少50-70%的数据库查询时间
- 减少30-50%的页面加载时间
- 提高并发处理能力
- 减少服务器资源消耗
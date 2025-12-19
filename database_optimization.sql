-- 游戏数据库优化脚本
-- 运行前请备份数据库

-- 1. 用户表优化
USE wapgame;

-- 为用户表添加复合索引
ALTER TABLE `user` 
ADD INDEX `idx_name_password` (`name`, `password`),
ADD INDEX `idx_sid_time` (`sid`, `last_login_time`),
ADD INDEX `idx_community_openid` (`community`, `open_id`);

-- 优化用户表结构
ALTER TABLE `user` 
MODIFY COLUMN `password` VARCHAR(255) NOT NULL DEFAULT '12345678',
MODIFY COLUMN `sid` VARCHAR(255) DEFAULT NULL,
MODIFY COLUMN `open_id` VARCHAR(255) DEFAULT NULL;

-- 2. CDK表优化
ALTER TABLE `cdk` 
ADD INDEX `idx_game_is_use` (`game`, `is_use`),
ADD INDEX `idx_user_game` (`user_id`, `game`),
ADD INDEX `idx_create_time` (`create_time`),
ADD INDEX `idx_event_game` (`event`, `game`);

-- 3. 充值表优化
ALTER TABLE `recharge` 
ADD INDEX `idx_user_time` (`user_name`, `time`),
ADD INDEX `idx_game_area` (`game`, `area`),
ADD INDEX `idx_community_time` (`community`, `time`),
ADD INDEX `idx_order_unique` (`order`);

-- 4. 游戏区服表优化（如果存在）
-- 假设存在游戏数据表
CREATE TABLE IF NOT EXISTS `game_data` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `game_area` INT NOT NULL,
  `data_key` VARCHAR(100) NOT NULL,
  `data_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_area` (`user_id`, `game_area`),
  INDEX `idx_key_area` (`data_key`, `game_area`),
  INDEX `idx_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. 会话表优化
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_id` VARCHAR(255) PRIMARY KEY,
  `user_id` INT NOT NULL,
  `game_area` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP,
  INDEX `idx_user_area` (`user_id`, `game_area`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. 缓存表优化
CREATE TABLE IF NOT EXISTS `cache_data` (
  `cache_key` VARCHAR(255) PRIMARY KEY,
  `cache_value` TEXT,
  `expires_at` TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. 日志表优化
CREATE TABLE IF NOT EXISTS `game_logs` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `action` VARCHAR(100),
  `game_area` INT,
  `details` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_action` (`user_id`, `action`),
  INDEX `idx_area_time` (`game_area`, `created_at`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. 优化表结构
-- 分析表
ANALYZE TABLE `user`;
ANALYZE TABLE `cdk`;
ANALYZE TABLE `recharge`;

-- 优化表
OPTIMIZE TABLE `user`;
OPTIMIZE TABLE `cdk`;
OPTIMIZE TABLE `recharge`;

-- 9. 创建存储过程优化查询
DELIMITER //

-- 获取用户信息的存储过程
CREATE PROCEDURE IF NOT EXISTS `GetUserBySid`(IN p_sid VARCHAR(255))
BEGIN
  SELECT id, name, password, mobile, last_login_time, community, open_id
  FROM user 
  WHERE sid = p_sid 
  LIMIT 1;
END //

-- 获取用户CDK列表的存储过程
CREATE PROCEDURE IF NOT EXISTS `GetUserCDKs`(IN p_user_id INT, IN p_game VARCHAR(128))
BEGIN
  SELECT * FROM cdk 
  WHERE user_id = p_user_id AND game = p_game
  ORDER BY use_time DESC;
END //

-- 获取充值记录的存储过程
CREATE PROCEDURE IF NOT EXISTS `GetRechargeHistory`(IN p_user_name VARCHAR(32), IN p_game VARCHAR(64))
BEGIN
  SELECT * FROM recharge 
  WHERE user_name = p_user_name AND game = p_game
  ORDER BY time DESC
  LIMIT 50;
END //

DELIMITER ;

-- 10. 设置数据库性能参数
-- 注意：以下参数需要根据服务器内存调整

-- 查看当前配置
SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
SHOW VARIABLES LIKE 'query_cache_size';
SHOW VARIABLES LIKE 'max_connections';

-- 建议的MySQL配置（添加到my.ini或my.cnf）
-- [mysqld]
-- innodb_buffer_pool_size = 1G
-- query_cache_size = 64M
-- query_cache_type = 1
-- max_connections = 200
-- innodb_log_file_size = 256M
-- innodb_flush_log_at_trx_commit = 2
-- wait_timeout = 28800
-- interactive_timeout = 28800
@echo off
chcp 65001
cls
echo ================================================
echo        游戏性能优化指南
echo ================================================
echo.
echo 当前检测到的问题：
echo 1. 数据库查询没有优化
echo 2. 缺少索引
echo 3. 没有使用缓存
echo 4. 服务器配置需要调整
echo.
echo ================================================
echo 第一步：数据库优化
echo ================================================
echo 请按以下步骤操作：
echo.
echo 1. 打开小皮面板
ping 127.0.0.1 -n 2 > nul
start http://localhost/phpmyadmin/
echo.
echo 2. 在phpMyAdmin中执行以下SQL：
echo    USE wapgame;
echo    ALTER TABLE `user` ADD INDEX `idx_name` (`name`);
echo    ALTER TABLE `user` ADD INDEX `idx_sid` (`sid`);
echo    ALTER TABLE `cdk` ADD INDEX `idx_user_id` (`user_id`);
echo    ALTER TABLE `cdk` ADD INDEX `idx_game` (`game`);
echo    ALTER TABLE `recharge` ADD INDEX `idx_user_name` (`user_name`);
echo    ALTER TABLE `recharge` ADD INDEX `idx_time` (`time`);
echo    OPTIMIZE TABLE `user`, `cdk`, `recharge`;
echo.
echo 3. 或者导入 database_optimization.sql 文件
echo.
echo ================================================
echo 第二步：服务器配置优化
echo ================================================
echo.
echo 1. 找到小皮面板的Apache配置目录：
echo    C:\xampp\apache\conf\ 或类似路径
echo.
echo 2. 将 apache_optimization.conf 的内容添加到 httpd.conf 末尾
echo.
echo 3. 重启Apache服务
echo.
echo ================================================
echo 第三步：PHP配置优化
echo ================================================
echo.
echo 1. 找到小皮面板的PHP配置文件：
echo    php.ini（通常在小皮面板中可以编辑）
echo.
echo 2. 修改以下配置：
echo    memory_limit = 256M
echo    max_execution_time = 300
echo    upload_max_filesize = 50M
echo    post_max_size = 50M
echo.
echo 3. 启用OPcache：
echo    opcache.enable = 1
echo    opcache.memory_consumption = 128
echo    opcache.max_accelerated_files = 4000
echo.
echo ================================================
echo 第四步：代码优化
echo ================================================
echo.
echo 1. 备份原始文件：
echo    复制 config.php 为 config.php.backup
echo    复制 sanguo/app/conf/config.php 为 config.php.backup
echo.
echo 2. 修改数据库连接配置：
echo    在 config.php 中启用持久连接：
echo    $mysqli = new mysqli('p:127.0.0.1', 'root', '123456', 'wapgame', 3306);
echo.
echo 3. 使用优化后的数据库类：
echo    将 db_optimized.php 包含到项目中
echo.
echo ================================================
echo 第五步：性能监控
echo ================================================
echo.
echo 1. 启用MySQL慢查询日志：
echo    在小皮面板的MySQL配置中添加：
echo    slow_query_log = 1
echo    slow_query_log_file = slow.log
echo    long_query_time = 2
echo.
echo 2. 测试游戏响应时间
echo.
echo ================================================
echo 优化文件说明：
echo ================================================
echo.
echo optimization_guide.md    - 完整的优化指南
echo database_optimization.sql   - 数据库优化SQL
echo db_optimized.php          - 优化数据库连接类
echo apache_optimization.conf  - Apache配置优化
echo php_optimization.ini      - PHP优化配置
echo mysql_optimization.ini    - MySQL优化配置
echo optimization_report.txt    - 优化报告
echo.
echo ================================================
echo 完成后请重启所有服务！
echo ================================================
pause
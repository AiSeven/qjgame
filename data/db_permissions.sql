-- 数据库权限设置SQL语句
-- 用于解决宝塔环境中的数据库连接权限问题

-- 为sg_mp001用户设置localhost访问权限
GRANT ALL PRIVILEGES ON sg_mp001.* TO 'sg_mp001'@'localhost' IDENTIFIED BY '123456';

-- 为sg_mp001用户设置127.0.0.1访问权限
GRANT ALL PRIVILEGES ON sg_mp001.* TO 'sg_mp001'@'127.0.0.1' IDENTIFIED BY '123456';

-- 为wapgame用户设置localhost访问权限
GRANT ALL PRIVILEGES ON wapgame.* TO 'wapgame'@'localhost' IDENTIFIED BY '123456';

-- 为wapgame用户设置127.0.0.1访问权限
GRANT ALL PRIVILEGES ON wapgame.* TO 'wapgame'@'127.0.0.1' IDENTIFIED BY '123456';

-- 刷新权限表
FLUSH PRIVILEGES;

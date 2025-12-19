-- 游戏物品一键清理SQL
-- 使用方法：
-- 1. 双击这个文件
-- 2. 或者复制到phpMyAdmin执行

-- 清理地面物品（30分钟前的掉落物品）
DELETE FROM game_prop WHERE map_id > 0 AND time < DATE_SUB(NOW(), INTERVAL 30 MINUTE);

-- 清理地图物品值
DELETE FROM game_value WHERE valuename LIKE 'map.%i.%' AND time < DATE_SUB(NOW(), INTERVAL 30 MINUTE);

-- 显示清理结果
SELECT CONCAT('✅ 清理完成！共清理 ', 
              (SELECT ROW_COUNT() FROM (SELECT 1) as t1) + 
              (SELECT ROW_COUNT() FROM (SELECT 1) as t2), 
              ' 个物品') as 清理结果;

-- 优化表
OPTIMIZE TABLE game_prop;
OPTIMIZE TABLE game_value;
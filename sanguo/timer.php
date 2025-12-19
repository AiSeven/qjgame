<?php
//忽略危险
error_reporting(E_ALL || ~E_NOTICE);
//离线执行
ignore_user_abort(true);
//永不超时
set_time_limit(0);
//接收参数
$timer_run_dir_name = "wapgame.run";
if ($_GET['mode']) {
    fopen($timer_run_dir_name, "w");
} else {
    unlink($timer_run_dir_name);
    exit;
}
//引入游戏文件
//配置文件
require_once("app/conf/config.php");
//系统函数
require_once("app/sys/system.php");
//系统类
require_once("app/sys/class.php");
//事件系统
require_once("app/sys/event.php");
//任务系统
require_once("app/sys/task.php");
//用户ID
$uid = 0;
//连接数据库
$mysqli = new mysqli($mysql_host, $mysql_user_name, $mysql_passwd, $game_area_dbname, $mysql_port);
//数据库连接成功
if (!$mysqli->errno) {
    //执行编码
    $mysqli->query("SET NAMES UTF8MB4");
    //连接Redis数据库
    $redis = new Redis();
    $redis->open($re_host, $re_port);
    $redis->auth($re_pass);
    $redis_onload = false;
    while (!$redis_onload) {
        $redis_ready = $redis->info("persistence");
        if ($redis_ready["loading"] == 0) {
            $redis_onload = true;
        }
        sleep(30);
    }
    //是否循环
    $database_arr=array();
    while (file_exists($timer_run_dir_name)) {
        //开始微秒
        $start_microtime = microtime_float();
        //开始循环
        foreach ($mysql_game_area_db_name as $game_area_dbname) {
            //设置数据库
            $mysqli->query("USE $game_area_dbname");
            //设置Redis数据库
            $re_db = $re_db_name[$game_area_dbname];
            $redis->select($re_db);
            if (!$database_arr["{$game_area_dbname}_db_ok"]) {
                if ($redis->get("game_area_name") == $game_area_dbname) {
                    $database_arr["{$game_area_dbname}_db_ok"] = 1;
                }
            }
            if ($database_arr["{$game_area_dbname}_db_ok"]) {
                //系统变量
                //现行时间
                $now_time = time();
                //玩家操作
                $user_sql = "";
                //在线玩家操作
                $user_sql = "SELECT `id`,`is_pk`,`in_ctm` FROM `game_user` WHERE `is_online` = 1";
                $result = $mysqli->query($user_sql);
                while (list($o_user_id, $is_pk, $in_ctm) = $result->fetch_row()) {
                    //战斗系统
                    //玩家是否战斗中
                    if ($is_pk) {
                        //是否玩家PK或组队PKNPC
                        if ($in_ctm == "ctm_pk_user" || $in_ctm == "ctm_pk_team_npc") {
                            e82($o_user_id, true);
                        }
                    }
                }
                //开始竞技
                $jingji_sql = "SELECT id FROM game_sports WHERE status=0 AND UNIX_TIMESTAMP(time)<" . ($now_time - 10);
                $result = $mysqli->query($jingji_sql);
                while (list($sports_id) = $result->fetch_row()) {
                    sports::enter_the_sports($sports_id);
                }
                //秒钟定时事件
                value::set_system_value('loop.second', $now_time);
                //分钟定时事件
                $loop_minute = value::get_system_value('loop.minute');
                if ($loop_minute < $now_time - 60) {
                    value::set_system_value("loop.minute", $now_time);
                    minute_loop();
                    //小时定时事件
                    $loop_minute = value::get_system_value('loop.hour');
                    if ($loop_minute < $now_time - 3600) {
                        value::set_system_value("loop.hour", $now_time);
                        hour_loop();
                        //每日定时事件
                        $today = date("Y-m-d");
                        $loop_day = value::get_system_value('loop.day');
                        if ($loop_day != $today) {
                            value::set_system_value("loop.day", $today);
                            day_loop();
                        }
                    }
                }
                //系统事件
                //循环事件
                //等待微秒
                $sleep_microtime = $start_microtime + 1000000 - microtime_float();
                value::set_system_value('sleep_microtime', $sleep_microtime);
            }
        }
        //开始等待
        usleep($sleep_microtime > 0 ? $sleep_microtime : 0);
        //清除缓存
        clearstatcache();
    }
    //关闭数据库
    $mysqli->close();
    $redis->close();
    //退出循环
    exit;
}

//每日定时事件
function day_loop()
{
    //系统操作
    //删除一个月前的消息记录
    $sql = "DELETE FROM `game_chat` WHERE `id` > 0 AND `time` < '" . date("Y-m-d 00:00:00", strtotime("-1 month")) . "';";
    sql($sql);
    //玩家操作
    $user_sql = "SELECT `id`,`is_pk`,`in_ctm` FROM `game_user` WHERE 1";
    $result = sql($user_sql);
    while (list($user_id) = $result->fetch_row()) {
        //限额奖励
        value::set_user_value("today.exp", 0, $user_id);
        value::set_user_value("today.jq", 0, $user_id);
    }
    //攻城每天删除事件
    value::set_system_value('map_gcgw_cs', 0);
    value::set_system_value('gcgw_hb_je', 0);
    value::set_system_value('gcgw_hb_cs', 0);
    value::set_system_value('map_gcgw_xc_id', 5039);
    value::set_system_value('gczz_id', 0);
    value::set_system_value('gczz_id2', 0);
    value::set_system_value('gczz_id3', 0);
    value::set_system_value('map_gcgw_id9', 0);
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.mj_yysl_csid' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.pk_sw_cs' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.map_jymj_cs' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.gwgc_sh' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.gc_gjgw_gj' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.gc_gjgw_fy' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.gc_jb_gjgw' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.gc_yb_gjgw' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.gc_jb_fygw' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.gc_yb_fygw' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    //每天删除称号
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.using_nick_name' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.ch_sx_gj' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.ch_sx_fy' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.ch_sx_hp' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.yl_smc_zg_dc' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.yl_sz_zg_dc' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.hd_hyd_sz' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.sc_money' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `valuename` LIKE '%.map_gcgw_cs' ORDER BY CONVERT(`value`,SIGNED) DESC ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    //在线礼包每天更新属性
    $lb_qd_xt_xqj = date("w");
    $lb_qd_xt_d1 = date("d");
    value::set_system_value('lb_qd_xt_day', $lb_qd_xt_day);
    value::set_system_value('lb_qd_xt_d', $lb_qd_xt_d1);
    $lb_zx_fz_xt_day2 = date("d");
    $lb_zx_fz_xt_day1 = value::get_system_value('lb_zx_fz_xt_day1');
    value::set_system_value('lb_zx_fz_xt_day', $lb_zx_fz_xt_day2);
    value::set_system_value('lb_zx_fz_xt_day1', $lb_zx_fz_xt_day2);
    //娱乐砸金蛋每日更新
    $yl_zjd_xz_day = date("d");
    value::set_system_value('yl_zjd_day', $yl_zjd_xz_day);
    value::set_system_value('yl_zjd_zg_cs', 0);
    value::set_system_value('yl_zjd_zg_money', 0);
    value::set_system_value('yl_zjd_zg_lingshi', 0);
    value::set_system_value('yl_smc_zg_cs', 0);
    value::set_system_value('yl_smc_zg_dc', 0);
    value::set_system_value('yl_smc_zg_money', 0);
    value::set_system_value('yl_smc_zg_lingshi', 0);
    value::set_system_value('yl_sz_zg_cs', 0);
    value::set_system_value('yl_sz_zg_dc', 0);
    value::set_system_value('yl_sz_zg_money', 0);
    value::set_system_value('yl_sz_zg_lingshi', 0);
    value::set_system_value('yl_jj_zg_cs', 0);
    value::set_system_value('yl_jj_zg_money', 0);
    value::set_system_value('yl_jj_zg_lingshi', 0);
    $sql = "SELECT `id`,`userid`,`value` FROM `game_user_value` WHERE `value`=0 ";
    $result = sql($sql);
    while (list($oid, $userid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_user_value` WHERE `id`={$oid} LIMIT 1");
    }
    $sql = "SELECT `id`,`value` FROM `game_value` WHERE `value`=0 ";
    $result = sql($sql);
    while (list($oid, $ovalue) = $result->fetch_row()) {
        sql("DELETE FROM `game_value` WHERE `id`={$oid} LIMIT 1");
    }
    return 1;
}

//小时定时事件
function hour_loop()
{
    //消息系统
    //广播系统
    //公告
    c_add_guangbo("<span style=color:green>请您记住绿色传奇官网仙盟会手机浏览器可直接游戏,官方qq群：738323424〈新手指引及详细攻略请参考官网及官群〉</span>");
    //删除已读
    $sql = "DELETE FROM `game_chat` WHERE `is_look`=1";
    sql($sql);
    $bx_time = mt_rand(0, 59);
    value::set_system_value('bx_time', $bx_time);
    //帮派系统
    //减少帮派灵气
    sql("UPDATE `game_union` SET `lingqi`=`lingqi`-1000 WHERE `lingqi`>=1000");
    //小时减少红名
    $user_sql = "SELECT `user_id` FROM `game_user` WHERE `is_online` = 1";
    $result = sql($user_sql);
    while (list($user_id) = $result->fetch_row()) {
        $hongming = value::get_user_value('hongming', $user_id);
        if ($hongming > 0) {
            value::add_user_value('hongming', -1, $user_id);
        }
    }
    //会员之家
    $time_h = date("H");
    if ($time_h % 2 != 0) {
        //会员之家一层boss刷新
        $sql = "SELECT `id` FROM `pet` WHERE `star`=2 AND `is_yj`<1 AND `id`<221 ";
        $result = sql($sql);
        while (list($oid) = $result->fetch_row()) {
            $map_id = mt_rand(5731, 5830);
            pet::new_pet($oid, 1, $map_id);
            $name = value::getvalue('pet', 'name', 'id', $oid);
            $map_name = value::get_map_value($map_id, 'name');
            c_add_guangbo("亲爱的玩家，{$name}现已在 {$map_name} 出现了!");
        }
        //会员之家二层boss刷新
        $sql = "SELECT `id` FROM `pet` WHERE `star`=3 AND `is_yj`<1 AND `id`<221 ";
        $result = sql($sql);
        while (list($oid) = $result->fetch_row()) {
            $map_id = mt_rand(5831, 5894);
            pet::new_pet($oid, 1, $map_id);
            $name = value::getvalue('pet', 'name', 'id', $oid);
            $map_name = value::get_map_value($map_id, 'name');
            c_add_guangbo("亲爱的玩家，{$name}现已在 {$map_name} 出现了!");
        }
        //会员之家三层boss刷新
        $sql = "SELECT `id` FROM `pet` WHERE `star`>3 AND `is_yj`<1 AND `id`<221 ";
        $result = sql($sql);
        while (list($oid) = $result->fetch_row()) {
            $map_id = mt_rand(5895, 5943);
            pet::new_pet($oid, 1, $map_id);
            $name = value::getvalue('pet', 'name', 'id', $oid);
            $map_name = value::get_map_value($map_id, 'name');
            c_add_guangbo("亲爱的玩家，{$name}现已在 {$map_name} 出现了!");
        }
    }
    //返回
    return 1;
}

//分钟定时事件
function minute_loop()
{
    //天梯排名玩家奖励
    for ($i = 1; $i < 46; $i++) {
        if ($i == 1) {
            $i1 = 200;
        }
        if ($i == 2) {
            $i1 = 150;
        }
        if ($i == 4) {
            $i1 = 100;
        }
        if ($i == 7) {
            $i1 = 80;
        }
        if ($i == 11) {
            $i1 = 60;
        }
        if ($i == 16) {
            $i1 = 40;
        }
        if ($i == 22) {
            $i1 = 30;
        }
        if ($i == 29) {
            $i1 = 20;
        }
        if ($i == 37) {
            $i1 = 10;
        }
        $tz_tdxt_id = value::get_system_value('tz_tdxt_id' . $i);
        if ($tz_tdxt_id) {
            value::add_user_value('tz_tdxt_money', $i1, $tz_tdxt_id);
        } 
    }
    //天下第一
    $time_i = date("i");
    $time_h = date("H");
    if ($time_h == 19 && $time_i > 30) {
        c_add_guangbo("亲爱的玩家，竞技争霸火热进行中，活动-竞技擂台参加！");
    }
    $time_i = date("i");
    $time_h = date("H");
    if ($time_h == 19 && $time_i == 15) {
        $ocount = 0;
        $sql = "SELECT `name`,`id` FROM `game_user` WHERE `in_map_id`=5361 AND `is_online`=1";
        if ($sql) {
            $result = sql($sql);
            while (list($oname, $oid) = $result->fetch_row()) {
                $ocount++;
                if ($ocount == 1) {
                    $oname1 = $oname;
                    $oid1 = $oid;
                }
            }
            if ($ocount == 1) {
                value::set_system_value('txdy_id1', $oid1);
                c_add_guangbo("恭喜玩家{$oname1},在<img src=res/img/ch/txdy1.gif>活动获得最终胜利!");
            }
        }
        $ocount1 = 0;
        $sql = "SELECT `name`,`id` FROM `game_user` WHERE `in_map_id`=5362 AND `is_online`=1";
        if ($sql) {
            $result = sql($sql);
            while (list($oname, $oid) = $result->fetch_row()) {
                $ocount1++;
                if ($ocount1 == 1) {
                    $oname1 = $oname;
                    $oid1 = $oid;
                }
            }
            if ($ocount1 == 1) {
                value::set_system_value('txdy_id2', $oid1);
                c_add_guangbo("恭喜玩家{$oname1},在<img src=res/img/ch/txdy2.gif>活动获得最终胜利!");
            }
        }
        $ocount2 = 0;
        $sql = "SELECT `name`,`id` FROM `game_user` WHERE `in_map_id`=5363 AND `is_online`=1";
        if ($sql) {
            $result = sql($sql);
            while (list($oname, $oid) = $result->fetch_row()) {
                $ocount2++;
                if ($ocount2 == 1) {
                    $oname1 = $oname;
                    $oid1 = $oid;
                }
            }
            if ($ocount2 == 1) {
                value::set_system_value('txdy_id3', $oid1);
                c_add_guangbo("恭喜玩家{$oname1},在<img src=res/img/ch/txdy2.gif>活动获得最终胜利!");
            }
        }
    }
    //公测超级福利boss来袭
    $time_i = date("i");
    $time_h = date("H");
    if ($time_h == 12 && $time_i == 0) {
        $sql = "SELECT COUNT(*) FROM `map` WHERE `id`>0 AND `lvl`< 70 AND `is_pk`=1 AND `ts_map`<1 AND `ts_pk`<1 ";
        $result = sql($sql);
        list($map_count) = $result->fetch_row();
        $sj = mt_rand(1, $map_count);
        $sql = "SELECT `name`,`id` FROM `map` WHERE `id`>0 LIMIT {$sj}, 1";
        $result = sql($sql);
        list($map_name, $map_id) = $result->fetch_row();
        pet::new_pet(221, 1, $map_id);
        $name = value::getvalue('pet', 'name', 'id', 221);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $sql = "SELECT COUNT(*) FROM `map` WHERE `id`>0 AND `lvl`< 70 AND `is_pk`=1 AND `ts_map`<1 AND `ts_pk`<1 ";
        $result = sql($sql);
        list($map_count) = $result->fetch_row();
        $sj = mt_rand(1, $map_count);
        $sql = "SELECT `name`,`id` FROM `map` WHERE `id`>0 LIMIT {$sj}, 1";
        $result = sql($sql);
        list($map_name, $map_id) = $result->fetch_row();
        pet::new_pet(221, 1, $map_id);
        $name = value::getvalue('pet', 'name', 'id', 221);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $sql = "SELECT COUNT(*) FROM `map` WHERE `id`>0 AND `lvl`< 70 AND `is_pk`=1 AND `ts_map`<1 AND `ts_pk`<1 ";
        $result = sql($sql);
        list($map_count) = $result->fetch_row();
        $sj = mt_rand(1, $map_count);
        $sql = "SELECT `name`,`id` FROM `map` WHERE `id`>0 LIMIT {$sj}, 1";
        $result = sql($sql);
        list($map_name, $map_id) = $result->fetch_row();
        pet::new_pet(221, 1, $map_id);
        $name = value::getvalue('pet', 'name', 'id', 221);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $sql = "SELECT COUNT(*) FROM `map` WHERE `id`>0 AND `lvl`< 70 AND `is_pk`=1 AND `ts_map`<1 AND `ts_pk`<1 ";
        $result = sql($sql);
        list($map_count) = $result->fetch_row();
        $sj = mt_rand(1, $map_count);
        $sql = "SELECT `name`,`id` FROM `map` WHERE `id`>0 LIMIT {$sj}, 1";
        $result = sql($sql);
        list($map_name, $map_id) = $result->fetch_row();
        pet::new_pet(221, 1, $map_id);
        $name = value::getvalue('pet', 'name', 'id', 221);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $sql = "SELECT COUNT(*) FROM `map` WHERE `id`>0 AND `lvl`< 70 AND `is_pk`=1 AND `ts_map`<1 AND `ts_pk`<1 ";
        $result = sql($sql);
        list($map_count) = $result->fetch_row();
        $sj = mt_rand(1, $map_count);
        $sql = "SELECT `name`,`id` FROM `map` WHERE `id`>0 LIMIT {$sj}, 1";
        $result = sql($sql);
        list($map_name, $map_id) = $result->fetch_row();
        pet::new_pet(222, 1, $map_id);
        $name = value::getvalue('pet', 'name', 'id', 222);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
    }
    //赛马场游戏
    $sm_time = date("i");
    if ($sm_time < 1 || $sm_time == 10 || $sm_time == 20 || $sm_time == 30 || $sm_time == 40 || $sm_time == 50) {
        $sj = mt_rand(1, 6);
        $yl_smc_xt_dc = value::get_system_value('yl_smc_xt_dc');
        value::set_system_value('yl_smc_xt_dc1', $yl_smc_xt_dc);
        value::set_system_value('yl_smc_xt_dc', $sj);
        $yl_smc_zg_dc20 = value::get_system_value('yl_smc_zg_dc');
        $yl_smc_zg_dc1 = value::get_system_value('yl_smc_zg_dc') + 1;
        value::set_system_value('yl_smc_zg_dc', $yl_smc_zg_dc1);
        value::set_system_value('yl_smc_1_lingshi', 0);
        value::set_system_value('yl_smc_2_lingshi', 0);
        value::set_system_value('yl_smc_3_lingshi', 0);
        value::set_system_value('yl_smc_4_lingshi', 0);
        value::set_system_value('yl_smc_5_lingshi', 0);
        value::set_system_value('yl_smc_6_lingshi', 0);
        value::set_system_value('yl_smc_6_money', 0);
        value::set_system_value('yl_smc_5_money', 0);
        value::set_system_value('yl_smc_4_money', 0);
        value::set_system_value('yl_smc_3_money', 0);
        value::set_system_value('yl_smc_2_money', 0);
        value::set_system_value('yl_smc_1_money', 0);
        c_add_guangbo("娱乐-赛马游戏-{$yl_smc_zg_dc20}场开<img src=res/img/yl/{$yl_smc_xt_dc}.gif>{$yl_smc_xt_dc}号马!");
    }
    //骰子游戏
    $sz_time = date("i");
    if ($sz_time < 1 || $sz_time == 10 || $sz_time == 20 || $sz_time == 30 || $sz_time == 40 || $sz_time == 50) {
        $sz1_sj = mt_rand(1, 6);
        $sz2_sj = mt_rand(1, 6);
        $sz3_sj = mt_rand(1, 6);
        $yl_sz_sz1 = value::get_system_value('yl_sz_sz1');
        $yl_sz_sz2 = value::get_system_value('yl_sz_sz2');
        $yl_sz_sz3 = value::get_system_value('yl_sz_sz3');
        value::set_system_value('yl_sz_sz4', $yl_sz_sz1);
        value::set_system_value('yl_sz_sz5', $yl_sz_sz2);
        value::set_system_value('yl_sz_sz6', $yl_sz_sz3);
        value::set_system_value('yl_sz_sz1',  $sz1_sj);
        value::set_system_value('yl_sz_sz2',  $sz2_sj);
        value::set_system_value('yl_sz_sz3',  $sz3_sj);
        $yl_sz_zg_dc10 = value::get_system_value('yl_sz_zg_dc');
        $yl_sz_zg_dc1 = value::get_system_value('yl_sz_zg_dc') + 1;
        value::set_system_value('yl_sz_zg_dc', $yl_sz_zg_dc1);
        value::set_system_value('yl_sz_1_lingshi', 0);
        value::set_system_value('yl_sz_1_money', 0);
        value::set_system_value('yl_sz_2_lingshi', 0);
        value::set_system_value('yl_sz_2_money', 0);
        value::set_system_value('yl_sz_3_lingshi', 0);
        value::set_system_value('yl_sz_3_money', 0);
        value::set_system_value('yl_sz_4_lingshi', 0);
        value::set_system_value('yl_sz_4_money', 0);
        value::set_system_value('yl_sz_5_lingshi', 0);
        value::set_system_value('yl_sz_5_money', 0);
        $yl_sz_sz = $yl_sz_sz1 + $yl_sz_sz2 + $yl_sz_sz3;
        $bz = 0;
        $yl_sz_bz = ""; 
        $yl_sz_ds = ""; 
        $yl_sz_dx = ""; 
        if ($yl_sz_sz1 == $yl_sz_sz2){
            $bz = $bz + 1;
        }
        if ($yl_sz_sz1 == $yl_sz_sz3){
            $bz = $bz + 1;
        }
        if ($yl_sz_sz2 == $yl_sz_sz3){
            $bz = $bz + 1;
        }
        if ($bz == 3){
            $yl_sz_bz = "豹子"; 
        }
        if ($bz != 3){
            if ($yl_sz_sz % 2 == 0) {
                $yl_sz_ds = "双"; 
            } else {
                $yl_sz_ds = "单"; 
            }
            if ($yl_sz_sz > 10) {
                $yl_sz_dx = "大"; 
            } else {
                $yl_sz_dx = "小"; 
            }
        }
        c_add_guangbo("娱乐-赌骰子游戏-{$yl_sz_zg_dc10}场开<img src=res/img/yl/{$yl_sz_sz1}.jpg><img src=res/img/yl/{$yl_sz_sz2}.jpg><img src=res/img/yl/{$yl_sz_sz3}.jpg>{$yl_sz_sz1}{$yl_sz_sz2}{$yl_sz_sz3}{$yl_sz_dx}{$yl_sz_ds}{$yl_sz_bz}");
    }
    //删除创建60分钟的宠物
    $min_60_del_time = date('Y-m-d H:i:s', strtotime('-60 minute'));
    $sql = "SELECT `id` FROM `game_pet` WHERE `cj_gw_time` < '{$min_60_del_time}' AND `master_id`=0 AND `enemy_user`=0 AND `star`=1 AND `npc_id`=0 AND `master_mode`!=8";
    $result = sql($sql);
    while (list($oid) = $result->fetch_row()) {
        pet::del_pet($oid);
    }
    $min_60_del_time1 = date('Y-m-d H:i:s', strtotime('-60 minute'));
    $sql = "SELECT `id` FROM `game_pet` WHERE `cj_gw_time` < '{$min_60_del_time1}' AND `master_id`=0 AND `enemy_user`=0 AND `star`=2 AND `npc_id`=0 AND `master_mode`!=8";
    $result = sql($sql);
    while (list($oid) = $result->fetch_row()) {
        pet::del_pet($oid);
    }
    $min_120_del_time2 = date('Y-m-d H:i:s', strtotime('-120 minute'));
    $sql = "SELECT `id` FROM `game_pet` WHERE `cj_gw_time` < '{$min_120_del_time2}' AND `master_id`=0 AND `enemy_user`=0 AND `star`>2 AND `npc_id`=0 AND `master_mode`!=8 AND `pet_id`!=221 AND `pet_id`!=222";
    $result = sql($sql);
    while (list($oid) = $result->fetch_row()) {
        pet::del_pet($oid);
    }
    //场景临时装备删除
    $del_time = date('Y-m-d H:i:s', strtotime('-1440 minute'));
    $sql = "SELECT `id`,`prop_id` FROM `game_prop` WHERE `prop_id`>0 AND `cj_time` < '{$del_time}'";
    $result = sql($sql);
    while (list($del_id, $del_prop_id) = $result->fetch_row()) {
        if (value::get_prop_value($del_prop_id, 'zb_ls') > 0) {
            prop::del_prop($del_id);
        }
    }
    //场景临时宠物删除
    $sql = "SELECT `id`,`master_id` FROM `game_pet` WHERE `master_id`>0 AND `is_yj`<1";
    $rs = sql($sql);
    while (list($d_pet_id, $master_id) = $rs->fetch_row()) {
        $is_online = value::get_game_user_value('is_online' , $master_id);
        if (!$is_online) {
            pet::del_pet($d_pet_id);
        }
    }
    //擂台盟主
    $lt_h = date("H");
    $lt_i = date("i");
    if ($lt_h == 23 && $lt_i == 59) {
        $sql = "UPDATE `game_user` SET `jingji.jifen`=0 WHERE `id`>0";
        sql($sql);
    }
    if ($lt_h == 22 && $lt_i < 1) {
        value::set_system_value('wlmz_id', 0);
    }
    if ($lt_h == 18 && $lt_i == 49) {
        value::set_system_value('txdy_id1', 0);
        value::set_system_value('txdy_id2', 0);
        value::set_system_value('txdy_id3', 0);
        $sql = "SELECT `id` FROM `game_prop` WHERE `prop_id`=324 OR `prop_id`=325 OR `prop_id`=326 OR `prop_id`=312";
        $result = sql($sql);
        while (list($del_id) = $result->fetch_row()) {
            prop::del_prop($del_id);
        }
    }
    //公测红包
    $hb_d = date("d");
    $hb_d1 = value::get_system_value('hb_d');
    if ($hb_d1 != $hb_d) {
        value::set_system_value('hb_d', $hb_d);
        value::set_system_value('gc_hb_cs', 30);
        value::set_system_value('gc_hb_zg_je', 6666);
        c_add_guangbo("亲爱的玩家， 公测活动元宝红包天天送已经开抢红啦!");
    }
    //攻城结束提示
    if ($lt_h == 21 && $lt_i < 1) {
        c_add_guangbo("亲爱的玩家， 热血攻城已经结束，获得奖励的玩家请到活动页面领奖!");
    }
    //攻城盟主
    $gc_h = date("H");
    $gc_i = date("i");
    if ($gc_h == 19 && $gc_i == 59) {
        value::set_system_value('map_gcgw_id', 0);
        $map_gcgw_cs = value::get_system_value('map_gcgw_cs');
        if ($map_gcgw_cs < 1) {
            value::set_system_value('map_gcgw_cs', 1);
            for ($i = 0; $i < 100; $i++) {
                pet::new_pet(184, 10, 5039);
                pet::new_pet(187, 20, 5040);
                pet::new_pet(188, 30, 5044);
                pet::new_pet(189, 40, 5045);
                pet::new_pet(190, 50, 5046);
                pet::new_pet(191, 60, 5048);
                pet::new_pet(192, 70, 5050);
                pet::new_pet(193, 80, 5051);
                pet::new_pet(194, 90, 5052);
                pet::new_pet(195, 100, 5054);
                pet::new_pet(196, 110, 5055);
                pet::new_pet(197, 120, 5059);
            }
            pet::new_pet(198, 30, 5041);
            pet::new_pet(200, 120, 5058);
            pet::new_pet(201, 100, 5053);
            pet::new_pet(202, 80, 5049);
        }
        c_add_guangbo("亲爱的玩家， 热血攻城还有1分钟开始!");
    }
    //吃鸡空投更新出现 
    $sx_time = date("i");
    if ($sx_time == 10 || $sx_time == 20 || $sx_time == 30 || $sx_time == 40 || $sx_time == 50 || $sx_time == 59) {
        $sql = "SELECT COUNT(*) FROM `map` WHERE `id`>0";
        $result = sql($sql);
        list($map_count) = $result->fetch_row();
        $sj = mt_rand(1, $map_count);
        $sql = "SELECT `name`,`id` FROM `map` WHERE `id`>0 LIMIT {$sj}, 1";
        $result = sql($sql);
        list($map_name, $map_id) = $result->fetch_row();
        value::set_system_value('xs_kt_map', $map_id);
        c_add_guangbo("亲爱的玩家， <img src=kt.png >【空投】现已在{$map_name}出现了!");
    }
    //幸运宝箱更新出现 
    $bx_now_time = date("i");
    $bx_time = value::get_system_value('bx_time');
    if ($bx_now_time == $bx_time) {
        $sql = "SELECT COUNT(*) FROM `map` WHERE `id`>0";
        $result = sql($sql);
        list($map_count) = $result->fetch_row();
        $sj = mt_rand(1, $map_count);
        $sql = "SELECT `name`,`id` FROM `map` WHERE `id`>0 LIMIT {$sj}, 1";
        $result = sql($sql);
        list($map_name, $map_id) = $result->fetch_row();
        value::set_system_value('xs_bx_map', $map_id);
        c_add_guangbo("亲爱的玩家，【幸运宝箱】现已在{$map_name}出现了!");
    }
    //武林盟主
    $wlmz_time_i = date("i");
    $wlmz_time_h = date("H");
    if ($wlmz_time_h > 0 && $wlmz_time_i < 1) {
        $wlmz_time_h_ts = 24 - $wlmz_time_h;
        $sql = "SELECT `id`,`jingji.jifen` FROM `game_user` WHERE `jingji.jifen` >0 AND `name`!='' ORDER BY `jingji.jifen` DESC,`is_online` DESC LIMIT 0,1 ";
        $result = sql($sql);
        while (list($oid, $ovalue) = $result->fetch_row()) {
            $name1 = value::get_game_user_value('name', $oid);
        }
        c_add_guangbo("亲爱的玩家，武林争霸还剩余<span style=color:green>{$wlmz_time_h_ts}</span>小时结束，暂时排名第一是{$name1}!");
    }
    $wlmz_day = value::get_system_value('wlmz_day');
    $wlmz_day = $wlmz_day + 1;
    if ($wlmz_time_h == 23 && $wlmz_time_i == 59) {
        $sql = "SELECT `id`,`jingji.jifen` FROM `game_user` WHERE `jingji.jifen` >0 AND `name`!='' ORDER BY `jingji.jifen` DESC,`is_online` DESC LIMIT 0,1 ";
        $result = sql($sql);
        while (list($oid, $ovalue) = $result->fetch_row()) {
            value::set_system_value('wlmz_id', $oid);
            $name = value::get_game_user_value('name', $oid);
            c_add_guangbo('恭喜玩家' . $name . '在武林争霸成为了武林盟主获得<img src=res/img/ch/lzbz.png>称号!');
        }
        value::set_system_value('wlmz_day', $wlmz_day);
    }
    //魔龙boss 刷新
    $boss_time = date("i");
    $boss_ts_time = date("H");
    if ($boss_ts_time % 2 != 0 && $boss_time == 10) {
        //魔龙地图boss刷新
        $map_sj = mt_rand(5679, 5714);
        $map_id_boss = 220;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
    }
    if ($boss_ts_time % 2 == 0 && $boss_time == 0) {
        //魔龙地图boss刷新
        $map_sj = mt_rand(2952, 3051);
        $map_id_boss = 147;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        //ST会员圣地boss刷新
        $map_sj = mt_rand(3346, 3445);
        $map_id_boss = 152;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $map_sj = mt_rand(3346, 3445);
        $map_id_boss = 153;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $map_sj = mt_rand(3691, 3739);
        $map_id_boss = 162;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $map_sj = mt_rand(3853, 3933);
        $map_id_boss = 166;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $map_sj = mt_rand(4119, 4154);
        $map_id_boss = 170;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $map_sj = mt_rand(4329, 4392);
        $map_id_boss = 178;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
        $map_sj = mt_rand(4538, 4637);
        $map_id_boss = 183;
        $map_lvl = value::get_map_value($map_sj, 'lvl');
        $map_name = value::get_map_value($map_sj, 'name');
        pet::new_pet($map_id_boss, $map_lvl, $map_sj);
        $name = value::getvalue('pet', 'name', 'id', $map_id_boss);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
    }
    //十二生肖更新出现 
    $sx_time = date("i");
    if ($sx_time == 5 || $sx_time == 10 || $sx_time == 15 || $sx_time == 20 || $sx_time == 25 || $sx_time == 30 || $sx_time == 35 || $sx_time == 40 || $sx_time == 45 || $sx_time == 50 || $sx_time == 55 || $sx_time == 59) {
        $sql = "SELECT COUNT(*) FROM `map` WHERE `id`>0 AND `is_pk`>0 AND `lvl`<'{$sx_time}'";
        $result = sql($sql);
        list($map_count) = $result->fetch_row();
        $sj = mt_rand(1, $map_count);
        $sql = "SELECT `name`,`id` FROM `map` WHERE `id`>0 AND `is_pk`>0 AND `lvl`<'{$sx_time}' LIMIT {$sj}, 1";
        $result = sql($sql);
        list($map_name, $lz_sj) = $result->fetch_row();
        $map_sesx_id22 = 'map_sesx_id' . $sx_time;
        $map_sesx_id = value::get_system_value($map_sesx_id22);
        $sql = "UPDATE `map` SET `sxts_boss`=0 WHERE `id`='{$map_sesx_id}'";
        sql($sql);
        if ($lz_sj > 0) {
            value::set_system_value('map_sesx_id' . $sx_time, $lz_sj);
            $sql = "UPDATE `map` SET `sxts_boss`=1 WHERE `id`='{$lz_sj}'";
            sql($sql);
        }
        $map_lvl = value::get_map_value($lz_sj, 'lvl');
        if ($sx_time == 5) {
            $sx_gw = 85;
        } else if ($sx_time == 10) {
            $sx_gw = 86;
        } else if ($sx_time == 15) {
            $sx_gw = 87;
        } else if ($sx_time == 20) {
            $sx_gw = 88;
        } else if ($sx_time == 25) {
            $sx_gw = 89;
        } else if ($sx_time == 30) {
            $sx_gw = 90;
        } else if ($sx_time == 35) {
            $sx_gw = 91;
        } else if ($sx_time == 40) {
            $sx_gw = 92;
        } else if ($sx_time == 45) {
            $sx_gw = 93;
        } else if ($sx_time == 50) {
            $sx_gw = 94;
        } else if ($sx_time == 55) {
            $sx_gw = 95;
        } else if ($sx_time == 59) {
            $sx_gw = 96;
        }
        pet::new_pet($sx_gw, $map_lvl, $lz_sj);
        $name = value::getvalue('pet', 'name', 'id', $sx_gw);
        c_add_guangbo("亲爱的玩家，<span style=color:green>{$name}</span>现已在 {$map_name} 出现了!");
    }
    //七龙珠携带者更新出现 
    $lz_f_time = date("i");
    if ($lz_f_time == 0 || $lz_f_time == 8 || $lz_f_time == 16 || $lz_f_time == 24 || $lz_f_time == 32 || $lz_f_time == 40 || $lz_f_time == 50) {
        if ($lz_f_time == 0) {
            $lz_sj = mt_rand(402, 434);
            $map_lz_wz_id1 = value::get_system_value('map_lz_wz_id1');
            $sql = "UPDATE `map` SET `sxts_boss`=0 WHERE `id`='{$map_lz_wz_id1}'";
            sql($sql);
            value::set_system_value('map_lz_wz_id1', $lz_sj);
            $sql = "UPDATE `map` SET `sxts_boss`=1 WHERE `id`='{$lz_sj}'";
            sql($sql);
            $map_lvl = value::get_map_value($lz_sj, 'lvl');
            pet::new_pet(71, $map_lvl, $lz_sj);
            c_add_guangbo("亲爱的玩家，<span style=color:green>一星珠携带者召魂使</span>现已在 地下2层采矿所 出现了!");
        }
        if ($lz_f_time == 8) {
            $lz_sj = mt_rand(972, 1037);
            $map_lz_wz_id2 = value::get_system_value('map_lz_wz_id2');
            $sql = "UPDATE `map` SET `sxts_boss`=0 WHERE `id`='{$map_lz_wz_id2}'";
            sql($sql);
            value::set_system_value('map_lz_wz_id2', $lz_sj);
            $sql = "UPDATE `map` SET `sxts_boss`=1 WHERE `id`='{$lz_sj}'";
            sql($sql);
            $map_lvl = value::get_map_value($lz_sj, 'lvl');
            pet::new_pet(79, $map_lvl, $lz_sj);
            c_add_guangbo("亲爱的玩家，<span style=color:green>二星珠携带者利爪魔</span>现已在 石阁5层 出现了!");
        }
        if ($lz_f_time == 16) {
            $lz_sj = mt_rand(1237, 1332);
            $map_lz_wz_id3 = value::get_system_value('map_lz_wz_id3');
            $sql = "UPDATE `map` SET `sxts_boss`=0 WHERE `id`='{$map_lz_wz_id3}'";
            sql($sql);
            value::set_system_value('map_lz_wz_id3', $lz_sj);
            $sql = "UPDATE `map` SET `sxts_boss`=1 WHERE `id`='{$lz_sj}'";
            sql($sql);
            $map_lvl = value::get_map_value($lz_sj, 'lvl');
            pet::new_pet(80, $map_lvl, $lz_sj);
            c_add_guangbo("亲爱的玩家，<span style=color:green>三星珠携带者烈火鸡</span>现已在 沃玛神殿2层 出现了!");
        }
        if ($lz_f_time == 24) {
            $lz_sj = mt_rand(1750, 1829);
            $map_lz_wz_id4 = value::get_system_value('map_lz_wz_id4');
            $sql = "UPDATE `map` SET `sxts_boss`=0 WHERE `id`='{$map_lz_wz_id4}'";
            sql($sql);
            value::set_system_value('map_lz_wz_id4', $lz_sj);
            $sql = "UPDATE `map` SET `sxts_boss`=1 WHERE `id`='{$lz_sj}'";
            sql($sql);
            $map_lvl = value::get_map_value($lz_sj, 'lvl');
            pet::new_pet(81, $map_lvl, $lz_sj);
            c_add_guangbo("亲爱的玩家，<span style=color:green>四星珠携带者炼金魔</span>现已在 祖玛神殿5层 出现了!");
        }
        if ($lz_f_time == 32) {
            $lz_sj = mt_rand(2102, 2182);
            $map_lz_wz_id5 = value::get_system_value('map_lz_wz_id5');
            $sql = "UPDATE `map` SET `sxts_boss`=0 WHERE `id`='{$map_lz_wz_id5}'";
            sql($sql);
            value::set_system_value('map_lz_wz_id5', $lz_sj);
            $sql = "UPDATE `map` SET `sxts_boss`=1 WHERE `id`='{$lz_sj}'";
            sql($sql);
            $map_lvl = value::get_map_value($lz_sj, 'lvl');
            pet::new_pet(82, $map_lvl, $lz_sj);
            c_add_guangbo("亲爱的玩家，<span style=color:green>五星珠携带者炼金魔</span>现已在 赤月山谷2层 出现了!");
        }
        if ($lz_f_time == 40) {
            $lz_sj = mt_rand(2660, 2723);
            $map_lz_wz_id6 = value::get_system_value('map_lz_wz_id6');
            $sql = "UPDATE `map` SET `sxts_boss`=0 WHERE `id`='{$map_lz_wz_id6}'";
            sql($sql);
            value::set_system_value('map_lz_wz_id6', $lz_sj);
            $sql = "UPDATE `map` SET `sxts_boss`=1 WHERE `id`='{$lz_sj}'";
            sql($sql);
            $map_lvl = value::get_map_value($lz_sj, 'lvl');
            $map_name = value::get_map_value($lz_sj, 'name');
            pet::new_pet(83, $map_lvl, $lz_sj);
            c_add_guangbo("亲爱的玩家，<span style=color:green>六星珠携带者妖力士</span>现已在 {$map_name} 出现了!");
        }
        if ($lz_f_time == 50) {
            $lz_sj = mt_rand(4304, 4328);
            $map_lz_wz_id7 = value::get_system_value('map_lz_wz_id7');
            $sql = "UPDATE `map` SET `sxts_boss`=0 WHERE `id`='{$map_lz_wz_id7}'";
            sql($sql);
            value::set_system_value('map_lz_wz_id7', $lz_sj);
            $sql = "UPDATE `map` SET `sxts_boss`=1 WHERE `id`='{$lz_sj}'";
            sql($sql);
            $map_lvl = value::get_map_value($lz_sj, 'lvl');
            $map_name = value::get_map_value($lz_sj, 'name');
            pet::new_pet(84, $map_lvl, $lz_sj);
            c_add_guangbo("亲爱的玩家，<span style=color:green>七星珠携带者烈焰使</span>现已在 {$map_name} 出现了!");
        }
    }
    //废矿刷新法老僵尸 
    $bq_fk_i = date("i");
    if ($bq_fk_i == 0) {
        $fk1 = mt_rand(316, 350);
        value::set_system_value('fk_npc1', $fk1);
    }
    if ($bq_fk_i == 15) {
        $fk2 = mt_rand(363, 391);
        value::set_system_value('fk_npc2', $fk2);
    }
    if ($bq_fk_i == 30) {
        $fk3 = mt_rand(402, 434);
        value::set_system_value('fk_npc3', $fk3);
    }
    if ($bq_fk_i == 45) {
        $fk4 = mt_rand(447, 500);
        value::set_system_value('fk_npc4', $fk4);
    }
    //boss刷新提示
    $sql = "SELECT `id`,`yg_sx`,`yg_ys`,`name` FROM `map` WHERE `yg_sx`>0";
    $result = sql($sql);
    while (list($m_id, $yg_sx, $yg_ys, $m_name) = $result->fetch_row()) {
        $now_time = time();
        $now_time1 = (int)(($now_time - $yg_ys) / 60);
        if ($now_time1 == $yg_sx) {
            //获取显示宠物
            $sql = "SELECT `name`,`id` FROM `game_pet` WHERE `map_id`={$m_id} AND `master_id`=0 AND `npc_id`=0 AND `master_mode`!=8";
            $result = sql($sql);
            list($pet_name, $oid) = $result->fetch_row();
            c_add_guangbo("亲爱的玩家，{$pet_name}现已在{$m_name}刷新了!");
        }
    }
    //在线玩家操作
    $lt_now_time = date("i");
    if ($lt_now_time == 0) {
        value::set_system_value('xs_yb_sz', 1);
    }
    $user_sql = "SELECT `user_id` FROM `game_user` WHERE `is_online` = 1";
    $result = sql($user_sql);
    while (list($user_id) = $result->fetch_row()) {
        //增加在线玩家分钟时间
        value::add_user_value('onlinetime', 1, $user_id);
        //增加在线礼包分钟时间
        value::add_user_value('lb_zx_fz_time', 1, $user_id);
        $onlinetime = value::get_user_value('onlinetime', $user_id);
        if ($onlinetime < 0) {
            value::set_user_value('onlinetime', 0, $user_id);
        }
    }
    $now_time = time();
    //五分钟前
    $min_5_del_time = date('Y-m-d H:i:s', strtotime('-2 minute'));
    //删除地上物品
    $chat_sql = "DELETE FROM `game_value` WHERE `valuename` LIKE 'map.%i.%' AND `time` < '$min_5_del_time'";
    sql($chat_sql);
    //删除地上道具
    //非宠蛋
    $chat_sql = "DELETE FROM `game_prop` WHERE `map_id`>0 AND `prop_id`>0 AND `time` < '$min_5_del_time'";
    sql($chat_sql);
    //武将卡
    $chat_sql = "SELECT `id` FROM `game_prop` WHERE `map_id`>0 AND `prop_id`=62 AND `time` < '$min_5_del_time'";
    $result = sql($chat_sql);
    if (!$GLOBALS['mysqli']->errno) {
        while (list($del_egg_id) = $result->fetch_row()) {
            prop::del_prop($del_egg_id);
        }
    }
    //十分钟前
    $min_10_del_time = date('Y-m-d H:i:s', strtotime('-10 minute'));
    //删除广播消息 人物走向 战斗消息 组队请求 合成请求 竞技消息
    $chat_sql = "DELETE FROM `game_chat` WHERE `time` < '$min_10_del_time' AND (`mode` = 6 OR `mode` = 8 OR `mode` = 12 OR `mode` = 17 OR `mode` = 20 OR `mode` = 21)";
    sql($chat_sql);
    //下线发呆玩家
    c_exit_user();
    //在线玩家操作
    $user_sql = "SELECT `id` FROM `game_user` WHERE `is_online` = 1";
    $result = sql($user_sql);
    while (list($o_user_id) = $result->fetch_row()) {
        //获取最后刷新时间
        $now_time = time();
        $last_refresh_time = value::get_user_value('last_refresh_time', $o_user_id);
        if (!$last_refresh_time) {
            $last_refresh_time = $now_time;
        }
        //刷新间隔时间
        $add_online_time = $now_time - $last_refresh_time;
        //增加在线时间
        //设置最后刷新时间
        value::set_user_value('last_refresh_time', $now_time, $o_user_id);
        //结婚系统
        //获取伴侣信息
        $bl_id = value::get_user_value('bl.id', $o_user_id);
        if ($bl_id && value::get_game_user_value('is_online', $bl_id)) {
            //获取亲密时间
            $last_qinmi_time = value::get_user_value('last_qinmi_time', $o_user_id);
            if (!$last_qinmi_time) {
                $last_qinmi_time = $now_time;
            }
            //计算亲密度
            $add_qinmi_time = $now_time - $last_qinmi_time;
            //增加亲密度
            value::add_user_value('bl.qinmidu', $add_qinmi_time, $o_user_id);
            //设置亲密时间
            value::set_user_value('last_qinmi_time', $now_time, $o_user_id);
        }
        //悬赏系统
        //正在监狱 还有红名
        if (value::get_map_value(value::get_game_user_value('in_map_id', $o_user_id), 'is_jianyu') && user::get_hongming($o_user_id) > 0) {
            //获取红名时间
            $last_hongming_time = value::get_user_value('last_hongming_time', $o_user_id);
            if (!$last_hongming_time) {
                $last_hongming_time = $now_time;
            }
            //红名间隔时间
            $add_hongming_time = $now_time - $last_hongming_time;
            //减少红名时间
            $new_hongming = value::add_user_value('hongming', 0, $o_user_id);
            //设置最后红名时间
            value::set_user_value('last_hongming_time', $now_time, $o_user_id);
        }
    }
    //竞技场操作
    //平局退出
    $jingji_sql = "SELECT id FROM game_sports WHERE status=1 AND UNIX_TIMESTAMP(time)<" . ($now_time - 60 * 11);
    $result = sql($jingji_sql);
    while (list($sports_id) = $result->fetch_row()) {
        sports::out_of_sports($sports_id);
    }
    //返回
    return 1;
}

//检测 属性时间
function check_value_time($value_name, $seconds)
{
    $now_time = time();
    $loop_time = value::get_system_value($value_name);
    if ($loop_time < $now_time - $seconds) {
        value::set_system_value($value_name, $now_time);
        return true;
    } else {
        return false;
    }
}

//时间 获取微秒
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
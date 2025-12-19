<?php
//获取玩家id
$user_id = uid();
//ip限制
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
if (strstr($ip, ",")) {
    $ip_arr = explode(",", $ip);
    $ip = $ip_arr[0];
}
$sql = "SELECT COUNT(*) FROM game_user WHERE ip='$ip' AND is_online=1";
$rs = sql($sql);
list($one_ip_count) = $rs->fetch_row();
if ($one_ip_count > 50) {
    echo "当前IP同时在线人数已达到上限!";
    br();
    cmd::addcmd("e2", "再次尝试");
    br();
    echo "当前IP同时在线角色：";
    br();
    $sql = "SELECT `id`,`name` FROM `game_user` WHERE `is_online`=1 AND `ip`='{$ip}'";
    $result = sql($sql);
    while (list($o_user_id, $o_name) = $result->fetch_row()) {
        echo $o_name;
        echo "  ";
        cmd::addcmd('e6_ip,' . $o_user_id, '退出游戏');
        br();
    }
    cmd::set_show_return_game(false);
    return;
}
$in_map_id = value::get_game_user_value('in_map_id', $user_id);
$m_area_id = value::get_map_value($in_map_id, 'area_id');
//特殊地图传送回城
if ($m_area_id == 92 || $m_area_id == 93 || $m_area_id == 94) {
    $ts_day = date("d");
    $ts_d = value::get_user_value('mapgb_ts_day', $user_id);
    if ($ts_day != $ts_d) {
        value::set_user_value('mapgb_ts_day', $ts_day);
        $map_60_time = date('Y-m-d H:i:s', strtotime('+60 minute'));
        value::set_user_value('mapgb_60_time', $map_60_time);
    }
    $ts_time = date("Y-m-d H:i:s");
    $map_60_time = value::get_user_value('mapgb_60_time', $user_id);
    if ($ts_time >= $map_60_time) {
        value::set_user_value('ts_e60', 1, $user_id);
    }
    $mzsysj = strtotime($map_60_time) - strtotime($ts_time);
    $mz_min = (int)($mzsysj % 3600 / 60);
    if ($ts_time < $map_60_time) {
        echo "提示:你还剩<span style=color:red>{$mz_min}</span>分钟将传送出<span style=color:red>{$m_name}</span>!";
        br();
    }
}
if ($m_area_id == 65 || $m_area_id == 66 || $m_area_id == 67 || $m_area_id == 68 || $m_area_id == 69 || $m_area_id == 70) {
    $ts_day = date("d");
    $ts_d = value::get_user_value('map_ts_day', $user_id);
    if ($ts_day != $ts_d) {
        value::set_user_value('map_ts_day', $ts_day);
        $map_60_time = date('Y-m-d H:i:s', strtotime('+60 minute'));
        value::set_user_value('map_60_time', $map_60_time);
    }
    $ts_time = date("Y-m-d H:i:s");
    $map_60_time = value::get_user_value('map_60_time', $user_id);
    if ($ts_time >= $map_60_time) {
        value::set_user_value('ts_e60', 1, $user_id);
    }
    $mzsysj = strtotime($map_60_time) - strtotime($ts_time);
    $mz_min = (int)($mzsysj % 3600 / 60);
    if ($ts_time < $map_60_time) {
        echo "提示:你还剩<span style=color:red>{$mz_min}</span>分钟将传送出<span style=color:red>{$m_name}</span>!";
        br();
    }
}
if ($m_area_id == 86 || $m_area_id == 87 || $m_area_id == 88) {
    $ts_day = date("d");
    $ts_d = value::get_user_value('map_ts_day1', $user_id);
    if ($ts_day != $ts_d) {
        value::set_user_value('map_ts_day1', $ts_day);
        $map_60_time = date('Y-m-d H:i:s', strtotime('+60 minute'));
        value::set_user_value('map_80_time', $map_60_time);
    }
    $ts_time = date("Y-m-d H:i:s");
    $map_60_time = value::get_user_value('map_80_time', $user_id);
    if ($ts_time >= $map_60_time) {
        value::set_user_value('ts_e60', 1, $user_id);
    }
    $mzsysj = strtotime($map_60_time) - strtotime($ts_time);
    $mz_min = (int)($mzsysj % 3600 / 60);
    if ($ts_time < $map_60_time) {
        echo "提示:你还剩<span style=color:red>{$mz_min}</span>分钟将传送出<span style=color:red>{$m_name}</span>!";
        br();
    }
}
if ($m_area_id == 90) {
    $ts_day = date("d");
    $ts_d = value::get_user_value('map_jymj_day', $user_id);
    if ($ts_day != $ts_d) {
        value::set_user_value('map_jymj_day', $ts_day);
        value::add_user_value('map_jymj_cs', 1, $user_id);
        $map_60_time = date('Y-m-d H:i:s', strtotime('+60 minute'));
        value::set_user_value('map_jymj_time', $map_60_time);
    }
    $ts_time = date("Y-m-d H:i:s");
    $map_60_time = value::get_user_value('map_jymj_time', $user_id);
    if ($ts_time >= $map_60_time) {
        value::set_game_user_value('in_map_id', 1);
    }
    $mzsysj = strtotime($map_60_time) - strtotime($ts_time);
    $mz_min = (int)($mzsysj % 3600 / 60);
    if ($ts_time < $map_60_time) {
        echo "提示:你还剩<span style=color:red>{$mz_min}</span>分钟将传送出<span style=color:red>{$m_name}</span>!";
        br();
    }
}
if ($m_area_id == 95 || $m_area_id == 96 || $m_area_id == 97) {
    $ts_day = date("d");
    $ts_d = value::get_user_value('map_hyzj_day', $user_id);
    if ($ts_day != $ts_d) {
        value::set_user_value('map_hyzj_day', $ts_day);
        value::add_user_value('map_hyzj_cs', 1, $user_id);
        $map_60_time = date('Y-m-d H:i:s', strtotime('+60 minute'));
        value::set_user_value('map_hyzj_time', $map_60_time);
    }
    $ts_time = date("Y-m-d H:i:s");
    $map_60_time = value::get_user_value('map_hyzj_time', $user_id);
    if ($ts_time >= $map_60_time) {
        value::set_game_user_value('in_map_id', 1);
    }
    $mzsysj = strtotime($map_60_time) - strtotime($ts_time);
    $mz_min = (int)($mzsysj % 3600 / 60);
    if ($ts_time < $map_60_time) {
        echo "提示:你还剩<span style=color:red>{$mz_min}</span>分钟将传送出<span style=color:red>{$m_name}</span>!";
        br();
    }
}
//天下第一活动
$in_map_id = value::get_game_user_value('in_map_id', $user_id);
if ($in_map_id == 5565) {
    $time_i = date("i");
    $time_s = 60 - date("s");
    $time_i1 = 29 - date("i");
    $time_h = date("H");
    if ($time_h > 19) {
        value::set_game_user_value('in_map_id', 1);
    }
    if ($time_h == 19 && $time_i < 30) {
        echo "<span style=color:red>华山论剑开始：还剩余时间{$time_i1}分{$time_s}秒！</span>";
        br();
    } else {
        hd_wlzb_10();
        $time_d = date("d");
        $jj_time_d = value::get_user_value('jj_time_d', $user_id);
        if ($time_d != $jj_time_d) {
            item::add_item(287, 200);
            value::set_user_value('jj_time_d', $time_d, $user_id);
        }
    }
}
if ($in_map_id == 5361 || $in_map_id == 5362 || $in_map_id == 5363) {
    $time_i = date("i");
    $time_s = 60 - date("s");
    $time_i1 = 59 - date("i");
    $time_h = date("H");
    if ($time_h == 18 && $time_i > 49) {
        echo "<span style=color:red>比赛开始：还剩余时间{$time_i1}分{$time_s}秒！</span>";
        br();
        $sql = "UPDATE `map` SET `is_pk`='0' WHERE `id`='{$in_map_id}'";
        sql($sql);
    }
    if ($time_h == 19 && $time_i < 16) {
        $time_s = 60 - date("s");
        $time_i1 = 15 - date("i");
        $time_d = date("d");
        $txdy_time_d = value::get_user_value('txdy_time_d', $user_id);
        if ($time_d != $txdy_time_d) {
            item::add_item(299, 1);
            value::set_user_value('1.hd_hyd_sz', 10, $user_id);
            value::add_user_value('hd_hydz', 10, $user_id);
            value::set_user_value('txdy_time_d', $time_d, $user_id);
        }
        echo "<span style=color:red>比赛结束：还剩余时间{$time_i1}分{$time_s}秒！</span>";
        br();
        $sql = "UPDATE `map` SET `is_pk`='1' WHERE `id`='{$in_map_id}'";
        sql($sql);
    }
    $txdy_id1 = value::get_system_value('txdy_id1');
    $txdy_id2 = value::get_system_value('txdy_id2');
    $txdy_id3 = value::get_system_value('txdy_id3');
    if ($time_h == 19 && $time_i > 15) {
        if ($txdy_id1 == $user_id) {
            $name = value::get_game_user_value('name', $user_id);
            c_add_guangbo("恭喜" . $name . "在天下第一活动获得了<img src=res/img/ch/txdy1.gif>称号!");
            echo "恭喜你获得了<img src=res/img/ch/txdy1.gif>称号";
            br();
            value::set_user_value('using_nick_name', "<img src=res/img/ch/txdy1.gif>");
            prop::user_get_prop(prop::new_prop(324, 0, 0), 0, 1, false, true);
            value::set_game_user_value('in_map_id', 1, $user_id);
            e0();
        }
        if ($txdy_id2 == $user_id) {
            $name = value::get_game_user_value('name', $user_id);
            c_add_guangbo("恭喜" . $name . "在天下第一活动获得了<img src=res/img/ch/txdy2.gif>称号!");
            echo "恭喜你获得了<img src=res/img/ch/txdy2.gif>称号";
            br();
            value::set_user_value('using_nick_name', "<img src=res/img/ch/txdy2.gif>");
            prop::user_get_prop(prop::new_prop(325, 0, 0), 0, 1, false, true);
            value::set_game_user_value('in_map_id', 1, $user_id);
            e0();
        }
        if ($txdy_id3 == $user_id) {
            $name = value::get_game_user_value('name', $user_id);
            c_add_guangbo("恭喜" . $name . "在天下第一活动获得了<img src=res/img/ch/txdy3.gif>称号!");
            echo "恭喜你获得了<img src=res/img/ch/txdy3.gif>称号";
            br();
            value::set_user_value('using_nick_name', "<img src=res/img/ch/txdy3.gif>");
            prop::user_get_prop(prop::new_prop(326, 0, 0), 0, 1, false, true);
            value::set_game_user_value('in_map_id', 1, $user_id);
            e0();
        }
    }
}
//红名大于10被正义联盟NPC砍
$hongming = value::get_user_value('hongming', $user_id);
$in_map_id = value::get_game_user_value('in_map_id', $user_id);
$lvl = value::get_game_user_value('lvl', $user_id);
$name = value::get_game_user_value('name', $user_id);
$sj_npc = mt_rand(1, 100);
if ($hongming > 9 && $in_map_id != 5360 && $sj_npc < 6) {
    $map_name = value::get_map_value($in_map_id, 'name');
    $gw_name = value::getvalue('pet', 'name', 'id', 215);
    echo "很遗憾，你在{$map_name}被{$gw_name}看着不爽就砍了！";
    br();
    e1001(215, $lvl, $in_map_id);
    c_add_guangbo("很遗憾，{$name}在{$map_name}被{$gw_name}看着不爽就砍了！");
    return;
}
//监狱传送回监狱
$ts_hongming = value::get_user_value('ts_hongming', $user_id);
$hongming = value::get_user_value('hongming', $user_id);
$in_map_id = value::get_game_user_value('in_map_id');
if ($ts_hongming && $in_map_id != 5360) {
    value::set_game_user_value('in_map_id', 5360, $user_id);
    $hongming = user::get_hongming();
    echo "你正在准备传送越狱，却被狱卒抓个正着,还是老实的在里面忏悔吧<br>你还有{$hongming}点红名值,还需坐牢{$hongming}小时!";
    br();
    cmd::addcmd('e141,1', '买通守卫');
    br();
    cmd::addcmd('e142', '挖矿');
    br();
    return;
}
if (!$ts_hongming && $in_map_id == 5360 && $hongming) {
    value::set_user_value('ts_hongming', 1, $user_id);
}
if ($ts_hongming && $in_map_id == 5360 && $hongming < 1) {
    value::set_user_value('ts_hongming', 0, $user_id);
    value::set_game_user_value('in_map_id', 1, $user_id);
    echo "你决定洗心革面,重新做人,终于出狱了。";
    br();
    return;
}
//赛马场游戏奖励
$u_yl_smc_zg_dc = value::get_user_value('yl_smc_zg_dc', $user_id);
$yl_smc_zg_dc = value::get_system_value('yl_smc_zg_dc');
if ($u_yl_smc_zg_dc != $yl_smc_zg_dc) {
    $yl_smc_money = value::get_user_value('yl_smc_money', $user_id);
    if ($yl_smc_money > 0) {
        value::add_user_value('money', $yl_smc_money, $user_id);
        value::add_user_value('yl_money', $yl_smc_money, $user_id);
        value::set_user_value('yl_smc_money', 0, $user_id);
        echo "恭喜您，在娱乐-赛马游戏赚了{$yl_smc_money}个金币！";
        br();
        $name = value::get_game_user_value('name', $user_id);
        c_add_guangbo("恭喜玩家{$name}，在娱乐-赛马游戏赚了{$yl_smc_money}个金币！");
    }
    $yl_smc_lingshi = value::get_user_value('yl_smc_lingshi', $user_id);
    if ($yl_smc_lingshi > 0) {
        item::add_item(1, $yl_smc_lingshi);
        value::add_user_value('yl_lingshi', $yl_smc_lingshi, $user_id);
        value::set_user_value('yl_smc_lingshi', 0, $user_id);
        echo "恭喜您，在娱乐-赛马游戏赚了{$yl_smc_lingshi}个元宝！";
        br();
        $name = value::get_game_user_value('name', $user_id);
        c_add_guangbo("恭喜玩家{$name}，在娱乐-赛马游戏赚了{$yl_smc_lingshi}个元宝！");
    }
    value::set_user_value('yl_smc_zg_dc', $yl_smc_zg_dc, $user_id);
}
//赌骰子游戏奖励
$u_yl_sz_zg_dc = value::get_user_value('yl_sz_zg_dc', $user_id);
$yl_sz_zg_dc = value::get_system_value('yl_sz_zg_dc');
if ($u_yl_sz_zg_dc != $yl_sz_zg_dc) {
    $yl_sz_money = value::get_user_value('yl_sz_money', $user_id);
    if ($yl_sz_money > 0) {
        value::add_user_value('money', $yl_sz_money, $user_id);
        value::add_user_value('yl_money', $yl_sz_money, $user_id);
        value::set_user_value('yl_sz_money', 0, $user_id);
        echo "恭喜您，在娱乐-赌骰子游戏赚了{$yl_sz_money}个金币！";
        br();
        $name = value::get_game_user_value('name', $user_id);
        c_add_guangbo("恭喜玩家{$name}，在娱乐-赌骰子游戏赚了{$yl_sz_money}个金币！");
    }
    $yl_sz_lingshi = value::get_user_value('yl_sz_lingshi', $user_id);
    if ($yl_sz_lingshi > 0) {
        item::add_item(1, $yl_sz_lingshi);
        value::add_user_value('yl_lingshi', $yl_sz_lingshi, $user_id);
        value::set_user_value('yl_sz_lingshi', 0, $user_id);
        echo "恭喜您，在娱乐-赌骰子游戏赚了{$yl_sz_lingshi}个元宝！";
        br();
        $name = value::get_game_user_value('name', $user_id);
        c_add_guangbo("恭喜玩家{$name}，在娱乐-赌骰子游戏赚了{$yl_sz_lingshi}个元宝！");
    }
    value::set_user_value('yl_sz_zg_dc', $yl_sz_zg_dc, $user_id);
}
$user_is_online = value::get_game_user_value('is_online', $user_id);
if ($user_is_online == 0) {
    ctm::show_ctm('ctm_shouye');
    return;
}
//场景技能自动升级
$u = new game_user_object($user_id);
$uSkillArr = $u->jget('skill.arr');
foreach ($uSkillArr as $skill_id) {
    $jn_lvl = value::get_user_value('skill.' . $skill_id . '.lvl', $user_id);
    $jn_exp = value::get_user_value('skill.' . $skill_id . '.exp', $user_id);
    $jn_max_exp = $jn_lvl * 600 + 600;
    if ($jn_lvl < 3 && $jn_exp >= $jn_max_exp) {
        value::add_user_value('skill.' . $skill_id . '.lvl', 1, $user_id);
        value::set_user_value('skill.' . $skill_id . '.exp', 0, $user_id);
        $jn_lvl = value::get_user_value('skill.' . $skill_id . '.lvl', $user_id);
        if (!$jn_lvl) {
            $jn_lvl_name = "初级";
        } else if ($jn_lvl == 1) {
            $jn_lvl_name = "中级";
        } else if ($jn_lvl == 2) {
            $jn_lvl_name = "高级";
        } else if ($jn_lvl == 3) {
            $jn_lvl_name = "专家";
        }
        $skillChat = "你的 " . value::getvalue('skill', 'name', 'id', $skill_id) . "升级了,升到" . $jn_lvl_name . "了！";
        c_add_xiaoxi($skillChat, 0, $user_id, $user_id);
    }
}
//称号属性加成
ch_chsx();

//战斗是否结算
$is_pk_settlement_doing = value::get_user_value('is_pk_settlement_doing', $user_id);
if ($is_pk_settlement_doing) {
    if ($is_pk_settlement_doing == 1) {
        echo "战斗正在结算中,请稍后刷新!";
        br();
        cmd::addcmd("e137", "刷新");
        cmd::set_show_return_game(false);
        return;
    } else if ($is_pk_settlement_doing == 2) {
        e137();
        return;
    }
} else {
    value::set_game_user_value('is_pk', 0);
}
//刷新人物套装属性
map_tz_jc($user_id);
//地图不存在
$in_map_id = value::get_game_user_value('in_map_id');
if (!$in_map_id) {
    $in_cs_map_id = 1;
    value::set_game_user_value('in_map_id', $in_cs_map_id);
    e0();
}
$e60 = value::get_user_value('e60', $user_id);
if ($e60 > 0) {
    value::set_user_value("e60", 0, $user_id);
    return;
}
$ts_e60 = value::get_user_value('ts_e60', $user_id);
if ($ts_e60 > 0) {
    $map_id =value::get_game_user_value('in_map_id', $user_id);
    $map_name = value::get_map_value($map_id, 'name');
    echo "提示:你已经传送出<span style=color:red>{$map_name}</span>,商场购买对应【副本卷】可以增加副本时间!";
    value::set_game_user_value('in_map_id', 1);
    value::set_user_value('ts_e60', 0, $user_id);
    return;
}
$xy_e60 = value::get_user_value('xy_e60', $user_id);
if ($xy_e60 > 0) {
    $user_id = uid();
    item::add_money(100000, $user_id);
    user::get_exp($user_id, 1000000);
    item::add_item(1, 100);
    value::set_user_value("xy_e60", 0, $user_id);
    br();
    return;
}
if ($user_id) {
    //场景玩家死亡原地复活
    $user_is_sw = value::get_game_user_value('is_dead', $user_id);
    value::set_user_value('cs_map1', 0, $user_id);
    if ($user_is_sw > 0) {
        map_wjsw_jzhhd();
        return;
    }
    //场景获得祝福效果
    map_zf_xg();
    //在线玩家地图场景获得属性
    map_wj_zx();
}

//获取玩家性别
$sex = value::get_game_user_value('sex');
//获取队伍id
$team_id = value::get_game_user_value('team_id');
//获取帮派id
$union_id = user::get_union($user_id);
//登录时间
$login_time = value::get_game_user_value('login_time');
//获取玩家所在地图ID
$in_map_id = value::get_game_user_value('in_map_id');
//提取地图数据
$sql = "SELECT `area_id`,`id`,`sxts_boss`,`yg_ys`,`yg_sx`,`npc_sx`,`npc_time`,`name`, `desc`, `image`, `event`, `task`, `lvl`, `shuxing`, `npc`, `yaoguai`, `exit_b`, `exit_x`, `exit_d`, `exit_n`, `is_pk`, `is_danrenfuben`, `is_duorenfuben`, `is_cunzhangjia`, `is_fengyaoguan`, `is_yizhan`, `is_zahuopu`, `is_xiuxingchang`, `is_jianyu`, `is_gonghuilingdi`,`is_jingjichang`,`is_xs2`,`is_xs3`,`is_xs4`,`is_xs5`,`is_xs6`,`is_xs7`,`cs_map`,`ts_map`,`is_chalou` FROM `map` WHERE `id`={$in_map_id} LIMIT 1";
$result = sql($sql);
list($m_area_id, $m_id, $m_sxts_boss, $yg_ys, $yg_sx, $npc_sx, $npc_time, $m_name, $m_desc, $m_image, $m_event, $m_task, $m_lvl, $m_shuxing, $m_npc, $m_yaoguai, $m_exit_b, $m_exit_x, $m_exit_d, $m_exit_n, $m_is_pk, $m_is_danrenfuben, $m_is_duorenfuben, $m_is_cunzhangjia, $m_is_fengyaoguan, $m_is_yizhan, $m_is_zahuopu, $m_is_xiuxingchang, $m_is_jianyu, $m_is_gonghuilingdi, $m_is_jingjichang, $m_is_xs2, $m_is_xs3, $m_is_xs4, $m_is_xs5, $m_is_xs6, $m_is_xs7, $m_cs_map, $m_ts_map, $m_is_chalou) = $result->fetch_row();
//自动设置地图离线回城属性
if ($m_ts_map > 0) {
    value::set_user_value('ts_map', $m_ts_map);
}
if ($m_cs_map > 0) {
    value::set_user_value('cs_map', $in_map_id);
}
if ($m_ts_map < 1) {
    value::set_user_value('sw_map', $in_map_id);
}

//石阁阵迷宫 随机进入石阁7层
if ($m_area_id == 30) {
    $sgz_id6_sj = mt_rand(1, 100);
    $sgz_id6 = value::get_user_value("sgz_id6", $user_id);
    if ($sgz_id6_sj < 5 && $in_map_id != $sgz_id6) {
        map_cs_id(1043);
        return;
    }
    value::set_user_value("sgz_id6", $in_map_id, $user_id);
}
//祖玛阁迷宫 随机进入祖玛阁7层
if ($m_area_id == 44) {
    $sgz_id6_sj = mt_rand(1, 1000);
    $sgz_id6 = value::get_user_value("sgz_id6", $user_id);
    if ($sgz_id6_sj < 50 && $in_map_id != $sgz_id6) {
        map_cs_id(1835);
        return;
    }
    value::set_user_value("sgz_id6", $in_map_id, $user_id);
}
if ($m_yaoguai) {
    c_map_flush_pet($in_map_id);
}
//刷新地图怪物攻击
value::set_user_value("pk_yggj", 0, $user_id);
$sql = "SELECT `id` FROM `game_pet` WHERE `map_id`={$in_map_id} AND `master_id`=0 AND `npc_id`=0 AND `master_mode`!=8 LIMIT 1, 1";
$result = sql($sql);
while (list($oid) = $result->fetch_row()) {
    value::set_user_value("pk_yggj", $oid, $user_id);
}
$pk_yggj_id = value::get_user_value("pk_yggj", $user_id);
$pet_yg_sj = mt_rand(1, 100);
$gw_bg = value::get_user_value("wj_bg", $user_id);
$tsjz_ys = value::get_game_user_value("lvl", $user_id);
if ($tsjz_ys > $m_lvl) {
    $ts_jz7 = equip::get_user_equip_jc('ts_jz7');
}
if ($pet_yg_sj < 10 && $m_is_pk == 1 && $gw_bg < 1 && value::get_pet_value($pk_yggj_id, 'name') && $ts_jz7 < 1) {
    e48($pk_yggj_id);
    return;
}
//获得同地图队伍打怪方式
$sql = "SELECT `name`,`pk.map_pet.id` FROM `game_user` WHERE `in_map_id` = {$in_map_id} AND `is_online` =1 AND `user_id` != {$user_id} AND `team_id` = {$team_id} AND `team_id` >0 AND `pk.map_pet.id` >0 LIMIT 0, 5";
//开始获取
if ($sql) {
    $result = sql($sql);
    while (list($dy_name, $dy_id) = $result->fetch_row()) {
        $ocount++;
        $dy_gwname = value::get_pet_value($dy_id, 'name');
        if ($dy_gwname) {
            echo "队友{$ocount}：{$dy_name} 攻击 {$dy_gwname}";
            echo "   ";
            cmd::addcmd('e48,' . $dy_id, '参战');
            br();
        }
    }
}
//已读消息id数组
$tmp_chat_id_arr = array();
//竞技场挑战请求
if ($m_is_jingjichang < 10) {
    $jjctzqq_count = value::get_user_value('jjctzqq_count');
    $min_id = 0;
    $chat_count = 0;
    $chat_id = 0;
    if (!$jjctzqq_count) {
        $jjctzqq_count = 0;
    }
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 21 AND `oid`={$user_id} AND `id`>" . $jjctzqq_count . " AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 1";
    $result = sql($sql);
    if (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
        $user_name = value::get_game_user_value("name", $oid);
        $chat_id1 = $chat_id;
        e203($chat_id1,1);
        $chat_count++;
        if ($chat_count == 1) {
            $min_id = $chat_id;
            if ($min_id > value::get_user_value('jjctzqq_count')) {
                value::set_user_value('jjctzqq_count', $min_id);
            }
        }
    }
    if (user::get("pkjjzt")) {
        $sports_id = user::get("pkjjid");
        $sarr = sports::get_arr($sports_id);
        $start_jj_time = strtotime($sarr['time']) + 10;
        $sy_jj_time = $start_jj_time - time();
        if ($sy_jj_time > 0) {
            echo "距离竞技比赛开始还剩{$sy_jj_time}秒,请做好准备!";
        } else {
            if ($sarr['status'] == 1) {
                user::set_game_user("in_ctm", "ctm_pk_user");
                e0();
                return;
            }
            echo "竞技比赛马上开始,请做好准备!";
        }
        br();
    }
}
if ($m_is_jingjichang > 100) {
    $jjctzqq_count = value::get_user_value('jjctzqq_count');
    $min_id = 0;
    $chat_count = 0;
    $chat_id = 0;
    if (!$jjctzqq_count) {
        $jjctzqq_count = 0;
    }
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 21 AND `oid`={$user_id} AND `id`>" . $jjctzqq_count . " AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 1";
    $result = sql($sql);
    if (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
        $user_name = value::get_game_user_value("name", $oid);
        echo "{$user_name}想要在竞技场押上{$chats}个金币挑战你!";
        br();
        cmd::addcmd("e203,$chat_id", "查看详情");
        br();
        cmd::addcmd("e203,$chat_id,1", "同意挑战");
        br();
        cmd::addcmd("e203,$chat_id,2", "拒绝挑战");
        br();
        $chat_count++;
        if ($chat_count == 1) {
            $min_id = $chat_id;
            if ($min_id > value::get_user_value('jjctzqq_count')) {
                value::set_user_value('jjctzqq_count', $min_id);
            }
        }
    }
    if (user::get("pkjjzt")) {
        $sports_id = user::get("pkjjid");
        $sarr = sports::get_arr($sports_id);
        $start_jj_time = strtotime($sarr['time']) + 10;
        $sy_jj_time = $start_jj_time - time();
        if ($sy_jj_time > 0) {
            echo "距离竞技比赛开始还剩{$sy_jj_time}秒,请做好准备!";
        } else {
            if ($sarr['status'] == 1) {
                user::set_game_user("in_ctm", "ctm_pk_user");
                e0();
                return;
            }
            echo "竞技比赛马上开始,请做好准备!";
        }
        br();
    }
}
//显示地区聊天
if (!value::get_user_value('kg.xsdqpd')) {
    $dqlt_count = value::get_user_value('dqlt_count');
    $min_id = 0;
    $chat_count = 0;
    $chat_id = 0;
    if (!$dqlt_count) {
        $dqlt_count = 0;
    }
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 30 AND `id`>{$dqlt_count} ORDER BY `id` DESC LIMIT 0, 1 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
        echo "[";
        cmd::addcmd('e36,8', '广播');
        echo "]";
        user::showVipLogo($oid, true);
        cmd::addcmd('e12,' . $oid, value::get_game_user_value('name', $oid));
        echo " 说:<span style='color:red'>{$chats}</span>";
        br();
        $chat_count++;
        if ($chat_count == 1) {
            $min_id = $chat_id;
            $now_time = date("Y-m-d H:i:s");
            $shengyu_time = strtotime($time) - strtotime($now_time);
            $shengyu_minute = 60 + (int)($shengyu_time % 3600 / 60);
            if ($min_id > value::get_user_value('dqlt_count') && $shengyu_minute < 58) {
                value::set_user_value('dqlt_count', $min_id);
            }
        }
    }
}
//显示求婚请求
$chat_id = 0;
$sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 14 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
$result = sql($sql);
while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
    echo $chats;
    br();
    cmd::addcmd('c_add_qhqq,' . $oid . ',1,' . $chat_id, '接受求婚');
    br();
    cmd::addcmd('c_add_qhqq,' . $oid . ',2,' . $chat_id, '拒绝求婚');
    br();
    array_push($tmp_chat_id_arr, $chat_id);
}
//显示拜师请求
$chat_id = 0;
$sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 15 AND `oid` = {$user_id} AND `uid` != {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
$result = sql($sql);
while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
    echo $chats;
    br();
    cmd::addcmd('c_add_bsqq,' . $oid . ',1,' . $chat_id, '接受拜师');
    br();
    cmd::addcmd('c_add_bsqq,' . $oid . ',2,' . $chat_id, '拒绝拜师');
    br();
    array_push($tmp_chat_id_arr, $chat_id);
}
//显示组队请求
$chat_id = 0;
$sql = "SELECT `id`,`uid`,`chats`,`guangbo_mode` FROM `game_chat` WHERE `mode` = 17 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
$result = sql($sql);
while (list($chat_id, $oid, $chats, $guangbo_mode) = $result->fetch_row()) {
    echo $chats;
    br();
    //组队邀请
    if (!$guangbo_mode) {
        cmd::addcmd('c_add_zdqq,' . $oid . ',1,' . $chat_id, '接受邀请');
        br();
        cmd::addcmd('c_add_zdqq,' . $oid . ',2,' . $chat_id, '拒绝邀请');
        br();
    } else {
        //加入组队
        cmd::addcmd('c_add_zdqq,' . $oid . ',3,' . $chat_id, '接受请求');
        br();
        cmd::addcmd('c_add_zdqq,' . $oid . ',4,' . $chat_id, '拒绝请求');
        br();
    }
    array_push($tmp_chat_id_arr, $chat_id);
}
//显示好友请求
$chat_id = 0;
$sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 7 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
$result = sql($sql);
while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
    echo $chats;
    br();
    cmd::addcmd('c_add_hyqq,' . $oid . ',1,' . $chat_id, '接受请求');
    br();
    cmd::addcmd('c_add_hyqq,' . $oid . ',2,' . $chat_id, '拒绝请求');
    br();
    array_push($tmp_chat_id_arr, $chat_id);
}
//显示交易请求
if (!value::get_user_value('kg.xsjyqq')) {
//显示交易宠物请求
    $chat_id = 0;
    $sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 11 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
        $jyxq = explode(',', $chats);
        $pet_id = $jyxq[1];
        $money = $jyxq[2];
        $lvl = value::get_pet_value($pet_id, 'lvl');
        $name = value::get_pet_value($pet_id, 'name');
        $texing = explode(':', pet::get_texing(value::get_pet_value($pet_id, 'texing')));
        $xingge = pet::get_xingge(value::get_pet_value($pet_id, 'xingge'));
        echo $jyxq[0] . '想用' . $money . '个金币卖给你一只性格为' . $lvl . "级" . $name . "。";
        br();
        cmd::addcmd('c_add_jycwqq,' . $oid . ',1,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '接受交易');
        br();
        cmd::addcmd('c_add_jycwqq,' . $oid . ',2,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '拒绝交易');
        br();
        array_push($tmp_chat_id_arr, $chat_id);
    }
    $chat_id = 0;
    $sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 32 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
        $jyxq = explode(',', $chats);
        $pet_id = $jyxq[1];
        $money = $jyxq[2];
        $lvl = value::get_pet_value($pet_id, 'lvl');
        $name = value::get_pet_value($pet_id, 'name');
        $texing = explode(':', pet::get_texing(value::get_pet_value($pet_id, 'texing')));
        $xingge = pet::get_xingge(value::get_pet_value($pet_id, 'xingge'));
        echo $jyxq[0] . '想用' . $money . '个元宝卖给你一只' . $lvl . "级" . $name . "。";
        br();
        cmd::addcmd('c_add_jycwqq1,' . $oid . ',1,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '接受交易');
        br();
        cmd::addcmd('c_add_jycwqq1,' . $oid . ',2,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '拒绝交易');
        br();
        array_push($tmp_chat_id_arr, $chat_id);
    }
//显示交易物品请求
    $chat_id = 0;
    $sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 10 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
        $jyxq = explode(',', $chats);
        $money = $jyxq[2] * $jyxq[3];
        echo $jyxq[0] . '想用' . $money . '个金币卖给你' . $jyxq[3] . $jyxq[4] . $jyxq[5] . "。";
        br();
        cmd::addcmd('c_add_jywpqq,' . $oid . ',1,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $jyxq[3] . ',' . $chat_id, '接受交易');
        br();
        cmd::addcmd('c_add_jywpqq,' . $oid . ',2,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $jyxq[3] . ',' . $chat_id, '拒绝交易');
        br();
        array_push($tmp_chat_id_arr, $chat_id);
    }
    $chat_id = 0;
    $sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 31 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
        $jyxq = explode(',', $chats);
        $money = $jyxq[2] * $jyxq[3];
        echo $jyxq[0] . '想用' . $money . '个元宝卖给你' . $jyxq[3] . $jyxq[4] . $jyxq[5] . "。";
        br();
        cmd::addcmd('c_add_jywpqq1,' . $oid . ',1,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $jyxq[3] . ',' . $chat_id, '接受交易');
        br();
        cmd::addcmd('c_add_jywpqq1,' . $oid . ',2,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $jyxq[3] . ',' . $chat_id, '拒绝交易');
        br();
        array_push($tmp_chat_id_arr, $chat_id);
    }
//显示交易道具请求
    $chat_id = 0;
    $sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 13 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
        $jyxq = explode(',', $chats);
        $money = $jyxq[2];
        echo $jyxq[0] . '想用' . $money . '个金币卖给你1' . $jyxq[3] . $jyxq[4] . "。";
        br();
        cmd::addcmd('c_add_jydjqq,' . $oid . ',1,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '查看详情');
        br();
        cmd::addcmd('c_add_jydjqq,' . $oid . ',2,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '接受交易');
        br();
        cmd::addcmd('c_add_jydjqq,' . $oid . ',3,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '拒绝交易');
        br();
        array_push($tmp_chat_id_arr, $chat_id);
    }
    $chat_id = 0;
    $sql = "SELECT `id`,`uid`,`chats` FROM `game_chat` WHERE `mode` = 33 AND `oid` = {$user_id} AND `is_look` = 0 LIMIT 0, 5 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats) = $result->fetch_row()) {
        $jyxq = explode(',', $chats);
        $money = $jyxq[2];
        echo $jyxq[0] . '想用' . $money . '个元宝卖给你1' . $jyxq[3] . $jyxq[4] . "。";
        br();
        cmd::addcmd('c_add_jydjqq1,' . $oid . ',1,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '查看详情');
        br();
        cmd::addcmd('c_add_jydjqq1,' . $oid . ',2,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '接受交易');
        br();
        cmd::addcmd('c_add_jydjqq1,' . $oid . ',3,' . $jyxq[1] . ',' . $jyxq[2] . ',' . $chat_id, '拒绝交易');
        br();
        array_push($tmp_chat_id_arr, $chat_id);
    }
}
//设置消息数组已读
if (count($tmp_chat_id_arr)) {
    $sql = "UPDATE `game_chat` SET `is_look`=1 WHERE ";
    foreach ($tmp_chat_id_arr as $tmp_chat_id) {
        $sql .= " `id`={$tmp_chat_id} OR";
    }
    $sql = trim($sql, "OR");
    sql($sql);
}
//显示广播
$min_id = 0;
$chat_count = 0;
$chat_id = 0;
$gb_count = value::get_user_value('gb_count');
$sql = "SELECT `id`,`chats`,`guangbo_mode`,`uid` FROM `game_chat` WHERE `mode` = 6 AND `id`>{$gb_count} AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 0, 3 ";
$result = sql($sql);
while (list($chat_id, $chats, $guangbo_mode, $tid) = $result->fetch_row()) {
    echo '[系统]' . $chats;
    switch ($guangbo_mode) {
        //结婚祝福
        case 1:
            echo " ";
            cmd::addcmd('e116,' . $chat_id, '送上祝福');
            break;
        //拜师请求
        case 2:
            echo " ";
            cmd::addcmd('e120,' . $chat_id, '我要拜师');
            break;
        //拜师请求
        case 3:
            echo " ";
            cmd::addcmd('e205,' . $tid, '我要押注');
            break;
        //拜师请求
        case 4:
            echo " ";
            $name = value::get_game_prop_value($tid, 'name');
            cmd::addcmd('e10003,' . $tid . ',3', $name);
            break;
        case 5:
            echo " ";
            $name = value::get_pet_value($tid, 'name');
            cmd::addcmd('bx_e621,' . $tid . ',1', $name);
            break;
    }
    br();
    $chat_count++;
    if ($chat_count == 1) {
        $min_id = $chat_id;
        value::set_user_value("gb_count", $min_id, $user_id);
    }
}

//攻城提示
$wlmz_time_h = date("H");
$in_map_id = value::get_game_user_value('in_map_id', $user_id);
$yg_area_id = value::get_map_value($in_map_id, 'area_id', $user_id);
if ($wlmz_time_h == 20) {
    $map_gcgw_hb_cs = value::get_system_value('gcgw_hb_cs');
    $map_gcgw_hb_je = value::get_system_value('gcgw_hb_je');
    $map_gcgw_id = value::get_system_value('map_gcgw_id');
    if ($map_gcgw_hb_cs > 0) {
        cmd::addcmd('map_gc_hb', '天降第' . $map_gcgw_id . '波攻城红包');
        br();
    }
}
$map_gc_hb_cs = value::get_system_value('gc_hb_cs');
if ($map_gc_hb_cs > 0) {
    $map_gc_hb_je = value::get_system_value('gc_hb_je');
    cmd::addcmd('map_gc_hb2', '元宝红包天天送');
    br();
}
if ($wlmz_time_h == 20 && $yg_area_id != 89) {
    echo '[系统]亲爱的玩家，攻城活动已经开始，请点击参加';
    echo '  ';
    cmd::addcmd('map_cs_id1,5039', '开始攻城');
    br();
}

//新手30级进入试炼场
$game_lvl = value::get_game_user_value('lvl', $user_id);
if ($game_lvl < 10 && $m_area_id != 71) {
    cmd::addcmd("map_cs_id,3446", "[福利]新手炼级场");
    br();
}
if ($game_lvl > 9 && $m_area_id == 71) {
    map_cs_id(1);
    return;
}
//显示地图名称
echo "[{$m_name}]";
cmd::addcmd('e0', '刷新');
if ($m_is_pk == 1) {
    echo "<span style=color:red>[危]</span>";
} else {
    echo "<span style=color:green>[安]</span>";
}
br();

//显示功能
cmd::addcmd('e108', '任务');
echo " | ";
cmd::addcmd('e107', '组队');
echo " | ";
cmd::addcmd('e51,2', '好友');
echo " | ";
cmd::addcmd('map_hy', '会员');
echo " | ";
cmd::addcmd('map_lb', '<span style=color:red>礼包</span>');
br();
//药园
$in_map_id = value::get_game_user_value('in_map_id', $user_id);
if ($in_map_id > 5723 && $in_map_id < 5729) {
    $i = value::get_map_value($in_map_id, 'ts_map');
    $yy_ld_exp = $i;
    if ($yy_ld_exp == 1) {
        $yy_ld_name = "青铜炉";
    } elseif ($yy_ld_exp == 2) {
        $yy_ld_name = "八卦炉";
    } elseif ($yy_ld_exp == 3) {
        $yy_ld_name = "凤纹炉";
    } elseif ($yy_ld_exp == 4) {
        $yy_ld_name = "盘龙炉";
    } else {
        $yy_ld_name = "玄天炉";
    }
    cmd::addcmd('map_ld,1,' . $i, "<img src=res/img/wp/ldf" . $i . ".png >{$yy_ld_name}");
    br();
}
if ($in_map_id > 5714 && $in_map_id < 5724) {
    $i = value::get_map_value($in_map_id, 'ts_map');
    $yy_yp_yc_yp = value::get_user_value('yy_yp_yc_yp' . $i, $user_id);
    if ($yy_yp_yc_yp < 1) {
        echo "灵田{$i}";
    } else {
        $item_name = value::get_item_value($yy_yp_yc_yp, 'name');
        $item_lb = value::get_item_value($yy_yp_yc_yp, 'yp_lb');
        echo "{$i}. <img src=res/img/wp/yaoc{$item_lb}.png >{$item_name}:";
    }
    echo " ";
    $yy_yp_yc_lt = value::get_user_value('yy_yp_yc_lt' . $i, $user_id);
    $yy_yp_yc_time = value::get_user_value('yy_yp_yc_time' . $i, $user_id);
    $now_time = date("Y-m-d H:i:s");
    if ($yy_yp_yc_yp < 1) {
        if ($yy_yp_yc_lt > 0 || $i == 1) {
            echo "<img src=res/img/wp/lt.png >：";
            cmd::addcmd('map_yy2,' . $i . ',0,0', "种植");
        } else {
            echo "<img src=res/img/wp/kg.png >：";
            cmd::addcmd("map_yy3,0," . $i, "开耕");
        }
    } else {
        if ($yy_yp_yc_time > $now_time) {
            $mzsysj = strtotime($yy_yp_yc_time) - strtotime($now_time);
            $mz_hour = (int)($mzsysj / 3600);
            $mz_min = (int)($mzsysj % 3600 / 60);
            echo "(剩{$mz_hour}时{$mz_min}分)";
        } else {
            cmd::addcmd('map_yy5,' . $i . ',' . $yy_yp_yc_yp, "收获");
        }
    }
    echo " ";
    br();
}
//显示攻击防御战力榜事件
xs_gj_fy();
//攻城事件
$gc_time_h = date("H");
if ($m_area_id == 89) {
    map_gc_sj($m_area_id);
    $gc_hyd_h = value::get_user_value('gc_hyd_h', $user_id);
    if ($gc_time_h != $gc_time_h) {
        value::set_user_value('2.hd_hyd_sz', 10, $user_id);
        value::add_user_value('hd_hydz', 10, $user_id);
        value::set_user_value('gc_hyd_h', $gc_time_h, $user_id);
    }
    if ($gc_time_h != 20) {
        value::set_game_user_value('in_map_id', 1, $user_id);
    }
}
//显示任务提示
$task_ok = false;
$sql = "SELECT `id`,`from_npc`,`name` FROM `task` WHERE `from_map`!={$in_map_id}";
$result = sql($sql);
while (list($task_id, $from_npc, $name) = $result->fetch_row()) {
    if (call_task($task_id)) {
        $task_ok = true;
        break;
    }
}
if ($task_ok) {
    $from_npc_name = value::get_npc_value($from_npc, 'name');
    echo "[主线任务]";
    cmd::addcmd("show_task1", $name);
    br();
}
$ok_task = 0;
$sql = "SELECT `value` FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.task' LIMIT 0,5";
$result = sql($sql);
while (list($task_id) = $result->fetch_row()) {
    if (call_task($task_id, 9)) {
        $ok_task = $task_id;
        break;
    }
}
if ($ok_task) {
    $to_npc = value::get_task_value($ok_task, 'to_npc');
    $sex = value::get_game_user_value('sex');
    $to_npc_name = value::get_task_value($ok_task, 'name');
    echo "[主线任务]";
    cmd::addcmd("call_task,{$ok_task},1", $to_npc_name);
    br();
}

//地图传送操作
if ($in_map_id > 0) {
    dt_cs_cz($m_area_id, $in_map_id);
}

$npcstr = "";
$npc_arr = $m_npc;
if ($npc_arr) {
    //分割npc
    $npc_arr = explode(',', $npc_arr);
    $ncount = count($npc_arr);
    for ($i = 0; $i < $ncount; $i++) {
        $npc_name = value::getvalue('npc', 'name', 'id', $npc_arr[$i]);
        $npc_tp = value::getvalue('npc', 'tp', 'id', $npc_arr[$i]);
        if ($npc_name) {
            if ($i == 0) {
                $npcstr = "<img src='res/img/npc/{$npc_tp}' style='width: 20px;height: 20px;'>" . $npc_name;
            } else {
                $npcstr = '<br>' . "<img src='res/img/npc/{$npc_tp}' style='width: 20px;height: 20px;'>" . $npc_name;
            }

        }
        cmd::addcmd('e8,' . $npc_arr[$i], $npcstr);
    }
    br();
}

//显示任务提示
$ok_task = 0;
$task_ok = false;
$sql = "SELECT `id`,`from_npc`,`name` FROM `task` WHERE `from_map`={$in_map_id}";
$result = sql($sql);
while (list($task_id, $from_npc, $name) = $result->fetch_row()) {
    if (call_task($task_id)) {
        $task_ok = true;
        $from_npc_name = value::get_npc_value($from_npc, 'name');
        echo "<img src='rw5.png' >";
        cmd::addcmd('show_task2,0,' . $task_id, $from_npc_name);
        br();
    }
}
$ok_task = 0;
$sql = "SELECT `value` FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.task' LIMIT 0,5";
$result = sql($sql);
while (list($task_id) = $result->fetch_row()) {
    if (call_task($task_id, 5)) {
        $ok_task = $task_id;
        $to_npc = value::get_task_value($ok_task, 'to_npc');
        $to_npc_name = value::get_npc_value($to_npc, 'name');
        echo "<img src='rw6.png' >";
        cmd::addcmd("call_task,{$ok_task},6", $to_npc_name);
        br();
    }
}

//比奇废矿进入尸王殿
$fk_npc1 = value::get_system_value('fk_npc1');
$fk_npc2 = value::get_system_value('fk_npc2');
$fk_npc3 = value::get_system_value('fk_npc3');
$fk_npc4 = value::get_system_value('fk_npc4');
$now_time = time();
$npc_time1 = (int)(60 - (($now_time - $npc_time) / 60));
$npc_time2 = (int)(2 - (($now_time - $npc_time) / 60));
if ($npc_time1 < 0) {
    if ($in_map_id == $fk_npc1 || $in_map_id == $fk_npc2 || $in_map_id == $fk_npc3 || $in_map_id == $fk_npc4) {
        cmd::addcmd("e1001,30,30", "<span style='color:red'>法老僵尸</span>");
        br();
    }
}
if ($npc_time2 > 0) {
    cmd::addcmd("map_cs_id,575", "<img src='cs.gif' style='width: 25px;height: 25px;'>僵尸的洞");
    br();
}
//宝箱显示
$xs_bx_map = value::get_system_value('xs_bx_map');
if ($in_map_id == $xs_bx_map && $xs_bx_map > 0) {
    echo "<img src='bx.png' style='width: 20px;height: 20px;'>";
    cmd::addcmd('e450', '<span style=color:red>【幸运宝箱】</span>');
    br();
}
//空投显示
$xs_bx_map = value::get_system_value('xs_kt_map');
if ($in_map_id == $xs_bx_map && $xs_bx_map > 0) {
    echo "<img src='kt.png' >";
    cmd::addcmd('e450_kt', '<span style=color:red>【空投】</span>');
    br();
}
//显示玩家与NPC
//获取玩家
$ocount = 0;
$user_arr = "";
//获取地图在线玩家语句
//普通方式
$sql = "SELECT `name`,`id` FROM `game_user` WHERE `id`!='{$user_id}' AND `name`!='' AND `in_map_id` = {$in_map_id} AND `is_online` =1 LIMIT 0, 4";
//开始获取
if ($sql) {
    $result = sql($sql);
    while (list($oname, $oid) = $result->fetch_row()) {
        $ocount++;
        $vip_lvl = user::getVipLvl($oid);
        if ($ocount == 1) {
            $user_arr = "<img src=res/img/system/vip_{$vip_lvl}.gif>" . $oname;
            $using_nick_name = value::get_user_value('using_nick_name', $oid);
            if ($using_nick_name) {
                $user_arr = "" . $using_nick_name . "" . $user_arr;
            }
        } else {
            $vip_lvl = user::getVipLvl($oid);
            $using_nick_name = value::get_user_value('using_nick_name', $oid);
            if ($using_nick_name) {
                $user_arr = $user_arr . "," . $using_nick_name . "<img src=res/img/system/vip_{$vip_lvl}.gif>" . $oname;
            } else {
                $user_arr = $user_arr . ",<img src=res/img/system/vip_{$vip_lvl}.gif>" . $oname;
            }
        }
    }
}
if ($m_is_pk < 1) {
    $sql = "SELECT COUNT(*) FROM `game_user` WHERE `id`!='{$user_id}' AND `name`!='' AND `is_online` !=1";
    $result = sql($sql);
    list($user_count1) = $result->fetch_row();
    if ($user_count1 > 0) {
        value::set_user_value('user_count1', $user_count1, $user_id);
    }
    $sj = mt_rand(1, $user_count1);
    $sj1 = mt_rand(1, 5);
    if ($sj > 0) {
        value::set_user_value('sj10', $sj, $user_id);
    }
    if ($sj1 > 0) {
        value::set_user_value('sj11', $sj1, $user_id);
    }
    $sql = "SELECT `name`,`id` FROM `game_user` WHERE `id`!='{$user_id}' AND `name`!='' AND `is_online` !=1 LIMIT {$sj}, {$sj1}";
    //开始获取
    if ($sql && $user_count1 > 0) {
        $result = sql($sql);
        while (list($lxoname, $oid) = $result->fetch_row()) {
            $ocount++;
            if ($ocount == 1) {
                $vip_lvl = user::getVipLvl($oid);
                $user_arr = "<img src=res/img/system/vip_{$vip_lvl}.gif>" . $lxoname;
            } else {
                $vip_lvl = user::getVipLvl($oid);
                $user_arr = $user_arr . ',' . "<img src=res/img/system/vip_{$vip_lvl}.gif>" . $lxoname;
            }
        }
    }
}
//显示地图宠物
//野外显示
$now_time = time();
$yg_time = (int)(($now_time - $yg_ys) / 60);
if (($m_yaoguai && $yg_sx == 0) || ($yg_time >= $yg_sx)) {
    $pet_str = "";
    $pcount = 0;
    //获取显示宠物
    $sql = "SELECT `name`,`id` FROM `game_pet` WHERE `map_id` = {$in_map_id} AND `master_id`=0 AND `npc_id`=0 AND `master_mode`!=8 LIMIT 0 , 8";
    $result = sql($sql);
    while (list($pet_name, $oid) = $result->fetch_row()) {
        $pcount++;
        if ($pcount == 1) {
            $pet_kg = "  ";
            $pet_str .= "    *" . $pet_name .  $pet_kg;
        } else {
            $pet_kg = "  ";
            $pet_str = $pet_str . $pet_kg . "    *" . $pet_name;
        }
    }
    if ($pcount) {
        if ($yg_sx == 0) {
            echo "怪物:";
        } else {
            echo "BOSS:";
        }
        cmd::addcmd('e14', $pet_str);
        br();
    }
}
$item_count = 0;
$item_str = "";
//显示地上物品
$search = "map.{$in_map_id}.i.";
$sql = "SELECT `valuename` FROM `game_value` WHERE `valuename` LIKE 'map.{$in_map_id}.i.%' LIMIT 0,8";
$result = sql($sql);
while (list($item_id) = $result->fetch_row()) {
    $item_id = str_replace($search, "", $item_id);
    $item_count++;
    if ($item_count == 1) {
        $is_tp = value::getvalue('item', 'tp', 'id', $item_id);
        $item_str = "<img src=res/img/wp/{$is_tp} style='width: 20px;height: 20px;'>" . value::getvalue('item', 'name', 'id', $item_id);
    } else {
        $is_tp = value::getvalue('item', 'tp', 'id', $item_id);
        $item_str .= "  <img src=res/img/wp/{$is_tp} style='width: 20px;height: 20px;'>" . $wp_tp . value::getvalue('item', 'name', 'id', $item_id);
    }
}
//显示地上道具
$sql = "SELECT `star1`,`name`,`prop_id` FROM `game_prop` WHERE `map_id`={$in_map_id} LIMIT 0," . (8 - $item_count);
$result = sql($sql);
while (list($star1, $prop_name, $id) = $result->fetch_row()) {
    $item_count++;
    if ($item_count == 1) {
        $item_str = " <img src=res/img/zb/{$id}.png style='width: 20px;height: 20px;'>" . $prop_name;
    } else {
        $item_str .= " <img src=res/img/zb/{$id}.png style='width: 20px;height: 20px;'>" . $prop_name;
    }
}
if ($item_count >= 8) {
    $item_str .= '等';
}
if ($item_str) {
    echo '地上:';
    cmd::addcmd('e58', $item_str);
    br();
}

//显示系统消息
$min_id = 0;
$chat_count = 0;
$chat_id = 0;
$xt_count = value::get_user_value('xt_count');
$sql = "SELECT `id`,`chats` FROM `game_chat` WHERE `mode` = 0 AND `oid`={$user_id} AND `id`>" . $xt_count . " AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 0, 3 ";
$result = sql($sql);
while (list($chat_id, $chats) = $result->fetch_row()) {
    echo "[系统]" . $chats;
    br();
    $chat_count++;
    if ($chat_count == 1) {
        $min_id = $chat_id;
        value::set_user_value("xt_count", $min_id, $user_id);
    }
    array_push($tmp_chat_id_arr, $chat_id);
}

//显示私人聊天
if (!value::get_user_value('kg.xssrpd')) {
    $siliao_count = value::get_user_value('siliao_count');
    $min_id = 0;
    $chat_count = 0;
    $chat_id = 0;
    if (!$siliao_count) {
        $siliao_count = 0;
    }
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 2 AND `oid`={$user_id} AND  `id`>" . $siliao_count . " ORDER BY `id` DESC LIMIT 0, 5 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
        //输出伴侣昵称
        $bl_id = value::get_user_value('bl.id');
        if ($bl_id == $oid) {
            $bl_mode = value::get_user_value('bl.mode');
            $bl_nick_name = "";
            switch ($bl_mode) {
                case 1:
                    if ($sex == "男") {
                        $bl_nick_name = "未婚妻";
                    } else {
                        $bl_nick_name = "未婚夫";
                    }
                    break;
                case 2:
                    if ($sex == "男") {
                        $bl_nick_name = "老婆";
                    } else {
                        $bl_nick_name = "老公";
                    }
                    break;
            }
            echo "[{$bl_nick_name}]";
        }
        //输出玩家名称 消息内容
        cmd::addcmd('e12,' . $oid, value::get_game_user_value('name', $oid));
        echo " 对你说:" . $chats . " ";
        cmd::addcmd('e53,' . $oid . ',1', '回复');
        br();
        $chat_count++;
        if ($chat_count == 1) {
            $min_id = $chat_id;
            if ($min_id > value::get_user_value('siliao_count')) {
                value::set_user_value('siliao_count', $min_id);
            }
        }
    }
}
//显示师门聊天
if (!value::get_user_value('kg.xssmpd')) {
    $is_chushi = value::get_game_user_value('is_chushi', $user_id);
    if ($is_chushi) {
        $shifu_id = $user_id;
    } else {
        $shifu_id = value::get_game_user_value('shifu.id', $user_id);
    }
    if ($shifu_id) {
        $smlt_count = value::get_user_value('smlt_count');
        $min_id = 0;
        $chat_count = 0;
        $chat_id = 0;
        if (!$smlt_count) {
            $smlt_count = 0;
        }
        $sm_nick_name = "";
        $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 16 AND `oid`={$shifu_id} AND `id`>" . $smlt_count . " AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 0, 5 ";
        $result = sql($sql);
        while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
            $sm_nick_name = user::get_shimen_nick_name($oid);
            echo "[";
            cmd::addcmd('e36,3', '师门');
            echo "]({$sm_nick_name})";
            cmd::addcmd('e12,' . $oid, value::get_game_user_value('name', $oid));
            echo " 说:" . $chats;
            br();
            $chat_count++;
            if ($chat_count == 1) {
                $min_id = $chat_id;
                if ($min_id > value::get_user_value('smlt_count')) {
                    value::set_user_value('smlt_count', $min_id);
                }
            }
        }
    }
}
//显示地区聊天
if (!value::get_user_value('kg.xsdqpd')) {
    $area_id = $m_area_id;
    $dqlt_count = value::get_user_value('dqlt_count');
    $min_id = 0;
    $chat_count = 0;
    $chat_id = 0;
    if (!$dqlt_count) {
        $dqlt_count = 0;
    }
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 1 AND `id`>" . $dqlt_count . " AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 0, 3 ";
    $result = sql($sql);
    while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
        echo "[";
        cmd::addcmd('e36,1', '公共');
        echo "]";
        $gw_name = value::get_pet_value($oid, 'name');
        $pet_id = value::get_pet_value($oid, 'pet_id');
        $img = value::getvalue('pet', 'image', 'id', $pet_id);
        if ($gw_name) {
            pet::img1($img, true, false);
            cmd::addcmd('gl_e15,' . $oid, $gw_name);
            echo " 说:" . $chats;
            br();
            $chat_count++;
            if ($chat_count == 1) {
                $min_id = $chat_id;
                if ($min_id > value::get_user_value('dqlt_count')) {
                    value::set_user_value('dqlt_count', $min_id);
                }
            }
        } else {
            user::showVipLogo($oid, true);
            cmd::addcmd('e12,' . $oid, value::get_game_user_value('name', $oid));
            echo " 说:" . $chats;
            br();
            $chat_count++;
            if ($chat_count == 1) {
                $min_id = $chat_id;
                if ($min_id > value::get_user_value('dqlt_count')) {
                    value::set_user_value('dqlt_count', $min_id);
                }
            }
        }
    }
}
//显示黑市广告
if (!value::get_user_value('kg.xshspd')) {
    value::set_user_value('hs_look_mode', 1);
    $min_id = 0;
    $chat_count = 0;
    $chat_id = 0;
    $hsgg_count = value::get_user_value('hsgg_count');
    $sql = "SELECT `id`,`chats`,`oid`,`guangbo_mode` FROM `game_chat` WHERE `mode` = 5 AND `id`>{$hsgg_count} AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 0, 5 ";
    $result = sql($sql);
    while (list($chat_id, $chats, $prop_id, $guangbo_mode) = $result->fetch_row()) {
        echo '[黑市]' . $chats . ' ';
        if (!$guangbo_mode) {
            cmd::addcmd('e23,5,3,' . $prop_id, '查看详情');
        } else {
            $heishi_pet_id = $prop_id;
            cmd::addcmd('e124,' . $heishi_pet_id, '查看详情');
        }
        br();
        $chat_count++;
        if ($chat_count == 1) {
            $min_id = $chat_id;
            value::set_user_value('hsgg_count', $min_id);
        }
    }
}
//显示组队聊天
if (!value::get_user_value('kg.xszdpd')) {
    if ($team_id) {
        $zdlt_count = value::get_user_value('zdlt_count');
        $min_id = 0;
        $chat_count = 0;
        $chat_id = 0;
        if (!$zdlt_count) {
            $zdlt_count = 0;
        }
        $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 18 AND `oid`={$team_id} AND `id`>" . $zdlt_count . " AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 0, 5 ";
        $result = sql($sql);
        while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
            echo "[";
            cmd::addcmd('e36,5', '组队');
            echo "]";
            cmd::addcmd('e12,' . $oid, value::get_game_user_value('name', $oid));
            echo " 说:" . $chats;
            br();
            $chat_count++;
            if ($chat_count == 1) {
                $min_id = $chat_id;
                if ($min_id > value::get_user_value('zdlt_count')) {
                    value::set_user_value('zdlt_count', $min_id);
                }
            }
        }
    }
}
//显示帮派聊天
if (!value::get_user_value('kg.xsghpd')) {
    if ($union_id) {
        $ghlt_count = value::get_user_value('ghlt_count');
        $min_id = 0;
        $chat_count = 0;
        $chat_id = 0;
        if (!$ghlt_count) {
            $ghlt_count = 0;
        }
        $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 4 AND `oid`={$union_id} AND `id`>" . $ghlt_count . " AND `time`>='{$login_time}' ORDER BY `id` DESC LIMIT 0, 5 ";
        $result = sql($sql);
        while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
            echo "[";
            cmd::addcmd('e36,6', '帮派');
            echo "]";
            cmd::addcmd('e12,' . $oid, value::get_game_user_value('name', $oid));
            echo " 说:" . $chats;
            br();
            $chat_count++;
            if ($chat_count == 1) {
                $min_id = $chat_id;
                if ($min_id > value::get_user_value('ghlt_count')) {
                    value::set_user_value('ghlt_count', $min_id);
                }
            }
        }
    }
}
//帮派合成宠物请求
if ($in_map_id == 62 && $union_id) {
    $ghhcqq_count = value::get_user_value('ghhcqq_count');
    $min_id = 0;
    $chat_count = 0;
    $chat_id = 0;
    if (!$ghhcqq_count) {
        $ghhcqq_count = 0;
    }
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 20 AND `oid`={$user_id} AND `id`>" . $ghhcqq_count . " ORDER BY `id` DESC LIMIT 1";
    $result = sql($sql);
    if (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
        $var_arr = explode(",", $chats);
        $o_pet_id = $var_arr[0];
        $u_pet_id = $var_arr[1];
        echo value::get_game_user_value("name", $oid), "想让", pet::get($o_pet_id, 'name'), "与你的", pet::get($u_pet_id, 'name'), "进行合成?";
        br();
        cmd::addcmd("e201,6," . $oid . "," . $chats, "同意合成");
        br();
        cmd::addcmd("e201,7," . $oid . "," . $chats, "拒绝合成");
        br();
        $chat_count++;
        if ($chat_count == 1) {
            $min_id = $chat_id;
            if ($min_id > value::get_user_value('ghhcqq_count')) {
                value::set_user_value('ghhcqq_count', $min_id);
            }
        }
    }
}

//获取地图出口
if ($m_exit_b || $m_exit_x || $m_exit_d || $m_exit_n) {
    $pk_yggj = 0;
    $pet_yg_sj = mt_rand(1, 100);
    $sql = "SELECT `id` FROM `game_pet` WHERE `map_id` = {$in_map_id} AND `master_id`=0 AND `npc_id`=0 AND `master_mode`!=8 LIMIT 1, 1";
    $result = sql($sql);
    while (list($oid) = $result->fetch_row()) {
        value::set_user_value("pk_yggj", $oid, $user_id);
    }
    $pk_yggj_id = value::get_user_value("pk_yggj", $user_id);
    $map_gw_dg = value::get_user_value("map_gw_dg", $user_id);
    $gw_bg = value::get_user_value("wj_bg", $user_id);
    value::add_user_value('map_gw_dg', -1, $user_id);
    $tsjz_ys = value::get_game_user_value("lvl", $user_id);
    if ($tsjz_ys > $m_lvl) {
        $ts_jz7 = equip::get_user_equip_jc('ts_jz7');
    }
    if ($pet_yg_sj < 10 && $m_is_pk == 1 && $pcount > 0 && $map_gw_dg < 1 && $gw_bg < 1 && $ts_jz7 < 1) {
        $pk_yggj = 1;
    }
    echo '请选择 . ';
    cmd::addcmd('dt,' . $m_area_id, '地图');
    echo ' | ';
    cmd::addcmd('map_cs', '传送');
    br();
    if ($m_exit_b && $pk_yggj != 1) {
        cmd::addcmd('e7,0,1', '北:  ' . value::get_map_value($m_exit_b, 'name', true) . '↑');
        br();
    }
    if ($m_exit_b && $pk_yggj == 1) {
        cmd::addcmd('e48,' . $pk_yggj_id, '北:  ' . value::get_map_value($m_exit_b, 'name', true) . '↑');
        br();
    }
    if ($m_exit_x && $pk_yggj != 1) {
        cmd::addcmd('e7,0,2', '西:  ' . value::get_map_value($m_exit_x, 'name', true) . '←');
        br();
    }
    if ($m_exit_x && $pk_yggj == 1) {
        cmd::addcmd('e48,' . $pk_yggj_id, '西:  ' . value::get_map_value($m_exit_x, 'name', true) . '←');
        br();
    }
    if ($m_exit_d && $pk_yggj != 1) {
        cmd::addcmd('e7,0,3', '东:  ' . value::get_map_value($m_exit_d, 'name', true) . '→');
        br();
    }
    if ($m_exit_d && $pk_yggj == 1) {
        cmd::addcmd('e48,' . $pk_yggj_id, '东:  ' . value::get_map_value($m_exit_d, 'name', true) . '→');
        br();
    }
    if ($m_exit_n && $pk_yggj == 1) {
        cmd::addcmd('e48,' . $pk_yggj_id, '南:  ' . value::get_map_value($m_exit_n, 'name', true) . '↓');
        br();
    }
    if ($m_exit_n && $pk_yggj != 1) {
        cmd::addcmd('e7,0,4', '南:  ' . value::get_map_value($m_exit_n, 'name', true) . '↓');
        br();
    }
}

//显示人物走向
if (!value::get_user_value('kg.xsrwzx')) {
    //读取玩家走向
    $sql = "SELECT `uid`,`chats`  FROM `game_chat` WHERE `mode` = 8 AND `map_id` ='{$in_map_id}' AND `uid`!={$user_id} ORDER BY `id` DESC LIMIT 0,2";
    if ($m_is_danrenfuben) {
        $sql = '';
    }
    if ($m_is_duorenfuben) {
        $team_user_arr = team::get_team_user($team_id, false);
        foreach ($team_user_arr as $team_user_id) {
            if (!$add_sql) {
                $add_sql = "AND (`uid`={$team_user_id} ";
            } else {
                $add_sql .= "OR `uid`={$team_user_id} ";
            }
        }
        if ($add_sql) {
            $add_sql .= ")";
        }
        $sql = "SELECT `uid`,`chats`  FROM `game_chat` WHERE `mode` = 8 AND `map_id` ='{$in_map_id}' AND `uid`!={$user_id} $add_sql ORDER BY `id` DESC LIMIT 0,2";
    }
//显示人物走向
    if ($sql) {
        $result = sql($sql);
        while (list($trend_uid, $fangxiang) = $result->fetch_row()) {
            $dongxiang = "";
            if ($fangxiang == 1) {
                $dongxiang = value::get_map_value($m_exit_b, 'name');
            }
            if ($fangxiang == 2) {
                $dongxiang = value::get_map_value($m_exit_x, 'name');
            }
            if ($fangxiang == 3) {
                $dongxiang = value::get_map_value($m_exit_d, 'name');
            }
            if ($fangxiang == 4) {
                $dongxiang = value::get_map_value($m_exit_n, 'name');
            }
            echo '[场景]' . value::get_game_user_value('name', $trend_uid) . '向' . $dongxiang . '离开';
            br();
        }
    }
}

if ($user_arr) {
    echo "你遇到了:";
    if ($ocount == 1) {
        //直接查看玩家
        cmd::addcmd('e11,' . $user_one_id, $user_arr);
        br();
        //个个玩家
    } else if ($ocount == 2) {
        cmd::addcmd('e11', str_replace(',', ',', $user_arr));
        br();
        //多个玩家
    } else if ($ocount >= 3) {
        cmd::addcmd('e11', c_str_replace_limit(',', ',', $user_arr, 1) . "等");
        br();
    } else {
        cmd::addcmd('e11', c_str_replace_limit(',', ',', $user_arr, 1));
        br();
    }
}

//显示地图描述
if (!value::get_user_value('kg.xscjms')) {
    echo $m_desc . "";
    br();
}
$now_time = time();
$yg_time = (int)(($now_time - $yg_ys) / 60);
$yg_time1 = (int)($yg_sx - $yg_time);
if ($m_yaoguai && $yg_sx != 0 && ($yg_time < $yg_sx)) {
    $pet_str = "";
    $pcount = 0;
    //获取显示宠物
    $sql = "SELECT `name`,`id` FROM `game_pet` WHERE `map_id` = {$in_map_id} AND `master_id`=0 AND `npc_id`=0 AND `master_mode`!=8 LIMIT 0 , 5";
    $result = sql($sql);
    while (list($pet_name, $oid) = $result->fetch_row()) {
        $pcount++;
        if ($pcount == 1) {
            $pet_kg = "  ";
            $pet_str .= "    *" . $pet_name .  $pet_kg;
        } else {
            $pet_kg = "  ";
            $pet_str = $pet_str . $pet_kg . "    *" . $pet_name;
        }
    }
}
if ($m_yaoguai && $yg_sx != 0 && ($yg_time < $yg_sx)) {
    echo "距离  " . $pet_str . "  刷新还剩余距离<span style='color:red'>{$yg_time1}</span>分钟!";
    br();
}
//显示功能
cmd::addcmd('e13', '状态');
echo " . ";
cmd::addcmd('e40,2,1', '背包');
echo " . ";
// 直接跳转到我的技能页面，避免首次点击显示空白问题
cmd::addcmd('map_jn,0', '技能');
echo " . ";
cmd::addcmd('map_zb', '装备');
br();
cmd::addcmd('e36', '聊天');
echo " . ";
cmd::addcmd('e35', '宠物');
echo " . ";
cmd::addcmd('e106', '帮派');
echo " . ";
cmd::addcmd('map_hd', '<span style=color:green>活动</span>');
echo "<img src=res/img/ch/hot.gif>";
br();
cmd::addcmd('e47,2', '排行');
echo " . ";
cmd::addcmd('map_yl', '娱乐');
echo " . ";
cmd::addcmd('map_mj', '秘境');
echo " . ";
cmd::addcmd('map_fb', '副本');
br();
cmd::addcmd('map_pmh', '拍卖');
echo " . ";
cmd::addcmd('gd', '更多');
echo " . ";
cmd::addcmd('map_tz', '挑战');
echo " . ";
cmd::addcmd('e31', '商城');
br();
echo "-";
br();
cmd::addcmd('e4', '游戏首页');
echo " . ";
cmd::addcmd('map_gy', "<span style=color:RED>攻略</span>");
br();
echo "<span style='color:green'>仙盟会报时：" . date("H:i") . "</span>";
$user_id = uid();
//添加入群礼包
br();
if ($user_id == 1092 || $user_id == 100001) {
    cmd::addcmd('add_cdk1', '入群兑换礼包');
}
//游戏设计系统
if (value::get_user_value('game_master', 0, false) && !value::get_user_value('kg.xssjxt')) {
    //地图设计操作
    br();
    //地图属性表格
    echo "<style>td,th{text-align:center;font-size:12px;}</style><table  border='1'><tbody><tr><th>宠物等级</th><th>单人副本</th><th>多人副本</th><th>可PK</th><th>村长家</th><th>仓库</th><th>驿站</th><th>杂货铺</th><th>修行场</th><th>监狱</th><th>帮派领地</th><th>茶楼</th><th>竞技场</th></tr><tr><td><b style='color:#ff0000'>" . $m_lvl . "</b></td><td>" . c_get_map_value('is_danrenfuben') . "</td><td>" . c_get_map_value('is_duorenfuben') . "</td><td>" . c_get_map_value('is_pk') . "</td><td>" . c_get_map_value('is_cunzhangjia') . "</td><td>" . c_get_map_value('is_fengyaoguan') . "</td><td>" . c_get_map_value('is_yizhan') . "</td><td>" . c_get_map_value('is_zahuopu') . "</td><td>" . c_get_map_value('is_xiuxingchang') . "</td><td>" . c_get_map_value('is_jianyu') . "</td><td>" . c_get_map_value('is_gonghuilingdi') . "</td><td>" . c_get_map_value('is_chalou') . "</td><td>" . c_get_map_value('is_jingjichang') . "</td></tr></tbody></table>";
    if ($m_shuxing == 1) {
        echo "<b><span style='color:#0000ff'>需要凫水</span></b>";
        br();
    } else if ($m_shuxing == 2) {
        echo "<b><span style='color:#0000ff'>需要出窍</span></b>";
        br();
    } else if ($m_shuxing == 3) {
        echo "<b><span style='color:#0000ff'>需要潜水</span></b>";
        br();
    } else if ($m_shuxing == 4) {
        echo "<b><span style='color:#0000ff'>需要筋斗云</span></b>";
        br();
    }
    echo "当前地图:" . $m_name . "(s{$in_map_id})";
    br();
    echo "北:";
    if ($m_exit_b == 0) {
        cmd::addcmd('link_map,exit_b', '连接');
    } else {
        echo value::getvalue('map', 'name', 'id', $m_exit_b) . '(s' . $m_exit_b . ')↑ ';
        cmd::addcmd('cut_map,exit_b', '断开');
    }
    br();
    echo "西:";
    if ($m_exit_x == 0) {
        cmd::addcmd('link_map,exit_x', '连接');
    } else {
        echo value::getvalue('map', 'name', 'id', $m_exit_x) . '(s' . $m_exit_x . ')← ';
        cmd::addcmd('cut_map,exit_x', '断开');
    }
    br();
    echo "东:";
    if ($m_exit_d == 0) {
        cmd::addcmd('link_map,exit_d', '连接');
    } else {
        echo value::getvalue('map', 'name', 'id', $m_exit_d) . '(s' . $m_exit_d . ')→ ';
        cmd::addcmd('cut_map,exit_d', '断开');
    }
    br();
    echo "南:";
    if ($m_exit_n == 0) {
        cmd::addcmd('link_map,exit_n', '连接');
    } else {
        echo value::getvalue('map', 'name', 'id', $m_exit_n) . '(s' . $m_exit_n . ')↓ ';
        cmd::addcmd('cut_map,exit_n', '断开');
    }
    br();
    echo '[地图系统]';
    br();
    cmd::addcmd('add_map', '创建地图');
    br();
    cmd::addcmd('add_map,1', '修改地图');
    br();
    //设计系统操作
    echo "[设计系统]";
    br();
    cmd::addcmd("design_game", "游戏设计");
    br();
    cmd::addcmd("game_engine", "游戏引擎");
    //反馈系统操作
    br();
    echo '[反馈系统]';
    br();
    cmd::addcmd('c_fankui', '查看反馈');
    br();
    cmd::addcmd('sum_game_money', '查看货币');
    br();
    cmd::addcmd('add_gz_cdk', '查兑换礼包');
    br();
    cmd::addcmd('e49', '在线玩家');
    br();
    cmd::addcmd('user_show_recharge', '查询充值流水');
    br();
    cmd::addcmd('user_show_recharge1', '查询CDK流水');
}
cmd::set_show_return_game(false);
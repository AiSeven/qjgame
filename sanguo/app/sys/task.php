<?php
//任务事件
//执行任务
function call_task($task_id = 0, $task_mode = 0, $pet_id = 0, $npc_id = 0, $skip_map = false, $show_mode = 0, $user_id = 0)
{
    if (!$user_id) {
        $user_id = uid();
    }
    //每日限额
    $xiane_arr = config::getConfigByName("xiane");
    $day_max_money = $xiane_arr['day_max_money'];
    //每日限额
    $in_map_id = value::get_game_user_value('in_map_id', $user_id);
    $user_name = value::get_game_user_value('name', $user_id);
    $user_fuzhong = user::get_fuzhong(0, $user_id);
    $user_beibaorongliang = user::get_rongliang(0, $user_id);
    $ok = false;
    $sql = "SELECT `id`, `name`,`from_map`,`cs_pet_map`,`cs_npc_map`,`cs_wp_map`, `from_npc`, `from_desc`,`from_item`,`from_item_count`,`from_prop`,`from_prop_star`,`from_prop_count`,`to_map`, `to_npc`, `to_desc`,`to_item`,`to_item_count`,`to_prop`,`to_prop_star`,`to_prop_count`, `mode`,`zhuxian`,`leixing`, `lvl`,`money`,`exp`, `item`, `item_count`, `pet`, `pet_count`,`pet_lvl`, `npc`, `npc_count`,`is_xunhuan`,`trigger_condition` FROM `task` WHERE `id`={$task_id} LIMIT 1";
    $result = sql($sql);
    list($id, $name, $from_map, $cs_pet_map, $cs_npc_map, $cs_wp_map, $from_npc, $from_desc, $from_item, $from_item_count, $from_prop, $from_prop_star, $from_prop_count, $to_map, $to_npc, $to_desc, $to_item, $to_item_count, $to_prop, $to_prop_star, $to_prop_count, $mode, $zhuxian, $leixing, $lvl, $money, $exp, $item, $item_count, $pet, $pet_count, $pet_lvl, $npc, $npc_count, $is_xunhuan, $trigger_condition) = $result->fetch_row();
    $from_npc_name = value::get_npc_value($from_npc, 'name');
    $from_item_arr = explode(',', $from_item);
    $from_item_count_arr = explode(',', $from_item_count);
    $f_i_count = count($from_item_arr);
    $from_prop_arr = explode(',', $from_prop);
    $from_prop_star_arr = explode(',', $from_prop_star);
    $from_prop_count_arr = explode(',', $from_prop_count);
    $f_p_count = count($from_prop_arr);
    //接受任务所需背包容量
    $f_i_fuzhong = 0;
    if ($from_item) {
        for ($i = 0; $i < $f_i_count; $i++) {
            if (!item::get_item($from_item_arr[$i], $user_id)) {
                $f_i_fuzhong += value::get_item_value($from_item_arr[$i], 'fuzhong');
            }
        }
    }
    if ($from_prop) {
        for ($i = 0; $i < $f_p_count; $i++) {
            $f_i_fuzhong += value::get_prop_value($from_prop_arr[$i], 'fuzhong') * $from_prop_count_arr[$i];
        }
    }
    $to_npc_name = value::get_npc_value($to_npc, 'name');
    $to_item_arr = explode(',', $to_item);
    $to_item_count_arr = explode(',', $to_item_count);
    $t_i_count = count($to_item_arr);
    $to_prop_arr = explode(',', $to_prop);
    $to_prop_star_arr = explode(',', $to_prop_star);
    $to_prop_count_arr = explode(',', $to_prop_count);
    $t_p_count = count($to_prop_arr);
    $item_arr = explode(',', $item);
    $item_count_arr = explode(',', $item_count);
    $i_count = count($item_arr);
    //完成任务所需背包容量
    $t_i_fuzhong = 0;
    if ($to_item) {
        for ($i = 0; $i < $t_i_count; $i++) {
            if (!item::get_item($to_item_arr[$i], $user_id)) {
                $t_i_fuzhong += value::get_item_value($to_item_arr[$i], 'fuzhong');
            }
        }
    }
    if ($to_prop) {
        for ($i = 0; $i < $t_p_count; $i++) {
            $t_i_fuzhong += value::get_prop_value($to_prop_arr[$i], 'fuzhong') * $to_prop_count_arr[$i];
        }
    }
    if ($exp) {
        $t_i_fuzhong++;
    }
    if ($item) {
        for ($i = 0; $i < $i_count; $i++) {
            if (item::get_item($item_arr[$i], $user_id) == $item_count_arr[$i]) {
                $t_i_fuzhong -= value::get_item_value($item_arr[$i], 'fuzhong');
            }
        }
    }
    $pet_arr = explode(',', $pet);
    $pet_count_arr = explode(',', $pet_count);
    $pet_lvl_arr = explode(',', $pet_lvl);
    $p_count = count($pet_arr);
    $npc_arr = explode(',', $npc);
    $npc_count_arr = explode(',', $npc_count);
    $n_count = count($npc_arr);
    $u_lvl = user::get_max_pet_lvl($user_id);
    if (!$task_mode) {//触发任务事件
        //主线任务
        if (!$mode) {
            $u_zhuxian = value::get_user_value('zhuxian', $user_id);
            if (($zhuxian == ($u_zhuxian + 1) && !value::get_user_value('task.' . $task_id . '.step', $user_id) && !value::get_user_value("finish.task.{$task_id}", $user_id)) || ($zhuxian == 0 && !value::get_user_value('task.' . $task_id . '.step', $user_id) && !value::get_user_value("finish.task.{$task_id}", $user_id))) {
                $ok = true;
            }
        } else {
            if ($u_lvl >= $lvl && !value::get_user_value('task.' . $task_id . '.step', $user_id)) {
                if ($is_xunhuan || !value::get_user_value("finish.task.{$task_id}", $user_id)) {
                    $ok = true;
                    if ($trigger_condition) {
                        $ok = eval($trigger_condition);
                    }
                }
            }
        }
    } else if ($task_mode == 1) {//查看任务事件
        $task_complete = call_task($task_id, 5, 0, 0, true);
        cmd::addcmd('e108', '任务');
        echo ">> 任务介绍";
        br();
        echo "-";
        br();
        echo "【{$name}】({$lvl}级)";
        if ($show_mode == 1) {
            echo $task_complete ? "<span style='color: blue'>(已完成)</span>" : "";
        }
        $from_npc_name = value::get_npc_value($from_npc, 'name');
        echo "<br><span style=color:green>{$from_npc_name}</span>：“{$from_desc}”";
        if ($item) {
            echo "<br>物品:";
            for ($i = 0; $i < $i_count; $i++) {
                $i_name = value::get_item_value($item_arr[$i], 'name');
                $i_liangci = value::get_item_value($item_arr[$i], 'liangci');
                value::set_user_value('to_map4', $cs_wp_map, $user_id);
                cmd::addcmd('e447', $i_name);
                echo "x{$item_count_arr[$i]}{$i_liangci}" . ($show_mode == 1 ? "" : ",");
                //已接受任务详情
                if ($show_mode == 1) {
                    $k_i_count = item::get_item($item_arr[$i], $user_id);
                    $k_i_count = $item_count_arr[$i] - $k_i_count;
                    if ($k_i_count > 0) {
                        echo "(还差{$k_i_count}{$i_liangci})" . ($show_mode == 1 ? "," : "");
                    } else {
                        echo "(<span style='color: blue'>✔</span>)";
                    }
                }
            }
        }
        //宠物条件
        if ($pet) {
            $leixing_miaoshu = "";
            //普通模式
            if (!$leixing) {
                $leixing_miaoshu = "除妖";
            } else if ($leixing == 1) {
                $leixing_miaoshu = "藏将";
            } else if ($leixing == 2) {
                $leixing_miaoshu = "拥有";
            }
            if ($leixing_miaoshu) {
                echo "<br>{$leixing_miaoshu}:";
                for ($i = 0; $i < $p_count; $i++) {
                    $p_lvl = $pet_lvl_arr[$i];
                    $p_name = value::getvalue('pet', 'name', 'id', $pet_arr[$i]);
                    value::set_user_value('to_map3', $cs_pet_map, $user_id);
                    cmd::addcmd('e446', $p_name);
                    echo ($i ? "," : "") . ($p_lvl ? "{$p_lvl}级" : "") . "x{$pet_count_arr[$i]}只";
                    //已接受任务详情
                    if ($show_mode == 1) {
                        $k_p_count = 0;
                        if (!$leixing) {
                            $k_p_count = value::get_user_value('task.' . $task_id . '.kill.pet.' . $pet_arr[$i], $user_id);
                        } else if ($leixing == 1) {
                            $sql = "SELECT COUNT(`id`) FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=3 AND `pet_id`={$pet_arr[$i]}";
                            $result = sql($sql);
                            list($k_p_count) = $result->fetch_row();
                        } else if ($leixing == 2) {//拥有
                            $sql = "SELECT COUNT(`id`) FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=1 AND `pet_id`={$pet_arr[$i]} AND `lvl`={$pet_lvl_arr[$i]}";
                            $result = sql($sql);
                            list($k_p_count) = $result->fetch_row();
                        }
                        $k_p_count = $pet_count_arr[$i] - $k_p_count;
                        if ($k_p_count > 0) {
                            echo "(还差{$k_p_count}只)";
                        } else {
                            echo "(<span style='color: blue'>✔</span>)";
                        }
                    }
                }
            }
        }
        //NPC条件
        if ($npc) {
            echo "<br>挑战:";
            for ($i = 0; $i < $n_count; $i++) {
                $n_name = value::get_npc_value($npc_arr[$i], 'name');
                value::set_user_value('to_map2', $cs_npc_map, $user_id);
                cmd::addcmd('e445', $n_name);
                echo "x{$npc_count_arr[$i]}次";
                //已接受任务详情
                if ($show_mode == 1) {
                    $k_n_count = value::get_user_value('task.' . $task_id . '.kill.npc.' . $npc_arr[$i], $user_id);
                    $k_n_count = $npc_count_arr[$i] - $k_n_count;
                    if ($k_n_count > 0) {
                        echo "(还差{$k_n_count}次)";
                    } else {
                        echo "(<span style='color: blue'>✔</span>)";
                    }
                }
            }
        }
        if ($task_complete) {
            value::set_user_value('to_map', $to_map, $user_id);
            br();
            echo "领奖:";
            cmd::addcmd('e400', $to_npc_name);
        }
    } else if ($task_mode == 2) {//接受任务事件
        //任务个数
        if (get_task_count($user_id) < 5) {
            //背包限制
            if ($user_fuzhong + $f_i_fuzhong <= $user_beibaorongliang) {
                //接受提示
                cmd::addcmd('e108', '任务');
                echo ">> 接受任务";
                br();
                echo "-";
                br();
                echo "你接受了{$name}({$lvl}级)任务!";
                br();
                echo "-";
                br();
                //接受任务
                sql("INSERT INTO `game_user_value` (`id`, `userid`, `petid`, `valuename`, `value`) VALUES (NULL, '{$user_id}', '0', '{$user_id}.task', '{$task_id}')");
                value::set_user_value('task.' . $task_id . '.step', '1', $user_id);
                //接受物品
                if ($from_item) {
                    for ($i = 0; $i < $f_i_count; $i++) {
                        item::add_item($from_item_arr[$i], $from_item_count_arr[$i], $user_id);
                    }
                }
                //接受道具
                if ($from_prop) {
                    for ($i = 0; $i < $f_p_count; $i++) {
                        $f_p_star = $from_prop_star_arr[$i];
                        if (!$f_p_star) {
                            $f_p_star = 1;
                        }
                        for ($j = 0; $j < $from_prop_count_arr[$i]; $j++) {
                            prop::user_get_prop(prop::new_prop($from_prop_arr[$i], 0, $f_p_star), $user_id, 1, true, true);
                        }
                    }
                }
            } else {
                //接受提示
                echo "背包已满,无法接受!";
                br();
            }
        } else {
            //接受提示
            echo "任务已满,无法接受!";
            br();
        }
        cmd::set_return_game_br(false);
    } else if ($task_mode == 3) {//进行任务事件
        //击杀宠物
        if ($pet) {
            for ($i = 0; $i < $p_count; $i++) {
                if ($pet_arr[$i] == $pet_id) {
                    if (!$leixing) {//普通模式
                        $k_p_count = value::add_user_value('task.' . $task_id . '.kill.pet.' . $pet_id, 1, $user_id);
                        $k_p_count = $pet_count_arr[$i] - $k_p_count;
                        $k_p_name = value::getvalue('pet', 'name', 'id', $pet_id);
                        if ($k_p_count > 0) {
                            skill::add_skill_chat("{$name}({$lvl}级):还差{$k_p_count}只{$k_p_name}。", $user_id, 0);
                        } else {
                            skill::add_skill_chat("{$name}({$lvl}级):<span style=''color: blue''>杀死{$k_p_name}✔。</span>", $user_id, 0);
                        }
                    } else if ($leixing == 3) {//掉落物品
                        if (mt_rand(1, $pet_count_arr[$i]) == 1) {
                            item::add_item($item_arr[$i], 1, $user_id);
                            $k_i_count = item::get_item($item_arr[$i], $user_id);
                            $k_i_count = $k_i_count - $item_count_arr[$i];
                            $k_i_name = value::get_item_value($item_arr[$i], 'name');
                            $k_i_liangci = value::get_item_value($item_arr[$i], 'liangci');
                            if ($k_i_count > 0) {
                                skill::add_skill_chat("{$name}({$lvl}级):还差{$k_i_count}{$k_i_liangci}{$k_i_name}。", $user_id, 0);
                            } else {
                                skill::add_skill_chat("{$name}({$lvl}级):<span style=''color: blue''>获得{$k_i_name}<img src='rwwc.png' >。</span>", $user_id, 0);
                            }
                        }
                    }
                }
            }
        }
        //挑战NPC
        if ($npc) {
            for ($i = 0; $i < $n_count; $i++) {
                if ($npc_arr[$i] == $npc_id) {
                    $k_n_count = value::add_user_value('task.' . $task_id . '.kill.npc.' . $npc_id, 1, $user_id);
                    $k_n_count = $npc_count_arr[$i] - $k_n_count;
                    $k_n_name = value::getvalue('npc', 'name', 'id', $npc_id);
                    if ($k_n_count > 0) {
                        skill::add_skill_chat("{$name}({$lvl}级):还差{$k_n_count}只{$k_n_name}。", $user_id, 0);
                    } else {
                        skill::add_skill_chat("{$name}({$lvl}级):<span style=''color: blue''>挑战{$k_n_name}<img src='rwwc.png' >。</span>", $user_id, 0);
                    }
                }
            }
        }
        //任务是否完成
        if (call_task($task_id, 5, 0, 0, true, 0, $user_id)) {
            skill::add_skill_chat("{$name}({$lvl}级):<span style=''color: blue''>任务已完成,快去领奖吧!</span>", $user_id, 0);
        }
    } else if ($task_mode == 4) {//取消任务事件
        if (!call_task($task_id, 5, 0, 0, true)) {
            if (!$from_prop && !$from_item && (!$npc || $is_xunhuan) && ($task_id < 180 && $task_id > 182)) {
                call_task($task_id, 7);
                echo "{$name}({$lvl})任务取消成功!";
                br();
            } else {
                echo "特殊任务,无法取消!";
                br();
            }
        } else {
            echo "任务完成,无法取消!";
            br();
        }
        show_task(1);
    } else if ($task_mode == 5) {//完成任务事件
        $i_ok = true;
        $p_ok = true;
        $n_ok = true;
        $m_ok = true;
        if (!$skip_map) {
            //地图条件
            if ($to_map != $in_map_id) {
                $m_ok = false;
            }
        }
        //物品条件
        if ($item) {
            for ($i = 0; $i < $i_count; $i++) {
                if (item::get_item($item_arr[$i], $user_id) < $item_count_arr[$i]) {
                    $i_ok = false;
                    break;
                }
            }
        }
        //宠物条件
        if ($pet) {
            for ($i = 0; $i < $p_count; $i++) {
                if (!$leixing) {//除妖
                    if (value::get_user_value('task.' . $task_id . '.kill.pet.' . $pet_arr[$i], $user_id) < $pet_count_arr[$i]) {
                        $p_ok = false;
                        break;
                    }
                } else if ($leixing == 1) {//藏将
                    $sql = "SELECT COUNT(`id`) FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=3 AND `pet_id`={$pet_arr[$i]}";
                    $result = sql($sql);
                    list($c_p_count) = $result->fetch_row();
                    if ($c_p_count < $pet_count_arr[$i]) {
                        $p_ok = false;
                        break;
                    }
                } else if ($leixing == 2) {//拥有
                    $sql = "SELECT COUNT(`id`) FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=1 AND `pet_id`={$pet_arr[$i]} AND `lvl`={$pet_lvl_arr[$i]}";
                    $result = sql($sql);
                    list($c_p_count) = $result->fetch_row();
                    if ($c_p_count < $pet_count_arr[$i]) {
                        $p_ok = false;
                        break;
                    }
                }
            }
        }
        //NPC条件
        if ($npc) {
            for ($i = 0; $i < $n_count; $i++) {
                if (value::get_user_value('task.' . $task_id . '.kill.npc.' . $npc_arr[$i], $user_id) < $npc_count_arr[$i]) {
                    $n_ok = false;
                    break;
                }
            }
        }
        //所有条件
        if ($m_ok && $i_ok && $p_ok && $n_ok) {
            $ok = true;
        }
    } else if ($task_mode == 9) {//完成任务事件
        $i_ok = true;
        $p_ok = true;
        $n_ok = true;
        $m_ok = true;
        if (!$skip_map) {
            //地图条件
            if ($to_map == $in_map_id) {
                $m_ok = false;
            }
        }
        //物品条件
        if ($item) {
            for ($i = 0; $i < $i_count; $i++) {
                if (item::get_item($item_arr[$i], $user_id) < $item_count_arr[$i]) {
                    $i_ok = false;
                    break;
                }
            }
        }
        //宠物条件
        if ($pet) {
            for ($i = 0; $i < $p_count; $i++) {
                if (!$leixing) {//除妖
                    if (value::get_user_value('task.' . $task_id . '.kill.pet.' . $pet_arr[$i], $user_id) < $pet_count_arr[$i]) {
                        $p_ok = false;
                        break;
                    }
                } else if ($leixing == 1) {//藏将
                    $sql = "SELECT COUNT(`id`) FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=3 AND `pet_id`={$pet_arr[$i]}";
                    $result = sql($sql);
                    list($c_p_count) = $result->fetch_row();
                    if ($c_p_count < $pet_count_arr[$i]) {
                        $p_ok = false;
                        break;
                    }
                } else if ($leixing == 2) {//拥有
                    $sql = "SELECT COUNT(`id`) FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=1 AND `pet_id`={$pet_arr[$i]} AND `lvl`={$pet_lvl_arr[$i]}";
                    $result = sql($sql);
                    list($c_p_count) = $result->fetch_row();
                    if ($c_p_count < $pet_count_arr[$i]) {
                        $p_ok = false;
                        break;
                    }
                }
            }
        }
        //NPC条件
        if ($npc) {
            for ($i = 0; $i < $n_count; $i++) {
                if (value::get_user_value('task.' . $task_id . '.kill.npc.' . $npc_arr[$i], $user_id) < $npc_count_arr[$i]) {
                    $n_ok = false;
                    break;
                }
            }
        }
        //所有条件
        if ($m_ok && $i_ok && $p_ok && $n_ok) {
            $ok = true;
        }
    } else if ($task_mode == 6) {//领奖任务事件
        if (call_task($task_id, 5)) {
            //背包容量
            if ($user_fuzhong + $t_i_fuzhong <= $user_beibaorongliang) {
                //完成描述
                $to_npc_name = value::get_npc_value($to_npc, 'name');
                echo "【{$name}】({$lvl}级)<br><span style=color:green>{$to_npc_name}</span>：“{$to_desc}”";
                br();
                //师徒奖励
                $u_shifu_id = value::get_game_user_value('shifu.id', $user_id);
                $u_shifu_name = value::get_game_user_value('name', $u_shifu_id);
                //任务物品
                if ($item) {
                    for ($i = 0; $i < $i_count; $i++) {
                        item::add_item($item_arr[$i], -1 * $item_count_arr[$i], $user_id);
                    }
                }
                //任务宠物
                if ($pet) {
                    for ($i = 0; $i < $p_count; $i++) {
                        $c_p_name = value::getvalue('pet', 'name', 'id', $pet_arr[$i]);
                        if ($leixing == 1) {
                            echo "你失去了{$pet_count_arr[$i]}只{$c_p_name}。";
                            br();
                            $sql = "SELECT `id` FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=3 AND `pet_id`={$pet_arr[$i]} LIMIT {$pet_count_arr[$i]}";
                            $rs = sql($sql);
                            while (list($d_pet_id) = $rs->fetch_row()) {
                                pet::del_pet($d_pet_id);
                            }
                        }
                    }
                }
                //完成道具
                if ($to_prop) {
                    for ($i = 0; $i < $t_p_count; $i++) {
                        $t_p_star = $to_prop_star_arr[$i];
                        if (!$t_p_star) {
                            $t_p_star = 1;
                        }
                        for ($j = 0; $j < $to_prop_count_arr[$i]; $j++) {
                            prop::user_get_prop(prop::new_prop($to_prop_arr[$i], 0, $t_p_star), $user_id, 1, true, true);
                        }
                    }
                }
                //完成物品
                if ($to_item) {
                    for ($i = 0; $i < $t_i_count; $i++) {
                        if ($to_item_arr[$i] > 29 && $to_item_arr[$i] < 42) {
                            //师徒奖励
                            if ($u_shifu_id) {
                                value::add_user_value('chongshengdian', 5, $u_shifu_id);
                                value::add_user_value('shengwangzhi', 10, $u_shifu_id);
                            }
                            //徽章广播
                            $shengxiaozhang_name = value::get_item_value($to_item_arr[$i], 'name');
                            c_add_guangbo("恭喜{$user_name}获得了{$shengxiaozhang_name}!", 6);
                            //镇妖天王
                            if ($to_item_arr[$i] == 41) {
                                item::add_item(88, 1, true, $user_id, true);
                            }
                        }
                        item::add_item($to_item_arr[$i], $to_item_count_arr[$i], $user_id);
                    }
                }
                //每日限额
                $today_money_ok = 1;
                if ($is_xunhuan) {
                    $today_money = value::get_user_value("today.jq", $user_id);
                    if ($user_id) {
                        value::add_user_value("today.jq", $money, $user_id);
                    } else {
                        echo "金钱奖励已达到每日限额。";
                        br();
                    }
                }
                //每日限额
                //奖励金钱
                if ($money && $today_money_ok) {
                    item::add_money($money, $user_id);
                }
                //奖励经验丹
                if ($exp) {
                    user::get_exp($user_id, (int)$exp);
                }
                //主线任务
                if (!$mode) {
                    value::add_user_value('zhuxian', 1, $user_id);
                }
                //记录已完成任务
                value::set_user_value("finish.task.{$task_id}", '1', $user_id);
                //清除任务相关数据
                call_task($task_id, 7);
            } else {
                //完成提示
                echo "背包已满,无法领奖!";
                br();
            }
        } else {
            echo "抱歉,你还没有完成这个任务。";
            br();
        }
        cmd::set_return_game_br(false);
    } else if ($task_mode == 10) {//领奖任务事件
        if (call_task($task_id, 9)) {
            //背包容量
            if ($user_fuzhong + $t_i_fuzhong <= $user_beibaorongliang) {
                //完成描述
                echo "【{$name}】({$lvl}级)<br>{$to_desc}";
                br();
                //师徒奖励
                $u_shifu_id = value::get_game_user_value('shifu.id', $user_id);
                $u_shifu_name = value::get_game_user_value('name', $u_shifu_id);
                //任务物品
                if ($item) {
                    for ($i = 0; $i < $i_count; $i++) {
                        item::add_item($item_arr[$i], -1 * $item_count_arr[$i], $user_id);
                    }
                }
                //任务宠物
                if ($pet) {
                    for ($i = 0; $i < $p_count; $i++) {
                        $c_p_name = value::getvalue('pet', 'name', 'id', $pet_arr[$i]);
                        if ($leixing == 1) {
                            echo "你失去了{$pet_count_arr[$i]}只{$c_p_name}。";
                            br();
                            $sql = "SELECT `id` FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=3 AND `pet_id`={$pet_arr[$i]} LIMIT {$pet_count_arr[$i]}";
                            $rs = sql($sql);
                            while (list($d_pet_id) = $rs->fetch_row()) {
                                pet::del_pet($d_pet_id);
                            }
                        }
                    }
                }
                //完成道具
                if ($to_prop) {
                    for ($i = 0; $i < $t_p_count; $i++) {
                        $t_p_star = $to_prop_star_arr[$i];
                        if (!$t_p_star) {
                            $t_p_star = 1;
                        }
                        for ($j = 0; $j < $to_prop_count_arr[$i]; $j++) {
                            prop::user_get_prop(prop::new_prop($to_prop_arr[$i], 0, $t_p_star), $user_id, 1, true, true);
                        }
                    }
                }
                //完成物品
                if ($to_item) {
                    for ($i = 0; $i < $t_i_count; $i++) {
                        if ($to_item_arr[$i] > 29 && $to_item_arr[$i] < 42) {
                            //师徒奖励
                            if ($u_shifu_id) {
                                value::add_user_value('chongshengdian', 5, $u_shifu_id);
                                value::add_user_value('shengwangzhi', 10, $u_shifu_id);
                            }
                            //徽章广播
                            $shengxiaozhang_name = value::get_item_value($to_item_arr[$i], 'name');
                            c_add_guangbo("恭喜{$user_name}获得了{$shengxiaozhang_name}!", 6);
                            //镇妖天王
                            if ($to_item_arr[$i] == 41) {
                                item::add_item(88, 1, true, $user_id, true);
                            }
                        }
                        item::add_item($to_item_arr[$i], $to_item_count_arr[$i], $user_id);
                    }
                }
                //每日限额
                $today_money_ok = 1;
                if ($is_xunhuan) {
                    $today_money = value::get_user_value("today.jq", $user_id);
                    if ($user_id) {
                        value::add_user_value("today.jq", $money, $user_id);
                    } else {
                        echo "金钱奖励已达到每日限额。";
                        br();
                    }
                }
                //每日限额
                //奖励金钱
                if ($money && $today_money_ok) {
                    item::add_money($money, $user_id);
                }
                //奖励经验丹
                if ($exp) {
                    user::get_exp($user_id, (int)$exp);
                }
                //主线任务
                if (!$mode) {
                    value::add_user_value('zhuxian', 1, $user_id);
                }
                //记录已完成任务
                value::set_user_value("finish.task.{$task_id}", '1', $user_id);
                //清除任务相关数据
                call_task($task_id, 7);
            } else {
                //完成提示
                echo "背包已满,无法领奖!";
                br();
            }
        } else {
            echo "抱歉,你还没有完成这个任务。";
            br();
        }
        cmd::set_return_game_br(false);
    } else if ($task_mode == 7) {//清除任务事件
        sql("DELETE FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename`='{$user_id}.task' AND `value`='{$task_id}'");
        sql("DELETE FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.task.{$task_id}.%'");
    } else if ($task_mode == 8) {//任务宠物事件
        $yg_ok = false;
        if ($pet) {
            $yaoguai = value::get_map_value($in_map_id, 'yaoguai');
            $yaoguai_arr = explode(',', $yaoguai);
            if (array_intersect($yaoguai_arr, $pet_arr)) {
                $yg_ok = true;
            }
        }
        $ok = $yg_ok;
    }
    return $ok;
}

//查看任务列表
function show_task($mode = 0, $task_id = 0)
{
    $user_id = uid();
    $in_map_id = value::get_game_user_value('in_map_id', $user_id);
    if (!$mode) {//查看可接受任务
        $sql = "SELECT `id`,`from_npc` FROM `task` WHERE `from_map`={$in_map_id}";
        $result = sql($sql);
        while (list($task_id, $from_npc) = $result->fetch_row()) {
            if (call_task($task_id)) {
                call_task($task_id, 1);
                br();
                cmd::addcmd('call_task,' . $task_id . ',2', '接受任务');
                br();
                echo "-";
            }
        }
    } else if ($mode == 1) {//查看已接受任务
        $t_count = 0;
        $is_zhuxian = 0;
        $sql = "SELECT `value` FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.task' LIMIT 0,5";
        $result = sql($sql);
        while (list($task_id) = $result->fetch_row()) {
            call_task($task_id, 1, 0, 0, false, 1);
            br();
            cmd::addcmd('call_task,' . $task_id . ',4', '取消任务');
            br();
            echo "-";
            br();
            $t_count++;
            if (value::get_task_value($task_id, 'zhuxian')) {
                $is_zhuxian++;
            }
        }
        if (!$t_count) {
            echo "你暂时还没有接受的任务!";
            br();
        }
        if (!$is_zhuxian) {
            $zhuxian = value::get_user_value('zhuxian', $user_id);
            $zhuxian++;
            $sql = "SELECT * FROM task WHERE zhuxian=$zhuxian LIMIT 1";
            $rs = sql($sql);
            $task_arr = $rs->fetch_array();
            if ($task_arr) {
                $map_name = value::get_map_value($task_arr['from_map'], 'name');
                $map_id1 = $task_arr['from_map'];
                $npc_name = value::get_npc_value($task_arr['from_npc'], 'name');
                value::set_user_value('to_map', $map_id1, $user_id);
                echo "请找";
                cmd::addcmd('e444', $map_name);
                echo "的";
                cmd::addcmd('e444', $npc_name);
                echo "接取下一个主线任务!";
                br();
            }
        }
        cmd::addcmd("show_task,2", '返回上级');
    } else if ($mode == 2) {//查看已接受任务
        cmd::addcmd('e108', '任务');
        echo ">> 我的任务";
        br();
        echo "-";
        br();
        $t_count = 1;
        $is_zhuxian = 0;
        $sql = "SELECT `value` FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.task' LIMIT 0,5";
        $result = sql($sql);
        while (list($task_id) = $result->fetch_row()) {
            $task_id_name = value::get_task_value($task_id, 'name');
            cmd::addcmd('show_task,1,' . $task_id, $t_count . '.' . $task_id_name);
            br();
            $t_count++;
            if (value::get_task_value($task_id, 'zhuxian')) {
                $is_zhuxian++;
            }
        }
        if (!$t_count) {
            echo "你暂时还没有接受的任务!";
            br();
        }
        if (!$is_zhuxian) {
            $zhuxian = value::get_user_value('zhuxian', $user_id);
            $zhuxian++;
            $sql = "SELECT * FROM task WHERE zhuxian=$zhuxian LIMIT 1";
            $rs = sql($sql);
            $task_arr = $rs->fetch_array();
            if ($task_arr) {
                $map_name = value::get_map_value($task_arr['from_map'], 'name');
                $map_id1 = $task_arr['from_map'];
                $npc_name = value::get_npc_value($task_arr['from_npc'], 'name');
                value::set_user_value('to_map', $map_id1, $user_id);
                echo "请找";
                cmd::addcmd('e444', $map_name);
                echo "的";
                cmd::addcmd('e444', $npc_name);
                echo "接取下一个主线任务!";
                br();
            }
        }
        echo "-";
    }
}

//查看任务列表
function show_task2($mode = 0, $task_id = 0)
{
    $user_id = uid();
    $in_map_id = value::get_game_user_value('in_map_id', $user_id);
    if (!$mode) {//查看可接受任务
        $sql = "SELECT `from_npc` FROM `task` WHERE `from_map`={$in_map_id} AND `id`={$task_id}";
        $result = sql($sql);
        while (list($from_npc) = $result->fetch_row()) {
            if (call_task($task_id)) {
                call_task($task_id, 1);
                br();
                cmd::addcmd('call_task,' . $task_id . ',2', '接受任务');
                br();
                echo "-";
            }
        }
    } else if ($mode == 1) {//查看已接受任务
        $t_count = 0;
        $is_zhuxian = 0;
        $sql = "SELECT `value` FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.task' LIMIT 0,5";
        $result = sql($sql);
        while (list($task_id) = $result->fetch_row()) {
            call_task($task_id, 1, 0, 0, false, 1);
            br();
            cmd::addcmd('call_task,' . $task_id . ',4', '取消任务');
            br();
            echo "-";
            br();
            $t_count++;
            if (value::get_task_value($task_id, 'zhuxian')) {
                $is_zhuxian++;
            }
        }
        if (!$t_count) {
            echo "你暂时还没有接受的任务!";
            br();
        }
        if (!$is_zhuxian) {
            $zhuxian = value::get_user_value('zhuxian', $user_id);
            $zhuxian++;
            $sql = "SELECT * FROM task WHERE zhuxian=$zhuxian LIMIT 1";
            $rs = sql($sql);
            $task_arr = $rs->fetch_array();
            if ($task_arr) {
                $map_name = value::get_map_value($task_arr['from_map'], 'name');
                $map_id1 = $task_arr['from_map'];
                $npc_name = value::get_npc_value($task_arr['from_npc'], 'name');
                value::set_user_value('to_map', $map_id1, $user_id);
                echo "请找";
                cmd::addcmd('e444', $map_name);
                echo "的";
                cmd::addcmd('e444', $npc_name);
                echo "接取下一个主线任务!";
                br();
            }
        }
        cmd::addcmd("show_task,2", '返回上级');
    } else if ($mode == 2) {//查看已接受任务
        cmd::addcmd('e108', '任务');
        echo ">> 我的任务";
        br();
        echo "-";
        br();
        $t_count = 1;
        $is_zhuxian = 0;
        $sql = "SELECT `value` FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.task' LIMIT 0,5";
        $result = sql($sql);
        while (list($task_id) = $result->fetch_row()) {
            $task_id_name = value::get_task_value($task_id, 'name');
            cmd::addcmd('show_task,1,' . $task_id, $t_count . '.' . $task_id_name);
            br();
            $t_count++;
            if (value::get_task_value($task_id, 'zhuxian')) {
                $is_zhuxian++;
            }
        }
        if (!$t_count) {
            echo "你暂时还没有接受的任务!";
            br();
        }
        if (!$is_zhuxian) {
            $zhuxian = value::get_user_value('zhuxian', $user_id);
            $zhuxian++;
            $sql = "SELECT * FROM task WHERE zhuxian=$zhuxian LIMIT 1";
            $rs = sql($sql);
            $task_arr = $rs->fetch_array();
            if ($task_arr) {
                $map_name = value::get_map_value($task_arr['from_map'], 'name');
                $map_id1 = $task_arr['from_map'];
                $npc_name = value::get_npc_value($task_arr['from_npc'], 'name');
                value::set_user_value('to_map', $map_id1, $user_id);
                echo "请找";
                cmd::addcmd('e444', $map_name);
                echo "的";
                cmd::addcmd('e444', $npc_name);
                echo "接取下一个主线任务!";
                br();
            }
        }
        echo "-";
    }
}

//查看任务列表
function show_task1($mode = 0)
{
    $user_id = uid();
    $in_map_id = value::get_game_user_value('in_map_id', $user_id);
    if (!$mode) {//查看可接受任务
        $sql = "SELECT `id`,`from_npc` FROM `task` WHERE `from_map`!={$in_map_id}";
        $result = sql($sql);
        while (list($task_id, $from_npc) = $result->fetch_row()) {
            if (call_task($task_id)) {
                call_task($task_id, 1);
                br();
                echo "-";
                br();
            }
        }
    }
    cmd::addcmd('e0', '返回上级');
}

//寻找任务宠物
function find_task_pet()
{
    $user_id = uid();
    $in_map_id = value::get_game_user_value('in_map_id', $user_id);
    $map_lvl = value::get_map_value($in_map_id, 'lvl');
    $task_pet_arr = array();
    $sql = "SELECT `id`  FROM `game_pet` WHERE `enemy_user` = {$user_id} AND `master_mode` = 8 LIMIT 1";
    $result = sql($sql);
    if (list($old_id) = $result->fetch_row()) {
        pet::del_pet($old_id);
    }
    $sql = "SELECT `value` FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.task' LIMIT 0,5";
    $result = sql($sql);
    while (list($task_id) = $result->fetch_row()) {
        if (!call_task($task_id, 5)) {
            $pet = value::get_task_value($task_id, 'pet');
            if ($pet) {
                $need_pet_arr = array();
                $pet_arr = explode(',', $pet);
                $pet_count = value::get_task_value($task_id, 'pet_count');
                $pet_count_arr = explode(',', $pet_count);
                $pet_arr_count = count($pet_arr);
                for ($i = 0; $i < $pet_arr_count; $i++) {
                    if (value::get_user_value('task.' . $task_id . '.kill.pet.' . $pet_arr[$i], $user_id) < $pet_count_arr[$i]) {
                        array_push($need_pet_arr, $pet_arr[$i]);
                    }
                }
                $yaoguai = value::get_map_value($in_map_id, 'yaoguai');
                $yaoguai_arr = explode(',', $yaoguai);
                $t_p_arr = array_intersect($yaoguai_arr, $need_pet_arr);
                $task_pet_arr = array_merge($task_pet_arr, $t_p_arr);
            }
        }
    }
    if ($task_pet_arr) {
        cmd::addcmd('find_task_pet', '继续寻找');
        br();
        $task_pet_count = count($task_pet_arr);
        $find_pet_id = $task_pet_arr[mt_rand(1, $task_pet_count) - 1];
        if ($find_pet_id) {
            $oid = pet::new_pet($find_pet_id, $map_lvl + mt_rand(0, 2), $in_map_id, 0, 0, 0, 0);
            value::set_pet_value($oid, 'enemy_user', $user_id);
            value::set_pet_value($oid, 'master_mode', '8');
            //获取宠物属性
            $sql = "SELECT `pet_id`,`name`,`sex`,`nick_name`,`lvl`,`max_lvl`,`hp`,`max_hp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`exp`,`max_exp`,`shuxingdian`,`zhongcheng`,`zhuangtai`,`xingge`,`texing`,`zhuansheng`,`is_pk`,`hechongcishu` FROM `game_pet` WHERE `id`={$oid} LIMIT 1";
            $result = sql($sql);
            list($ozzid, $oname, $osex, $nick_name, $lvl, $max_lvl, $hp, $max_hp, $pugong, $pufang, $tegong, $tefang, $minjie, $exp, $max_exp, $shuxingdian, $zhongcheng, $zhuangtai, $xingge, $texing, $zhuansheng, $is_pk, $hechongcishu) = $result->fetch_row();
            $hp = (int)$hp;
            $max_hp = (int)$max_hp;
            $pugong = (int)$pugong;
            $pufang = (int)$pufang;
            $tegong = (int)$tegong;
            $tefang = (int)$tefang;
            $minjie = (int)$minjie;
            //六维加成
            $pugong_jc = pet::get_liuwei_jiacheng($oid, 'pugong');
            $pufang_jc = pet::get_liuwei_jiacheng($oid, 'pufang');
            $tegong_jc = pet::get_liuwei_jiacheng($oid, 'tegong');
            $tefang_jc = pet::get_liuwei_jiacheng($oid, 'tefang');
            $minjie_jc = pet::get_liuwei_jiacheng($oid, 'minjie');
            $hp_jc = pet::get_liuwei_jiacheng($oid, 'hp');
            //获取种族属性
            $sql = "SELECT `image`,`desc`,`shuxing`,`study_lvl`,`jh_id`,`jh_lvl` FROM `pet` WHERE `id`={$ozzid}" . " LIMIT 1";
            $result = sql($sql);
            list($image, $desc, $shuxing, $study_lvl, $jh_id, $jh_lvl) = $result->fetch_row();
            //输出宠物名字
            echo $oname . "(" . $shuxing . ")" . ($is_pk ? "(战斗中)" : "");
            br();
            //输出图片 描述
            if (!value::get_user_value('kg.xscwtp')) {
                if ($image != "") {
                    pet::img($image, true, false);
                    br();
                }
            }
            if ($desc != "") {
                echo $desc;
            } else {
                echo "这是一只" . $oname . "。";
            }
            br();
            //输出等级 六维 属性 属性点 忠诚度 状态 性格 天赋
            echo "等级:" . $lvl;
            br();
            echo "生命:" . $hp . "/" . $max_hp . ($hp_jc ? "+{$hp_jc}" : "");
            br();
            echo "攻击:" . ($pugong > 0 ? $pugong : 1) . ($pugong_jc ? "+{$pugong_jc}" : "") . " " . "防御:" . ($pufang > 0 ? $pufang : 1) . ($pufang_jc ? "+{$pufang_jc}" : "");
            br();
            echo "魔攻:" . ($tegong > 0 ? $tegong : 1) . ($tegong_jc ? "+{$tegong_jc}" : "") . " " . "魔防:" . ($tefang > 0 ? $tefang : 1) . ($tefang_jc ? "+{$tefang_jc}" : "");
            br();
            cmd::addcmd('e48,' . $oid, '挑战');
            br();
            cmd::addcmd('find_task_pet', '继续寻找');
            br();
        }
    } else {
        echo "你找了好久,什么也没有找到。";
        br();
    }
    cmd::set_return_game_br(false);
}

//是否可挑战NPC
function task_can_pk_npc($npc_id)
{
    $user_id = uid();
    $can_pk = false;
    $sql = "SELECT `value` FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` = '{$user_id}.task' LIMIT 0,5";
    $result = sql($sql);
    while (list($task_id) = $result->fetch_row()) {
        if (!call_task($task_id, 5, 0, 0, true)) {
            $npc = value::get_task_value($task_id, 'npc');
            if ($npc) {
                $npc_count = value::get_task_value($task_id, 'npc_count');
                $npc_arr = explode(',', $npc);
                $npc_count_arr = explode(',', $npc_count);
                $n_count = count($npc_arr);
                for ($i = 0; $i < $n_count; $i++) {
                    if ($npc_id == $npc_arr[$i]) {
                        if (value::get_user_value('task.' . $task_id . '.kill.npc.' . $npc_arr[$i], $user_id) < $npc_count_arr[$i]) {
                            $can_pk = true;
                            break;
                        }
                    }
                }
            }
        }
    }
    return $can_pk;
}

//任务数量
function get_task_count($user_id = 0)
{
    if (!$user_id) {
        $user_id = uid();
    }
    $sql = "SELECT COUNT(`id`) FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename`='{$user_id}.task'";
    $result = sql($sql);
    list($count) = $result->fetch_row();
    return $count;
}
//任务事件
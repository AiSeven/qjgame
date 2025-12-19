<?php
//基础函数
//获取uid
function uid()
{
    return $GLOBALS['uid'];
}

//换行
function br()
{
    echo "<br />";
}

//查询
function sql($sql)
{
    return value::query($sql);
}

//基础函数
//调用函数总事件
function call_func($event)
{
    //读取现在系统时间
    $now_time = date('Y-m-d H:i:s');
    //读取玩家是否在线
    $is_online = value::get_game_user_value('is_online');
    //记录事件str
    $event_str = "";
    //删除所有cmd
    cmd::delallcmd();
    //调用事件
    $earr = explode(",", $event);
    if (count($earr) == 1) {
        call_user_func($event);
    } else {
        $event = array_shift($earr);
        call_user_func_array($event, $earr);
        for ($i = 0; $i < count($earr); $i++) {
            if ($i) {
                $event_str .= "," . $earr[$i];
            } else {
                $event_str = $earr[$i];
            }
        }
    }
    //记录用户数据
    if ($is_online && $event != 'e2') {
        //记录最后刷新
        value::set_game_user_value('refresh_time', $now_time);
        //记录连接ip
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (strstr($ip, ",")) {
            $ip_arr = explode(",", $ip);
            $ip = $ip_arr[0];
        }
        value::set_game_user_value('ip', $ip);
    }
}

//系统广播函数与系统消息函数
//记录用户行为事件
function c_log($event, $log_str = "")
{
    $game_area = value::get_game_user_value('game_area');
    $user_id = uid();
    $name = value::get_game_user_value('name');
    //记录目录
    $log_dir = "app/log/g" . $game_area;
    if (!is_dir($log_dir)) {
        mkdir($log_dir);
    }
    //区服记录
    $log_dir = "app/log/g" . $game_area . "/area";
    if (!is_dir($log_dir)) {
        mkdir($log_dir);
    }
    $log_file = fopen($log_dir . "/" . date("Y-m-d") . ".txt", "a");
    fwrite($log_file, $log_str);
    fclose($log_file);
    //玩家纪录
    $log_dir = "app/log/g" . $game_area . "/user";
    if (!is_dir($log_dir)) {
        mkdir($log_dir);
    }
    $log_dir = "app/log/g" . $game_area . "/user/" . $user_id;
    if (!is_dir($log_dir)) {
        mkdir($log_dir);
    }
    $log_file = fopen($log_dir . "/" . date("Y-m-d") . ".txt", "a");
    fwrite($log_file, $log_str);
    fclose($log_file);
}

//选择区服函数
function c_choice_area()
{
    //输出进入游戏链接
    echo "<img src='logo.png'><br><span>选择区服,进入游戏!</span><br>   ";
    br();
    echo "<a href='game.php?cmd=login&g=1'>绿色传奇</a><br/>";
    echo "   <br>绿色传奇,一路有你!";
}

//系统定时函数
//下线玩家函数
function c_exit_user()
{
    //下线离开玩家
    $exit_time = date("Y-m-d H:i:s", strtotime("-10 minute"));
    $sql = "SELECT `id` FROM `game_user` WHERE `refresh_time` < '{$exit_time}'  AND `is_online`=1";
    $result = sql($sql);
    while (list($o_user_id) = $result->fetch_row()) {
        //设置玩家离线
        $obj = new game_pet_object($o_user_id);
        $obj->adel();
        value::set_game_user_value('in_ctm', 'ctm_shouye', $o_user_id);
        value::set_game_user_value('is_online', '0', $o_user_id);
        //删除用户CMD
        cmd::delallcmd($o_user_id);
        //战斗系统
        //处理玩家战斗相关
        if (value::get_game_user_value('is_pk', $o_user_id)) {
            //玩家退出战斗
            user::exit_pk($o_user_id);
        }
        value::set_user_value('is_pk_settlement_doing', 0, $o_user_id);
        //竞技系统
        //处理竞技相关
        //退出战斗
        value::set_user_value('pkjjid', 0, $o_user_id);
        value::set_user_value('pkjjzt', 0, $o_user_id);
        //组队系统
        //退出组队
        if (value::get_game_user_value('team_id', $o_user_id)) {
            team::exit_team($o_user_id);
            c_add_xiaoxi('你退出了小队!', 0, $o_user_id, $o_user_id);
        }
    }
}

//安全引擎函数
//安全引擎开启
function c_aqyq_start()
{
    $uid = uid();
    $cmd = $GLOBALS['cmd'];
    $aq_fenghao_time = value::get_user_value('aq.fenghao.time', $uid, false);
    $now_time = date("Y-m-d H:i:s");
    if ($aq_fenghao_time > $now_time) {
        echo "你被封号至" . $aq_fenghao_time . ",请勿使用脚本与Bug!";
        br();
        $aq_fenghao_cishu = value::get_user_value('aq.fenghao.cishu', $uid, false);
        if ($aq_fenghao_cishu) {
            echo "<a href='localhost'>返回首页</a>";
            $GLOBALS['mysqli']->close();
            return false;
        }
    }
    if ($cmd == 'aqyq') {
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'com.fax.zdllq') {
            $aq_fenghao_cishu = value::add_user_value('aq.fenghao.cishu', 1, $uid);
            if ($aq_fenghao_cishu == 1) {
                value::set_user_value('aq.fenghao.time', date('Y-m-d H:i:s', strtotime('+30 minute')), $uid);
            }
            if ($aq_fenghao_cishu == 2) {
                value::set_user_value('aq.fenghao.time', date('Y-m-d H:i:s', strtotime('+1 day')), $uid);
            }
            if ($aq_fenghao_cishu >= 3) {
                value::set_user_value('aq.fenghao.time', date('Y-m-d H:i:s', strtotime('+1 week')), $uid);
            }
        }
        value::set_user_value('aq.yanzheng.time', date('Y-m-d H:i:s', strtotime('+5 minute')), $uid);
        $GLOBALS['mysqli']->close();
        return false;
    }
    return true;
}

//安全引擎检测
function c_aqyq_jiance()
{
    $uid = uid();
    $now_time = date("Y-m-d H:i:s");
    if (value::get_user_value('aq.yanzheng.time', $uid, false) < $now_time && value::get_user_value('aq.fenghao.time', $uid, false) < $now_time) {
        c_is_zdllq();
    }
}

//是否自动浏览器
function c_is_zdllq()
{
    echo <<<yzform
<script>
function load()
{
var xmlhttp;
if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.open("GET","game.php?cmd=aqyq",true);
xmlhttp.send();
}
window.onload=load;
</script>
yzform;
}


//是否黑名单
function c_is_black_list()
{
    $black_list_array = config::getConfigByName("black_list");
    $uid = uid();
    foreach ($black_list_array as $id) {
        if ($id == $uid) {
            return true;
        }
    }
    return false;
}

//安全引擎函数
//检查字符函数
function c_check_str($str, $mode = 0, $max_length = 0)
{
    $ok = true;
    //敏感词过滤
    $black_word_arr = config::getConfigByName("black_word");
    $count = count($black_word_arr);
    for ($i = 0; $i < $count; $i++) {
        if (strstr($str, $black_word_arr[$i])) {
            $ok = false;
        }
    }
    //文本长度限制
    if ($max_length && mb_strlen($str, 'UTF8') > $max_length) {
        $ok = false;
    }
    //mode 1 取名 无法与NPC/宠物同名
    if ($mode == 1 && $ok) {
        //特殊名称
        if ($str == "GM") {
            $ok = false;
        }
        //NPC名称
        $sql = "SELECT `name` FROM `npc` WHERE 1";
        $result = sql($sql);
        while (list($npc_name) = $result->fetch_row()) {
            if ($str == $npc_name) {
                $ok = false;
            }
        }
        //宠物名称
        $sql = "SELECT `name` FROM `pet` WHERE 1";
        $result = sql($sql);
        while (list($pet_name) = $result->fetch_row()) {
            if ($str == $pet_name) {
                $ok = false;
            }
        }
    }
    return $ok;
}

//系统广播函数
function c_add_guangbo($chat, $uid = 0, $guangbo_mode = 0)
{
    value::insert('game_chat', "`id`, `mode`,`guangbo_mode`, `area_id`, `uid`, `oid`, `chats`, `time`, `is_look`", "NULL, '6','{$guangbo_mode}', '0', '" . $uid . "', '0', '" . $chat . "', '" . date('Y-m-d H:i:s') . "', '0'");
}

//系统消息函数
function c_add_xiaoxi($chat, $mode = 0, $uid = 0, $oid = 0, $map_id = 0, $guangbo_mode = 0)
{
    value::insert('game_chat', "`id`, `mode`, `area_id`,`map_id`, `uid`, `oid`, `chats`, `time`, `is_look`,`guangbo_mode`", "NULL, '" . $mode . "', '0','{$map_id}', '" . $uid . "', '" . $oid . "', '" . $chat . "', '" . date('Y-m-d H:i:s') . "', '0','" . $guangbo_mode . "'");
}

//删除消息函数
function c_del_xiaoxi($chat_id)
{
    $l = "DELETE FROM game_chat WHERE id={$chat_id} LIMIT 1";
    sql($l);
}

//求婚请求函数
function c_add_qhqq($oid, $step, $chat_id = 0)
{
    $userid = uid();
    $oname = value::get_game_user_value('name', $oid);
    $uname = value::get_game_user_value('name');
    if ($step == 1) {
        $in_map_id = value::get_game_user_value('in_map_id');
        if (value::get_user_value('bl.id', $oid)) {
            echo "很抱歉,{$oname}已经有婚约在身了!";
        } else if (value::get_user_value('bl.id')) {
            echo "很抱歉,你已经有婚约在身了!";
        } else if ($in_map_id != value::get_game_user_value('in_map_id', $oid) || !value::get_map_value($in_map_id, 'is_cunzhangjia')) {
            echo "很抱歉,你们必须在相同的求婚地点才能结婚!";
        } else if (!value::get_game_user_value('is_online', $oid)) {
            echo "很抱歉,对方必须在线才能结婚!";
        } else {
            echo '恭喜你,你同意了' . $oname . '的求婚。';
            c_add_xiaoxi('恭喜你,' . $uname . '同意了你的求婚!', 0, uid(), $oid);
            sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
            value::set_user_value('bl.id', $oid, $userid);
            value::set_user_value('bl.id', $userid, $oid);
            value::set_user_value('bl.mode', '1', $userid);
            value::set_user_value('bl.mode', '1', $oid);
            value::set_user_value('bl.qinmidu', '0', $userid);
            value::set_user_value('bl.qinmidu', '0', $oid);
            $now_time = time();
            //设置伴侣亲密时间
            value::set_user_value('last_qinmi_time', $now_time, $userid);
            value::set_user_value('last_qinmi_time', $now_time, $oid);
        }
    } else if ($step == 2) {
        echo '你拒绝了' . $oname . "的求婚。";
        c_add_xiaoxi('很抱歉,' . $uname . '拒绝了你的求婚!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    }
}

//组队请求函数
function c_add_zdqq($oid, $step = 0, $chat_id = 0)
{
    $oname = value::get_game_user_value('name', $oid);
    $uid = uid();
    $uname = value::get_game_user_value('name');
    if ($step == 1) {
        if (value::get_game_user_value('is_online', $oid)) {
            $team_id = team::get_user_team($oid);
            if (team::get_team_user_count($team_id) < 5) {
                $o_in_map_id = value::get_game_user_value('in_map_id', $oid);
                if (!value::get_map_value($o_in_map_id, 'is_danrenfuben')) {
                    if (!team::get_user_team($uid)) {
                        $u_in_map_id = value::get_game_user_value('in_map_id', $uid);
                        if (!value::get_map_value($u_in_map_id, 'is_danrenfuben')) {
                            if (!$team_id) {
                                $team_id = team::new_team($oid);
                                value::set_game_user_value('team_id', $team_id, $oid);
                            }
                            value::set_game_user_value('team_id', $team_id, $uid);
                            echo "你加入了{$oname}的小队。";
                            c_add_xiaoxi($uname . '加入了小队!', 0, $uid, $oid);
                            c_add_xiaoxi("大家好,我是新来的!", 18, $uid, $team_id);
                        } else {
                            echo "你正在单人副本中,无法加入。";
                        }
                    } else {
                        echo "你已在小队中,无法加入。";
                    }
                } else {
                    echo "对方正在单人副本中,无法加入。";
                }
            } else {
                echo "队伍已满,无法加入。";
            }
        } else {
            echo "对方离线,无法加入。";
        }
    } else if ($step == 2) {
        echo '你拒绝了' . $oname . "的组队邀请。";
        c_add_xiaoxi($uname . '拒绝了你的组队邀请!', 0, $uid, $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else if ($step == 3) {
        if (value::get_game_user_value('is_online', $oid)) {
            if (!team::get_user_team($oid)) {
                if (!value::get_game_user_value('is_pk', $oid)) {
                    $team_id = team::get_user_team($uid);
                    if (team::get_team_user_count($team_id) < 5) {
                        $o_in_map_id = value::get_game_user_value('in_map_id', $oid);
                        if (!value::get_map_value($o_in_map_id, 'is_danrenfuben')) {
                            if (team::get_user_team($uid)) {
                                value::set_game_user_value('team_id', $team_id, $oid);
                                echo "{$oname}加入了小队。";
                                c_add_xiaoxi("你加入了{$uname}的小队。", 0, $uid, $oid);
                                c_add_xiaoxi("大家好,我是新来的!", 18, $oid, $team_id);
                            } else {
                                echo "你不在小队中,无法接受。";
                            }
                        } else {
                            echo "对方正在单人副本中,无法加入。";
                        }
                    } else {
                        echo "队伍已满,无法加入。";
                    }
                } else {
                    echo "对方正在战斗中,无法加入。";
                }
            } else {
                echo "对方正在小队中,无法加入。";
            }
        } else {
            echo "对方离线,无法接受。";
        }
    } else if ($step == 4) {
        echo '你拒绝了' . $oname . "的组队请求。";
        c_add_xiaoxi($uname . '拒绝了你的组队请求!', 0, $uid, $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    }
}

//拜师请求函数
function c_add_bsqq($oid, $step = 0, $chat_id = 0)
{
    $oname = value::get_game_user_value('name', $oid);
    $uname = value::get_game_user_value('name');
    if ($step == 1) {
        if (!value::get_game_user_value('shifu.id', $oid)) {
            if (!value::get_game_user_value('is_chushi', $oid)) {
                if (user::get_tudi_count() < 20) {
                    echo '恭喜,你把' . $oname . '收入门下,悉心调教。';
                    c_add_xiaoxi('恭喜,' . $uname . '同意了你的拜师请求!', 0, uid(), $oid);
                    sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
                    value::set_game_user_value('shifu.id', uid(), $oid);
                    value::set_game_user_value('baishi.time', date("Y-m-d H:i:s"), $oid);
                } else {
                    echo "你已经有20个徒弟了!";
                }
            } else {
                echo "对方已经学成出师了!";
            }
        } else {
            echo "对方已经另拜名师了!";
        }
    } else if ($step == 2) {
        echo '你拒绝了' . $oname . "的拜师请求。";
        c_add_xiaoxi($uname . '拒绝了你的拜师请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else {
        c_add_xiaoxi($uname . '跪在地上,三叩九拜想要拜你为师!', 15, uid(), $oid);
    }
}

//好友请求函数
function c_add_hyqq($oid, $step = 0, $chat_id = 0)
{
    $oname = value::get_game_user_value('name', $oid);
    $uname = value::get_game_user_value('name');
    if ($step == 1) {
        echo '成功添加' . $oname . '为好友。';
        c_add_xiaoxi($uname . '同意了你的好友请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
        value::insert_user_value('haoyou', $oid, uid());
        value::insert_user_value('haoyou', uid(), $oid);
    } else if ($step == 2) {
        echo '你拒绝了' . $oname . "的好友请求。";
        c_add_xiaoxi($uname . '拒绝了你的好友请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else {
        c_add_xiaoxi($uname . '想要添加你为好友。', 7, uid(), $oid);
    }
}

//金币交易道具请求函数
function c_add_jydjqq($oid, $step = 0, $item_id = 0, $danjia = 0, $chat_id = 0)
{
    $uname = value::get_game_user_value('name');
    $oname = value::get_game_user_value('name', $oid);
    if ($step == 1) {
        prop::show_prop($item_id, 7);
        cmd::addcmd('c_add_jydjqq,' . $oid . ',2,' . $item_id . ',' . $danjia . ',' . $chat_id, '接受交易');
        br();
        cmd::addcmd('c_add_jydjqq,' . $oid . ',3,' . $item_id . ',' . $danjia . ',' . $chat_id, '拒绝交易');
        br();
    } else if ($step == 2) {
        $is_online = value::get_game_user_value('is_online', $oid);
        if (!$is_online) {
            echo '玩家已离线,无法交易。';
            br();
        } else {
            $ok = true;
            if (!prop::user_have_prop($item_id, $oid, 1)) {
                c_add_xiaoxi('你与' . $uname . '的交易失败,你身上没有足够的物品!', 0, uid(), $oid);
                echo '对方没有这件物品了。';
                br();
                $ok = false;
            }
            if ($ok) {
                if ($danjia > value::get_user_value('money')) {
                    c_add_xiaoxi('你与' . $uname . '的交易失败,对方身上没有足够的金币!', 0, uid(), $oid);
                    echo '你身上没有足够的金币。';
                    br();
                    $ok = false;
                }
            }
            if ($ok) {
                if (!prop::user_get_prop($item_id, 0, 1)) {
                    c_add_xiaoxi('你与' . $uname . '的交易失败,对方的背包已满!', 0, uid(), $oid);
                    $ok = false;
                }
            }
            if ($ok) {
                item::add_money(-1 * $danjia);
                item::add_money($danjia, $oid, false);
                c_add_xiaoxi('你与' . $uname . '的交易成功,你获得了' . $danjia . '个金币!', 0, uid(), $oid);
            }
        }
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else if ($step == 3) {
        echo '你拒绝了' . $oname . "的交易请求。";
        br();
        c_add_xiaoxi($uname . '拒绝了你的交易请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else {
        $zzid = value::get_game_prop_value($item_id, 'prop_id');
        $liangci = value::get_prop_value($zzid, 'liangci');
        $leixing = value::get_prop_value($zzid, 'leixing');
        $name = value::get_game_prop_value($item_id, 'name');
        if ($leixing == 1) {
            $exp = value::get_game_prop_value($item_id, 'exp');
            $name = "含有{$exp}点经验的仙丹";
        }
        c_add_xiaoxi($uname . ',' . $item_id . ',' . $danjia . ',' . $liangci . ',' . $name, 13, uid(), $oid);
    }
    cmd::set_return_game_br(false);
}

//元宝交易道具请求函数
function c_add_jydjqq1($oid, $step = 0, $item_id = 0, $danjia = 0, $chat_id = 0)
{
    $uname = value::get_game_user_value('name');
    $oname = value::get_game_user_value('name', $oid);
    if ($step == 1) {
        prop::show_prop($item_id, 7);
        cmd::addcmd('c_add_jydjqq1,' . $oid . ',2,' . $item_id . ',' . $danjia . ',' . $chat_id, '接受交易');
        br();
        cmd::addcmd('c_add_jydjqq1,' . $oid . ',3,' . $item_id . ',' . $danjia . ',' . $chat_id, '拒绝交易');
        br();
    } else if ($step == 2) {
        $is_online = value::get_game_user_value('is_online', $oid);
        if (!$is_online) {
            echo '玩家已离线,无法交易！';
            br();
        } else {
            $ok = true;
            if (!prop::user_have_prop($item_id, $oid, 1)) {
                c_add_xiaoxi('你与' . $uname . '的交易失败,你身上没有足够的物品!', 0, uid(), $oid);
                echo '对方没有这件物品了！';
                br();
                $ok = false;
            }
            if ($ok) {
                $lingshi = item::get_lingshi();
                if ($danjia > $lingshi) {
                    c_add_xiaoxi('你与' . $uname . '的交易失败,对方身上没有足够的元宝!', 0, uid(), $oid);
                    echo '你身上没有足够的元宝！';
                    br();
                    $ok = false;
                }
            }
            if ($ok) {
                if (!prop::user_get_prop($item_id, 0, 1)) {
                    c_add_xiaoxi('你与' . $uname . '的交易失败,对方的背包已满!', 0, uid(), $oid);
                    $ok = false;
                }
            }
            if ($ok) {
                $danjia1 = $danjia - ($danjia / 10);
                item::add_item(1, $danjia1, false, $oid, true);
                item::add_item(1, -1 * $danjia);
                c_add_xiaoxi('你与' . $uname . '的交易成功,你获得了' . $danjia1 . '个元宝!', 0, uid(), $oid);
            }
        }
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else if ($step == 3) {
        echo '你拒绝了' . $oname . "的交易请求。";
        br();
        c_add_xiaoxi($uname . '拒绝了你的交易请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else {
        $zzid = value::get_game_prop_value($item_id, 'prop_id');
        $liangci = value::get_prop_value($zzid, 'liangci');
        $leixing = value::get_prop_value($zzid, 'leixing');
        $name = value::get_game_prop_value($item_id, 'name');
        if ($leixing == 1) {
            $exp = value::get_game_prop_value($item_id, 'exp');
            $name = "含有{$exp}点经验的仙丹";
        }
        c_add_xiaoxi($uname . ',' . $item_id . ',' . $danjia . ',' . $liangci . ',' . $name, 33, uid(), $oid);
    }
    cmd::set_return_game_br(false);
}

//金币交易物品请求函数
function c_add_jywpqq($oid, $step = 0, $item_id = 0, $danjia = 0, $count = 0, $chat_id = 0)
{
    $oname = value::get_game_user_value('name', $oid);
    $uname = value::get_game_user_value('name');
    if ($step == 1) {
        $is_online = value::get_game_user_value('is_online', $oid);
        if (!$is_online) {
            echo '玩家已离线,无法交易！';
            br();
        } else {
            $ok = true;
            $zongjia = $danjia * $count;
            $ocount = item::get_item($item_id, $oid);
            if ($zongjia > value::get_user_value('money')) {
                c_add_xiaoxi('你与' . $uname . '的交易失败,对方身上没有足够的金币!', 0, uid(), $oid);
                echo '你身上没有足够的金币！';
                br();
                $ok = false;
            }
            if ($ok) {
                if ($ocount < $count) {
                    c_add_xiaoxi('你与' . $uname . '的交易失败,你身上没有足够的物品!', 0, uid(), $oid);
                    echo '对方没有足够的物品了！';
                    br();
                    $ok = false;
                }
            }
            if ($ok) {
                if (user::get_rongliang() <= user::get_fuzhong()) {
                    echo '你的背包已满！';
                    br();
                    $ok = false;
                }
            }
            if ($ok) {
                item::add_money($zongjia, $oid, false);
                item::lose_item($item_id, $count, false, $oid);
                item::add_money(-1 * $zongjia);
                item::add_item($item_id, $count, true, 0, true);
                c_add_xiaoxi('你与' . $uname . '的交易成功,你获得了' . $zongjia . '个金币!', 0, uid(), $oid);
            }
        }
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else if ($step == 2) {
        echo '你拒绝了' . $oname . "的交易请求！";
        br();
        c_add_xiaoxi($uname . '拒绝了你的交易请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else {
        $name = value::getvalue('item', 'name', 'id', $item_id);
        $liangci = value::getvalue('item', 'liangci', 'id', $item_id);
        c_add_xiaoxi($uname . ',' . $item_id . ',' . $danjia . ',' . $count . ',' . $liangci . ',' . $name, 10, uid(), $oid);
    }
    cmd::set_return_game_br(false);
}


//元宝交易物品请求函数
function c_add_jywpqq1($oid, $step = 0, $item_id = 0, $danjia = 0, $count = 0, $chat_id = 0)
{
    $oname = value::get_game_user_value('name', $oid);
    $uname = value::get_game_user_value('name');
    if ($step == 1) {
        $is_online = value::get_game_user_value('is_online', $oid);
        if (!$is_online) {
            echo '玩家已离线,无法交易！';
            br();
        } else {
            $ok = true;
            $zongjia = $danjia * $count;
            $ocount = item::get_item($item_id, $oid);
            $lingshi = item::get_lingshi();
            if ($zongjia > $lingshi) {
                c_add_xiaoxi('你与' . $uname . '的交易失败,对方身上没有足够的元宝!', 0, uid(), $oid);
                echo '你身上没有足够的元宝！';
                br();
                $ok = false;
            }
            if ($ok) {
                if ($ocount < $count) {
                    c_add_xiaoxi('你与' . $uname . '的交易失败,你身上没有足够的物品!', 0, uid(), $oid);
                    echo '对方没有足够的物品了！';
                    br();
                    $ok = false;
                }
            }
            if ($ok) {
                if (user::get_rongliang() <= user::get_fuzhong()) {
                    echo '你的背包已满！';
                    br();
                    $ok = false;
                }
            }
            if ($ok) {
                $zongjia1 = $zongjia - ($zongjia / 10);
                item::add_item(1, $zongjia1, false, $oid, true);
                item::lose_item($item_id, $count, false, $oid);
                item::add_item(1, -1 * $zongjia);
                item::add_item($item_id, $count, true, 0, true);
                c_add_xiaoxi('你与' . $uname . '的交易成功,你获得了' . $zongjia1 . '个元宝!', 0, uid(), $oid);
            }
        }
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else if ($step == 2) {
        echo '你拒绝了' . $oname . "的交易请求！";
        br();
        c_add_xiaoxi($uname . '拒绝了你的交易请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else {
        $name = value::getvalue('item', 'name', 'id', $item_id);
        $liangci = value::getvalue('item', 'liangci', 'id', $item_id);
        c_add_xiaoxi($uname . ',' . $item_id . ',' . $danjia . ',' . $count . ',' . $liangci . ',' . $name, 31, uid(), $oid);
    }
    cmd::set_return_game_br(false);
}

//金币交易宠物请求函数
function c_add_jycwqq($oid, $step = 0, $pet_id = 0, $money = 0, $chat_id = 0)
{
    $oname = value::get_game_user_value('name', $oid);
    $uname = value::get_game_user_value('name');
    if ($step == 1) {
        $is_online = value::get_game_user_value('is_online', $oid);
        if (!$is_online) {
            echo '玩家已离线,无法交易！';
        } else {
            $ok = true;
            if ($money > value::get_user_value('money')) {
                c_add_xiaoxi('你与' . $uname . '的交易失败,对方身上没有足够的金币!', 0, uid(), $oid);
                echo '你身上没有足够的金币！';
                br();
                $ok = false;
            }
            $name = value::get_pet_value($pet_id, 'name');
            $lvl = value::get_pet_value($pet_id, 'lvl');
            $zhuansheng = value::get_pet_value($pet_id, 'zhuansheng');
            $master_id = value::get_pet_value($pet_id, 'master_id');
            $master_mode = value::get_pet_value($pet_id, 'master_mode');
            $is_pk = value::get_game_user_value('is_pk', $oid);
            if ($lvl > 4 || $zhuansheng > 0 || $master_id != $oid || $master_mode != 1 || $is_pk == 1) {
                c_add_xiaoxi('你与' . $uname . '交易一只' . $name . '失败!<br>(宠物需未转生且等级<5级且战斗时无法交易)', 0, uid(), $oid);
                echo '对方现在无法交易宠物！';
                br();
                $ok = false;
            }
            $olvl = value::get_game_user_value('lvl', $oid) + 5;
            $ulvl = value::get_game_user_value('lvl') + 5;
            if ($olvl >= $lvl && $ulvl >= $lvl) {
                c_add_xiaoxi('你与' . $uname . '交易一只' . $name . '失败!<br>(宠物等级大于玩家5级或者大于你5级时无法交易)', 0, uid(), $oid);
                echo '对方现在无法交易宠物！';
                br();
                $ok = false;
            }
            if ($ok) {
                if (pet::user_lose_pet($oid, $pet_id, 1, false)) {
                    $equip_liuwei_id = pet::get_prop_id($pet_id, 2);
                    $equip_baowu_id = pet::get_prop_id($pet_id, 3);
                    foreach ($equip_liuwei_id as $equip_id) {
                        prop::pet_lose_prop($equip_id, $pet_id, false);
                        prop::user_get_prop($equip_id, $oid, 1, false, true);
                    }
                    if ($equip_baowu_id) {
                        prop::pet_lose_prop($equip_baowu_id, $pet_id, false);
                        prop::user_get_prop($equip_baowu_id, $oid, 1, false, true);
                    }
                    item::add_money(-1 * $money);
                    item::add_money($money, $oid, false);
                    c_add_xiaoxi('你与' . $uname . '的交易成功,你获得了' . $money . '个金币!', 0, uid(), $oid);
                    if ($equip_liuwei_id || $equip_baowu_id) {
                        c_add_xiaoxi($name . "的装备放进背包了！", 0, uid(), $oid);
                    }
                    c_add_xiaoxi('你失去了1只' . $name . "。", 0, uid(), $oid);
                    if (pet::user_get_pet(uid(), $pet_id, 1, false)) {
                        echo '你获得了1只' . $name . '！';
                        br();
                    } else {
                        pet::user_get_pet(uid(), $pet_id, 2);
                        echo $name . '存入了仓库！';
                        br();
                    }
                } else {
                    echo '对方身上没有/仅剩这只宠物了！';
                    br();
                    c_add_xiaoxi('你与' . $uname . '交易一只' . $name . '失败!<br>(你身上没有/仅剩这只宠物了)', 0, uid(), $oid);
                }
            }
        }
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else if ($step == 2) {
        echo '你拒绝了' . $oname . "的交易请求！";
        br();
        c_add_xiaoxi($uname . '拒绝了你的交易请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else {
        c_add_xiaoxi($uname . ',' . $pet_id . ',' . $money, 11, uid(), $oid);
    }
    cmd::set_return_game_br(false);
}

//元宝交易宠物请求函数
function c_add_jycwqq1($oid, $step = 0, $pet_id = 0, $money = 0, $chat_id = 0)
{
    $oname = value::get_game_user_value('name', $oid);
    $uname = value::get_game_user_value('name');
    if ($step == 1) {
        $is_online = value::get_game_user_value('is_online', $oid);
        if (!$is_online) {
            echo '玩家已离线,无法交易！';
        } else {
            $ok = true;
            $lingshi = item::get_lingshi();
            if ($money > $lingshi) {
                c_add_xiaoxi('你与' . $uname . '的交易失败,对方身上没有足够的元宝!', 0, uid(), $oid);
                echo '你身上没有足够的元宝！';
                br();
                $ok = false;
            }
            $name = value::get_pet_value($pet_id, 'name');
            $lvl = value::get_pet_value($pet_id, 'lvl');
            $zhuansheng = value::get_pet_value($pet_id, 'zhuansheng');
            $master_id = value::get_pet_value($pet_id, 'master_id');
            $master_mode = value::get_pet_value($pet_id, 'master_mode');
            $is_pk = value::get_game_user_value('is_pk', $oid);
            if ($lvl > 4 || $zhuansheng > 0 || $master_id != $oid || $master_mode != 1 || $is_pk == 1) {
                c_add_xiaoxi('你与' . $uname . '交易一只' . $name . '失败!<br>(宠物需未转生且等级<5级且战斗时无法交易)', 0, uid(), $oid);
                echo '对方现在无法交易宠物。';
                br();
                $ok = false;
            }
            $olvl = value::get_game_user_value('lvl', $oid) + 5;
            $ulvl = value::get_game_user_value('lvl') + 5;
            if ($olvl >= $lvl && $ulvl >= $lvl) {
                c_add_xiaoxi('你与' . $uname . '交易一只' . $name . '失败!<br>(宠物等级大于玩家5级或者大于你5级时无法交易)', 0, uid(), $oid);
                echo '对方现在无法交易宠物！';
                br();
                $ok = false;
            }
            if ($ok) {
                if (pet::user_lose_pet($oid, $pet_id, 1, false)) {
                    $equip_liuwei_id = pet::get_prop_id($pet_id, 2);
                    $equip_baowu_id = pet::get_prop_id($pet_id, 3);
                    foreach ($equip_liuwei_id as $equip_id) {
                        prop::pet_lose_prop($equip_id, $pet_id, false);
                        prop::user_get_prop($equip_id, $oid, 1, false, true);
                    }
                    if ($equip_baowu_id) {
                        prop::pet_lose_prop($equip_baowu_id, $pet_id, false);
                        prop::user_get_prop($equip_baowu_id, $oid, 1, false, true);
                    }
                    $money1 = $money - ($money / 10);
                    item::add_item(1, $money1, false, $oid, true);
                    item::add_item(1, -1 * $money);
                    c_add_xiaoxi('你与' . $uname . '的交易成功,你获得了' . $money1 . '个元宝!', 0, uid(), $oid);
                    if ($equip_liuwei_id || $equip_baowu_id) {
                        c_add_xiaoxi($name . "的装备放进背包了！", 0, uid(), $oid);
                    }
                    c_add_xiaoxi('你失去了1只' . $name . "。", 0, uid(), $oid);
                    if (pet::user_get_pet(uid(), $pet_id, 1, false)) {
                        echo '你获得了1只' . $name . '！';
                        br();
                    } else {
                        pet::user_get_pet(uid(), $pet_id, 2);
                        echo $name . '存入了仓库！';
                        br();
                    }
                } else {
                    echo '对方身上没有/仅剩这只宠物了！';
                    br();
                    c_add_xiaoxi('你与' . $uname . '交易一只' . $name . '失败!<br>(你身上没有/仅剩这只宠物了)', 0, uid(), $oid);
                }
            }
        }
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else if ($step == 2) {
        echo '你拒绝了' . $oname . "的交易请求！";
        br();
        c_add_xiaoxi($uname . '拒绝了你的交易请求!', 0, uid(), $oid);
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
    } else {
        c_add_xiaoxi($uname . ',' . $pet_id . ',' . $money, 32, uid(), $oid);
    }
    cmd::set_return_game_br(false);
}

//系统反馈函数
function c_fankui($mode = 0, $chat_id = 0)
{
    if (!$mode) {
        $fk_count = 0;
        $sql = "SELECT `uid`,`chats`,`time`,`id` FROM `game_chat` WHERE `mode`=9 ORDER BY `time` ASC";
        $result = sql($sql);
        while (list($oid, $chat, $time, $chat_id) = $result->fetch_row()) {
            if ($fk_count) {
                br();
            }
            $oname = value::get_game_user_value('name', $oid);
            echo "{$time} <span style='color:#0000ff'>{$oname}说:</span><span style='color:#ff0000'>{$chat}。</span> ";
            cmd::addcmd('e53,' . $oid . ',1', '回复');
            echo " ";
            cmd::addcmd('c_fankui,3,' . $chat_id, '采纳');
            echo " ";
            cmd::addcmd('c_fankui,1,' . $chat_id, '删除');
            $fk_count++;
        }
        if (!$fk_count) {
            echo "目前没有任何人反馈。";
        }
    } else if ($mode == 1) {
        echo "你确认要删除这条反馈?";
        br();
        cmd::addcmd('c_fankui,2,' . $chat_id, '确认删除');
        br();
        cmd::addcmd('c_fankui', '返回上级');
    } else if ($mode == 2) {
        sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
        echo "<span style='color: #ff0000'>你成功删除了这条反馈。</span>";
        br();
        c_fankui();
    } else if ($mode == 3) {
        $url = cmd::addcmd2url("c_fankui,4,{$chat_id}");
        echo <<<html
你要奖励多少元宝?
<form action="$url" method="post">
<input type="text" value="500" name="lingshi">
<br>
<input type="text" value="N" name="ok">
<br>
<input type="submit" value="确认">
</form>
html;
        cmd::addcmd('c_fankui', '返回上级');
    } else if ($mode == 4) {
        $lingshi = (int)post::get("lingshi");
        $ok = post::get("ok");
        if ($lingshi > 0 && $ok == "Y") {
            $sql = "SELECT `uid`,`id` FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1";
            $result = sql($sql);
            list($oid, $chat_id) = $result->fetch_row();
            c_add_xiaoxi("你的反馈被采纳并获得了奖励的{$lingshi}颗元宝!", 0, 0, $oid);
            item::add_lingshi($lingshi, false, $oid);
            sql("DELETE FROM `game_chat` WHERE `id`={$chat_id} LIMIT 1");
            echo "采纳并奖励{$lingshi}颗元宝成功!";
        } else {
            echo "输入有误!";
        }
        br();
        c_fankui();
    }
}

//取地图属性函数
function c_get_map_value($valuename)
{
    $r = value::getvalue('map', $valuename, 'id', value::get_game_user_value('in_map_id'));
    if ($r > 1) {
        return $r;
    }
    if ($r == 1) {
        return "<b style='color:#ff0000'>是</b>";
    } else {
        return "否";
    }
}

//字符串替换函数
function c_str_replace_limit($search, $replace, $subject, $limit = -1)
{
    if (is_array($search)) {
        foreach ($search as $k => $v) {
            $search[$k] = '`' . preg_quote($search[$k], '`') . '`';
        }
    } else {
        $search = '`' . preg_quote($search, '`') . '`';
    }
    return preg_replace($search, $replace, $subject, $limit);
}

//地图 刷新宠物事件
function c_map_flush_pet($map_id = 0)
{
    $user_id = uid();
    //获取地图属性
    $sql = "SELECT `name`,`lvl`,`yg_sx`, `yaoguai`,`area_id` FROM `map` WHERE `id`={$map_id} LIMIT 1";
    $result = sql($sql);
    list($m_name, $m_lvl, $yg_sx, $m_yaoguai, $m_area_id) = $result->fetch_row();
    //地图宠物数量
    $map_pet_count = $yg_sx > 0 ? 1 : 5;
    if ($m_area_id == 91) {
        $map_pet_count = 1;
    }
    //分割宠物类型
    $pet_arr = explode(',', $m_yaoguai);
    $yaoguai_count = count($pet_arr);
    //生成宠物
    $sql = "SELECT COUNT(*) FROM `game_pet` WHERE `map_id` = {$map_id} AND `master_id`=0 AND `npc_id`=0 AND `master_mode`!=8";
    $result = sql($sql);
    list($pcount) = $result->fetch_row();
    //地图是否有生物
    if (!$pcount) {
        //地图首次刷新宠物
        for ($i = $pcount; $i < $map_pet_count; $i++) {
            pet::new_pet($pet_arr[mt_rand(0, $yaoguai_count - 1)], $m_lvl + mt_rand(0, 2), $map_id);
        }
    }
}


//社区充值
function c_recharge($money)
{
    $community_arr = config::getConfigByName("community");
    //来源平台 用户 时间
    $source = user::get_community();
    $user = user::get_community_id();
    $time = time();
    //平台密钥
    $community_game_key = $community_arr[$source]["key"];
    //游戏编号
    $community_game_num = $community_arr[$source]["game"];
    //社区名称
    $community_name = $community_arr[$source]["name"];
    //社区网址
    $community_url = $community_arr[$source]["url"];
    //验证hash
    $community_user_hash = eval("return " . $community_arr[$source]["hash"] . ";");
    //充值链接
    $community_recharge_url = eval("return " . $community_arr[$source]["recharge_url"] . ";");
    if ($community_recharge_url) {
        $rt_url = "http://" . $_SERVER['SERVER_NAME'] . "/" . gstring::get("game_dir") . "/" . cmd::addcmd("e31", "", false, true);
        $rt_url = base64_encode($rt_url);
        $community_recharge_url .= "&retUrl={$rt_url}";
        echo <<<recharge
<a href="$community_recharge_url">前往{$community_name}充值</a> 
recharge;
    } else {
        echo "<b><span style='color:red'>请您记住绿色传奇官网仙盟会手机浏览器可直接游戏,官方qq群:qq-738323424（新手指引及详细攻略请参考官网及官群）</span></b>";
    }
}

//获取Q群号码
function c_get_qq_group_num()
{
    $n = gstring::get("qq_group_num");

    return $n[user::get_community()] ? $n[user::get_community()] : $n['*'];

}

//在线更新
function c_update()
{
    $now_time = time();
    $game_area = $_GET['g'] ? "&g={$_GET['g']}" : "";
    game_gg();
    br();
    echo "游戏在线更新中...";
    br();
    echo "<a href='game.php?cmd={$_GET['cmd']}&t={$now_time}{$game_area}'>耐心等待</a>";
}

//整数 取整 负数取零
function c_uint($v)
{
    if ($v < 0) {
        return 0;
    } else {
        return intval($v);
    }

}

//需要登录
function c_need_login()
{
    if ($GLOBALS['uid'] || $_COOKIE['community_url']) {
        $community_url = $_COOKIE['community_url'] ? $_COOKIE['community_url'] : user::get_community_url($GLOBALS['uid']);
        echo "<span>你好,游戏尚未登录!</span><br><a href=\"https://qm.qq.com/q/RF2pLliagq\">现在登录绿色传奇</a>";
    } else {
        echo "<span>你好,游戏尚未登录!</span><br><a href=\"https://qm.qq.com/q/RF2pLliagq\">现在登录绿色传奇</a>";
    }
}

//现行毫秒时间戳
function c_msectime()
{
    list($tmp1, $tmp2) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
}

require_once "design.php";
//系统函数

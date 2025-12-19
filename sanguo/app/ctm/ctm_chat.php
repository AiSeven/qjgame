<?php
$min_id = 0;
$mode = value::get_user_value('chat_mode');
$user_id = uid();
if (!$mode || $mode == -1) {
    e36();
    return;
}
echo "[信息频道]";
cmd::addcmd('e36,' . $mode, '刷新');
br();
if ($mode != 1) {
    cmd::addcmd('e36,1', '公聊');
}
if ($mode == 1) {
    echo "公聊";
}
echo "  ";
if ($mode != 7) {
    cmd::addcmd('e36,7', '系统');
}
if ($mode == 7) {
    echo "系统";
}
echo "  ";
if ($mode != 2) {
    cmd::addcmd('e36,2', '私聊');
}
if ($mode == 2) {
    echo "私聊";
}
echo "  ";
if ($mode != 8) {
    cmd::addcmd('e36,8', '广播');
}
if ($mode == 8) {
    echo "广播";
}
echo "  ";
if ($mode != 5) {
    cmd::addcmd('e36,5', '组队');
}
if ($mode == 5) {
    echo "组队";
}
echo "  ";
if ($mode != 3) {
    cmd::addcmd('e36,3', '师门');
}
if ($mode == 3) {
    echo "师门";
}
echo "  ";
if ($mode != 6) {
    cmd::addcmd('e36,6', '帮派');
}
if ($mode == 6) {
    echo "帮派";
}
echo "  ";
if ($mode != 4) {
    cmd::addcmd('e36,4', '黑市');
}
if ($mode == 4) {
    echo "黑市";
}
br();
if ($mode == 7) {
    $sql = "SELECT `id`,`chats`,`guangbo_mode`,`uid` FROM `game_chat` WHERE `mode` = 6 ORDER BY `id` DESC LIMIT 0, 15 ";
    $result = sql($sql);
    while (list($chat_id, $chats, $guangbo_mode, $tid) = $result->fetch_row()) {
        echo "[系统]" . $chats;
        if ($guangbo_mode == 4) {
            echo " ";
            $name = value::get_game_prop_value($tid, 'name');
            cmd::addcmd('e10003,' . $tid . ',3', $name);
        }
        if ($guangbo_mode == 5) {
            echo " ";
            $name = value::get_pet_value($tid, 'name');
            cmd::addcmd('bx_e621,' . $tid . ',1', $name);
        }
        br();
        $chat_count++;
        if ($chat_count == 1) {
            $min_id = $chat_id;
            value::set_user_value("xt_count", $min_id, $user_id);
        }
        array_push($tmp_chat_id_arr, $chat_id);
    }
    if ($chat_count == 15) {
        $sql = "DELETE FROM `game_chat` WHERE `mode` = 6 AND `id`<" . $min_id;
        sql($sql);
    }
}
if ($mode == 8) {
    $cmd = cmd::addcmd('e380', '信息内容', false);
    echo "<form action='game.php?cmd=" . $cmd . "' method='post'>信息内容:<input type='text' id='chats' name='chats' maxlength='50'><input type='submit' 'name'='发送信息' value='发送信息'><!--<span class='emotion'></span>--></form>";
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 30 ORDER BY `id` DESC LIMIT 0, 20 ";
    $result = sql($sql);
    $chat_count = 0;
    $i = 0;
    while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
        if ($i) {
            br();
        }
        $i++;
        echo date("H:i", strtotime($time)) . " ";
        user::showVipLogo($oid, true);
        cmd::addcmd('e12,' . $oid . ',2', value::get_game_user_value('name', $oid));
        echo " 广播:<span style='color:red'>{$chats}</span>";
        $chat_count++;
        $min_id = $chat_id;
//记录地区聊天编号
        if ($chat_count == 1) {
            if ($min_id > value::get_user_value('dqlt_count')) {
                value::set_user_value('dqlt_count', $min_id);
            }
        }
    }
    if (!$chat_count) {
        echo "这里空荡荡的,还没有人广播。";
    }
//删除较早记录
    if ($chat_count == 20) {
        $sql = "DELETE FROM `game_chat` WHERE `mode`=30 AND `id`<" . $min_id;
        sql($sql);
    }
}
if ($mode == 1) {
    $user_id = uid();
    $area_id = value::getvalue('map', 'area_id', 'id', value::get_game_user_value('in_map_id'));
    $cmd = cmd::addcmd('e38', '信息内容', false);
    echo <<<setname
    插入表情：
<select name="money" id="money1" onchange="set_money(this);">
<option value="#62#">困</option>
<option value="#63#">哭</option>
<option value="#64#">额</option>
<option value="#65#">吓</option>
<option value="#66#">晕</option>
<option value="#67#">惊叹</option>
<option value="#68#">哼</option>
<option value="#69#">吐血</option>
<option value="#70#">大笑</option>
<option value="#71#">坏笑</option>
<option value="#72#">害羞</option>
<option value="#73#">色</option>
<option value="#74#">汗</option>
<option value="#75#">爆笑</option>
<option value="#76#">得意</option>
<option value="#77#">微笑</option>
</select>
<script >
function set_money(sl_obj) {
    var sl_index = sl_obj.selectedIndex;
    var sl_value = sl_obj.options[sl_index].value;
    var money=document.getElementById("money");
    money.value+=sl_value;
}
</script>
setname;
    echo "<form action='game.php?cmd=" . $cmd . "' method='post'>信息内容:<input type='text' id='money' name='money' maxlength='50'><input type='submit' 'name'='发送信息' value='发送信息'><!--<span class='emotion'></span>--></form>";
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 1 ORDER BY `id` DESC LIMIT 0, 20 ";
    $result = sql($sql);
    $chat_count = 0;
    $i = 0;
    while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
        if ($i) {
            br();
        }
        $i++;
        if (value::get_game_user_value('name', $oid)) {
            user::showVipLogo($oid, true);
            $using_nick_name = value::get_user_value('using_nick_name', $oid);
            if ($using_nick_name) {
                echo value::get_user_value('using_nick_name', $oid);
            }
            cmd::addcmd('e12,' . $oid . ',2', value::get_game_user_value('name', $oid));
            echo " 说:" . $chats;
            $chat_count++;
            $min_id = $chat_id;
        } else {
            $chat_count++;
            $min_id = $chat_id;
            $sql = "DELETE FROM `game_chat` WHERE `mode`=1 AND `id`=" . $min_id;
            sql($sql);
        }
        //记录地区聊天编号
        if ($chat_count == 1) {
            if ($min_id > value::get_user_value('dqlt_count')) {
                value::set_user_value('dqlt_count', $min_id);
            }
        }
    }
    if (!$chat_count) {
        echo "这里空荡荡的,还没有人聊天。";
    }
    //删除较早记录
    if ($chat_count == 20) {
        $sql = "DELETE FROM `game_chat` WHERE `mode`=1 AND `id`<" . $min_id;
        sql($sql);
    }
} else if ($mode == 2) {
    $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 2 AND `oid`=" . $user_id . " ORDER BY `id` DESC LIMIT 0, 15 ";
    $result = sql($sql);
    if (!$GLOBALS['mysqli']->errno) {
        $chat_count = 0;
        //输出伴侣昵称
        $bl_id = value::get_user_value('bl.id');
        $sex = value::get_game_user_value("sex");
        while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
            br();
            echo date("H:i", strtotime($time)) . " ";
            $oname = value::get_game_user_value('name', $oid);
            $o_is_online = value::get_game_user_value('is_online', $oid);
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
                echo "({$bl_nick_name})";
            }
            if ($o_is_online) {
                user::showVipLogo($oid, true);
                cmd::addcmd('e12,' . $oid . ',5', $oname);
            } else {
                user::showVipLogo($oid, true);
                echo $oname;
            }
            echo " 说:" . $chats . " ";
            cmd::addcmd('e53,' . $oid . ',1', $o_is_online ? '回复' : '留言');
            $chat_count++;
            $min_id = $chat_id;
            //记录私人聊天编号
            if ($chat_count == 1) {
                if ($min_id > value::get_user_value('siliao_count')) {
                    value::set_user_value('siliao_count', $min_id);
                }
            }
        }
        //无人私聊
        if (!$chat_count) {
            echo "暂时还没有人私聊你!";
        }
        //删除较早记录
        if ($chat_count == 15) {
            $sql = "DELETE FROM `game_chat` WHERE `mode`=2 AND `oid`=" . $user_id . " AND `id`<" . $min_id;
            sql($sql);
        }
    }
} else if ($mode == 3) {
    $is_chushi = value::get_game_user_value('is_chushi', $user_id);
    if ($is_chushi) {
        $shifu_id = $user_id;
    } else {
        $shifu_id = value::get_game_user_value('shifu.id', $user_id);
    }
    if ($shifu_id) {
        echo "[师门聊天]";
        $cmd = cmd::addcmd('e131', '发言', false);
        echo "<form action='game.php?cmd=" . $cmd . "' method='post'>发言:<input type='text' id='chats' name='chats' maxlength='50'><input type='submit' 'name'='确定' value='确定'></form>";
        $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 16 AND `oid`={$shifu_id} ORDER BY `id` DESC LIMIT 0, 15 ";
        $result = sql($sql);
        $chat_count = 0;
        $sm_nick_name = "";
        while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
            if ($chat_count) {
                br();
            }
            $sm_nick_name = user::get_shimen_nick_name($oid);
            echo date("H:i", strtotime($time)) . " ({$sm_nick_name})";
            user::showVipLogo($oid, true);
            cmd::addcmd('e12,' . $oid . ',2', value::get_game_user_value('name', $oid));
            echo " 说:" . $chats;
            $chat_count++;
            $min_id = $chat_id;
            //记录师门聊天编号
            if ($chat_count == 1) {
                if ($min_id > value::get_user_value('smlt_count')) {
                    value::set_user_value('smlt_count', $min_id);
                }
            }
        }
        if (!$chat_count) {
            echo "这里空荡荡的,还没有人聊天。";
        }
    } else {
        echo "你还没有师门,无法聊天。";
    }
    //删除较早记录
    if ($chat_count == 15) {
        $sql = "DELETE FROM `game_chat` WHERE `mode`=16 AND `oid`=" . $shifu_id . " AND `id`<" . $min_id;
        sql($sql);
    }
} else if ($mode == 4) {
    value::set_user_value('hs_look_mode', 1);
    $sql = "SELECT `id`,`uid`,`oid`, `chats`, `time`,`guangbo_mode` FROM `game_chat` WHERE `mode` = 5 ORDER BY `id` DESC LIMIT 0, 15 ";
    $result = sql($sql);
    $chat_count = 0;
    while (list($chat_id, $oid, $prop_id, $chats, $time, $guangbo_mode) = $result->fetch_row()) {
        br();
        echo date("H:i", strtotime($time)) . " ";
        echo " " . $chats . " ";
        if (!$guangbo_mode) {
            cmd::addcmd('e23,5,4,' . $prop_id, '查看详情');
        } else {
            $heishi_pet_id = $prop_id;
            cmd::addcmd('e124,' . $heishi_pet_id, '查看详情');
        }
        $chat_count++;
        $min_id = $chat_id;
//记录黑市广告编号
        if ($chat_count == 1) {
            if ($min_id > value::get_user_value('hsgg_count')) {
                value::set_user_value('hsgg_count', $min_id);
            }
        }
    }
    if (!$chat_count) {
        echo "这里空荡荡的,还没有黑市广告。";
    }
//删除较早记录
    if ($chat_count == 15) {
        $sql = "DELETE FROM `game_chat` WHERE `mode`=5 AND `id`<" . $min_id;
        sql($sql);
    }
} else if ($mode == 5) {
    $team_id = team::get_user_team($user_id);
    if ($team_id) {
        echo "[组队频道聊天]";
        $cmd = cmd::addcmd('e147', '发言', false);
        echo "<form action='game.php?cmd=" . $cmd . "' method='post'>发言:<input type='text' id='chats' name='chats' maxlength='50'><input type='submit' 'name'='确定' value='确定'></form>";
        $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 18 AND `oid`={$team_id} ORDER BY `id` DESC LIMIT 0, 15 ";
        $result = sql($sql);
        $chat_count = 0;
        while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
            if ($chat_count) {
                br();
            }
            echo date("H:i", strtotime($time)) . " ";
            user::showVipLogo($oid, true);
            cmd::addcmd('e12,' . $oid . ',2', value::get_game_user_value('name', $oid));
            echo " 说:" . $chats;
            $chat_count++;
            $min_id = $chat_id;
            //记录组队聊天编号
            if ($chat_count == 1) {
                if ($min_id > value::get_user_value('zdlt_count')) {
                    value::set_user_value('zdlt_count', $min_id);
                }
            }
        }
        if (!$chat_count) {
            echo "这里空荡荡的,还没有人聊天。";
        }
        //删除较早记录
        if ($chat_count == 15) {
            $sql = "DELETE FROM `game_chat` WHERE `mode`=18 AND `oid`=" . $team_id . " AND `id`<" . $min_id;
            sql($sql);
        }
    } else {
        echo "你还没有小队,无法聊天。";
    }
} else if ($mode == 6) {
    $union_id = user::get_union($user_id);
    if ($union_id) {
        echo "[帮派频道聊天]";
        $cmd = cmd::addcmd('e183', '发言', false);
        echo "<form action='game.php?cmd=" . $cmd . "' method='post'>发言:<input type='text' id='chats' name='chats' maxlength='50'><input type='submit' 'name'='确定' value='确定'></form>";
        $sql = "SELECT `id`,`uid`, `chats`, `time` FROM `game_chat` WHERE `mode` = 4 AND `oid`={$union_id} ORDER BY `id` DESC LIMIT 0, 15 ";
        $result = sql($sql);
        $chat_count = 0;
        while (list($chat_id, $oid, $chats, $time) = $result->fetch_row()) {
            if ($chat_count) {
                br();
            }
            echo date("H:i", strtotime($time)) . " ";
            user::showVipLogo($oid, true);
            cmd::addcmd('e12,' . $oid . ',2', value::get_game_user_value('name', $oid));
            echo " 说:" . $chats;
            $chat_count++;
            $min_id = $chat_id;
            //记录帮派聊天编号
            if ($chat_count == 1) {
                if ($min_id > value::get_user_value('ghlt_count')) {
                    value::set_user_value('ghlt_count', $min_id);
                }
            }
        }
        if (!$chat_count) {
            echo "这里空荡荡的,还没有人聊天。";
        }
        //删除较早记录
        if ($chat_count == 15) {
            $sql = "DELETE FROM `game_chat` WHERE `mode`=18 AND `oid`=" . $team_id . " AND `id`<" . $min_id;
            sql($sql);
        }
        //返回帮派
        br();
        cmd::addcmd("e177", "返回帮派");
    } else {
        echo "你还没有帮派,无法聊天。";
    }
}
cmd::set_show_return_game();
return;

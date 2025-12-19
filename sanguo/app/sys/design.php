<?php
//系统 发送广播
function send_guangbo($step = 0)
{
    if (!$step) {
        $cmd = cmd::addcmd('send_guangbo,1', '发送列表广播', false);
        echo <<<petform
<form action="game.php?cmd={$cmd}" method="post">
请您输入要发送的广播:
<br>
<input type='text' name='chats'>
<input type='submit' value='确定'>
</form>
petform;
        cmd::add_last_cmd("design_game");
    } else {
        c_add_guangbo(post::get('chats'));
        e0();
        return;
    }
}

//论坛 设置版主
function set_bbs_banzhu($mode = 0, $set_banzhu_id = 0)
{
    if (!$mode) {
        //获取版主
        echo "版主列表如下:";
        br();
        $bbs_banzhu_count = 0;
        $sql = "SELECT `id`,`name` FROM `game_user` WHERE `is_bbs_banzhu` =1 LIMIT 5";
        $rs = sql($sql);
        while (list($bbs_banzhu_id, $bbs_banzhu_name) = $rs->fetch_row()) {
            echo $bbs_banzhu_name, " ";
            cmd::addcmd("set_bbs_banzhu,1,$bbs_banzhu_id", "删除");
            br();
            $bbs_banzhu_count++;
        }
        if (!$bbs_banzhu_count) {
            echo "暂时没有版主。";
        }
    } else if ($mode == 1) {
        value::set_game_user_value('is_bbs_banzhu', 0, $set_banzhu_id);
        set_bbs_banzhu();
        return;
    } else if ($mode == 2) {
        $set_banzhu_id = value::getvalue('game_user', 'id', 'name', post::get('name'));
        value::set_game_user_value('is_bbs_banzhu', 1, $set_banzhu_id);
        set_bbs_banzhu();
        return;
    }
    $url = cmd::addcmd("set_bbs_banzhu,2", "增加版主", false, true);
    echo <<<form
<form action="$url" method="post">
玩家名称:
<input type="text" name="name">
<br>
<input type="submit" name="submit" value="增加版主">
</form>
form;
    cmd::add_last_cmd("design_game");
}

//地图函数
//创建地图
function add_map($is_update = false)
{
    //是否修改
    if ($is_update == 1) {
        $GLOBALS['t_arr'][0] = $is_update;
    }
    ctm::show_ctm('design/ctm_addmap');
    cmd::add_last_cmd("design_game");
    br();
}

//连接地图
function link_map($direction = 0, $map_id = 0)
{
    $GLOBALS['t_arr'][0] = $direction;
    $GLOBALS['t_arr'][1] = $map_id;
    ctm::show_ctm('design/ctm_linkmap');
}

//断开地图
function cut_map($direction, $q_ok)
{
    $GLOBALS['t_arr'][0] = $direction;
    $GLOBALS['t_arr'][1] = $q_ok;
    ctm::show_ctm('design/ctm_cutmap');
}

//删除地图
function del_map($map_id, $q_ok)
{
    $map_name = value::getvalue('map', 'name', 'id', $map_id);
    if ($q_ok) {
        $sql = "UPDATE `map` SET `exit_b`=0 WHERE `exit_b`='{$map_id}'";
        sql($sql);
        $sql = "UPDATE `map` SET `exit_x`=0 WHERE `exit_x`='{$map_id}'";
        sql($sql);
        $sql = "UPDATE `map` SET `exit_d`=0 WHERE `exit_d`='{$map_id}'";
        sql($sql);
        $sql = "UPDATE `map` SET `exit_n`=0 WHERE `exit_n`='{$map_id}'";
        sql($sql);
        $sql = "DELETE FROM `map` WHERE `id`='{$map_id}' LIMIT 1";
        sql($sql);
        echo "<b style='color:#ff0000'>删除" . $map_name . "(s" . $map_id . ")成功</b>";
        br();
        link_map();
        return;
    } else {
        echo "<span style='color:#ff0000'>确定要<b>删除</b>" . $map_name . "(s" . $map_id . ")地图吗?</span>";
        br();
        cmd::addcmd('del_map,' . $map_id . ',1', '<b>确定删除</b>');
    }
}

//NPC函数
//创建npc函数
function add_npc($npc_id = 0)
{
    $GLOBALS['t_arr'][0] = $npc_id;
    ctm::show_ctm('design/ctm_addnpc');
    br();
    cmd::add_last_cmd("design_game");
}

//创建npc函数2
function add_npc2($step = 0, $npc_id = 0)
{
    $table_name = "npc";
    $rs = sql("SHOW FULL COLUMNS FROM `$table_name`");
    if (!$step) {
        $url = cmd::addcmd("add_npc2,1", "创建NPC", false, true);
        echo "<form action='{$url}' method='post'  id='form1'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            if ($npc_id) {
                $col_Default = value::get_npc_value($npc_id, $col_Name);
            }
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='创建'>创建NPC</button></form>";
        cmd::add_last_cmd("design_game");
    } else {
        $col_Name_str = "";
        $col_Name_arr = array();
        $col_Value_str = "";
        $col_Value_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            array_push($col_Name_arr, $col_Name);
            array_push($col_Value_arr, post::get($col_Name));
        }
        foreach ($col_Name_arr as $col_Name_tmp_str) {
            $col_Name_str .= "`{$col_Name_tmp_str}`,";
        }
        foreach ($col_Value_arr as $col_Value_tmp_str) {
            if ($col_Value_tmp_str != "") {
                $col_Value_str .= "'{$col_Value_tmp_str}',";
            } else {
                $col_Value_str .= "NULL,";
            }
        }
        sql("INSERT INTO `$table_name` (" . trim($col_Name_str, ",") . ") VALUES (" . trim($col_Value_str, ",") . ")");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "创建成功";
            br();
        }
        set_npc();
        return;
    }
}

//修改npc
function set_npc()
{
    ctm::show_ctm('design/ctm_setnpc');
    cmd::add_last_cmd("design_game");
}

//修改npc2
function change_npc($npc_id = 0, $step = 0)
{

    $table_name = "npc";
    $rs = sql("SHOW FULL COLUMNS FROM `$table_name`");
    if (!$step) {
        $url = cmd::addcmd("change_npc,$npc_id,1", "修改NPC", false, true);
        echo "<form action='{$url}' method='post' id='form1'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Default = value::get_npc_value($npc_id, $col_Name);
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='修改'>修改NPC</button></form>";
        cmd::addcmd("set_npc", "返回NPC");
    } else {
        $col_Update_str = "";
        $col_Update_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Update_arr[$col_Name] = post::get($col_Name);
        }
        foreach ($col_Update_arr as $col_Name_tmp_str => $col_Name_tmp_value) {
            $col_Update_str .= "`{$col_Name_tmp_str}`='{$col_Name_tmp_value}',";
        }
        $col_Update_str = trim($col_Update_str, ',');
        sql("UPDATE `{$table_name}` SET $col_Update_str WHERE `id`=$npc_id LIMIT 1");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "成功修改{$col_Update_arr['name']}。";
            br();
        }
        set_npc();
        return;
    }
}

//删除NPC
function del_npc($npc_id, $q_ok)
{
    $npc_name = value::getvalue('npc', 'name', 'id', $npc_id);
    if ($q_ok) {
        $sql = "DELETE FROM `npc` WHERE `id`='{$npc_id}' LIMIT 1";
        sql($sql);
        echo "<b style='color:#ff0000'>删除" . $npc_name . "成功</b>";
        br();
        set_npc();
        return;
    } else {
        echo "<span style='color:#ff0000'>确定要<b>删除</b>" . $npc_name . "吗?</span>";
        br();
        cmd::addcmd('del_npc,' . $npc_id . ',1', '<b>确定删除</b>');
        br();
        cmd::addcmd('set_npc', '返回上级');
    }
}

//放置地图NPC
function map_add_npc($npc_id)
{
    $in_map_id = value::get_game_user_value('in_map_id');
    $npc = value::getvalue('map', 'npc', 'id', $in_map_id);
    if ($npc) {
        $npc .= "," . $npc_id;
    } else {
        $npc = $npc_id;
    }
    value::setvalue('map', 'npc', $npc, 'id', $in_map_id);
    echo "<b style='color:#0000ff'>成功在" . value::getvalue('map', 'name', 'id', $in_map_id) . "放置" . value::getvalue('npc', 'name', 'id', $npc_id) . "</b>";
    br();
    e5();
}

//移除地图NPC
function map_del_npc($npc_id)
{
    $in_map_id = value::get_game_user_value('in_map_id');
    $npc = value::getvalue('map', 'npc', 'id', $in_map_id);
    if ($npc != $npc_id) {
        if (strstr($npc, ',' . $npc_id)) {
            $npc = str_replace(',' . $npc_id, "", $npc);
        } else {
            $npc = str_replace($npc_id . ',', "", $npc);
        }
    } else {
        $npc = "";
    }
    value::setvalue('map', 'npc', $npc, 'id', $in_map_id);
    echo "<b style='color:#ff0000'>成功在" . value::getvalue('map', 'name', 'id', $in_map_id) . "移除" . value::getvalue('npc', 'name', 'id', $npc_id) . "</b>";
    br();
    set_npc();
}

//NPC增加宠物
function npc_set_pet($npc_id, $step = 0)
{
    if (!$step) {
        $cmd = cmd::addcmd('npc_set_pet,' . $npc_id . ',1', 'npc宠物', false);
        echo <<<npc_pet
输入宠物名称 用空格隔开:
<br>
<form action="game.php?cmd={$cmd}" method="post">
<input type='text' name='pet_name_arr'>
<br>
输入宠物等级 用空格隔开:
<br>
<input type='text' name='pet_lvl_arr' autocomplete="true">
<br>
<input type='submit' value='确定'>
</form>
npc_pet;
    } else {
        $name_count = 0;
        $lvl_count = 0;
        $pet_name_arr = value::real_escape_string($_POST['pet_name_arr']);
        $pet_lvl_arr = value::real_escape_string($_POST['pet_lvl_arr']);
        $pet_id_arr = array();
        if ($pet_name_arr) {
            $pet_name_arr = explode(' ', $pet_name_arr);
            $name_count = count($pet_name_arr);
        }
        if ($pet_lvl_arr) {
            $pet_lvl_arr = explode(' ', $pet_lvl_arr);
            $lvl_count = count($pet_lvl_arr);
        }
        if ($lvl_count == $name_count) {
            for ($i = 0; $i < $name_count; $i++) {
                $oid = value::getvalue('pet', 'id', 'name', $pet_name_arr[$i]);
                array_push($pet_id_arr, $oid);
            }
            $pet_lvl_str = "";
            $pet_id_str = "";
            $id_count = count($pet_id_arr);
            for ($i = 0; $i < $id_count; $i++) {
                if ($i) {
                    $pet_id_str .= "," . $pet_id_arr[$i];
                    $pet_lvl_str .= "," . $pet_lvl_arr[$i];
                } else {
                    $pet_id_str .= $pet_id_arr[$i];
                    $pet_lvl_str .= $pet_lvl_arr[$i];
                }
            }
            value::setvalue('npc', 'pet_id', $pet_id_str, 'id', $npc_id);
            value::setvalue('npc', 'pet_lvl', $pet_lvl_str, 'id', $npc_id);
            echo "<span style='color:#0000ff'>设置成功。</span>";
            br();
            e0();
            return;
        } else {
            echo "<span style='color:#ff0000'>输入有误。</span>";
            br();
            cmd::addcmd('npc_set_pet,' . $npc_id, '返回上级');
            return;
        }
    }
    cmd::addcmd('e0', '返回上级');
}

//宠物函数
//创建宠物
function add_pet($step = 0)
{
    $rs = sql("SHOW FULL COLUMNS FROM `pet`");
    if (!$step) {
        echo <<<javascript
<script>
function getliuwei(){
    pugong=document.getElementById('pugong');
    pufang=document.getElementById('pufang');
    tegong=document.getElementById('tegong');
    tefang=document.getElementById('tefang');
    minjie=document.getElementById('minjie');
    hp=document.getElementById('hp');
    zongliuwei=(Number(pugong.value)+Number(pufang.value)+Number(tegong.value)+Number(tefang.value)+Number(hp.value)/2)*Number(minjie.value);
    liuwei=document.getElementById('liuwei');
    liuwei.innerHTML=parseFloat(zongliuwei).toFixed(2);
}
</script> 
javascript;
        $url = cmd::addcmd("add_pet,1", "创建宠物", false, true);
        echo "<form action='{$url}' method='post'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='创建'>创建宠物</button></form>六维:<span id='liuwei' onclick='getliuwei();'>0.00</span>";
        br();
        cmd::add_last_cmd("design_game");
    } else {
        $col_Name_str = "";
        $col_Name_arr = array();
        $col_Value_str = "";
        $col_Value_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            array_push($col_Name_arr, $col_Name);
            array_push($col_Value_arr, post::get($col_Name));
        }
        foreach ($col_Name_arr as $col_Name_tmp_str) {
            $col_Name_str .= "`{$col_Name_tmp_str}`,";
        }
        foreach ($col_Value_arr as $col_Value_tmp_str) {
            if ($col_Value_tmp_str != "") {
                $col_Value_str .= "'{$col_Value_tmp_str}',";
            } else {
                $col_Value_str .= "NULL,";
            }
        }
        sql("INSERT INTO `pet` (" . trim($col_Name_str, ",") . ") VALUES (" . trim($col_Value_str, ",") . ")");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "创建成功";
            br();
        }
        add_pet();
        return;
    }
}

//修改宠物
function change_pet($pet_id, $step = 0)
{
    $table_name = 'pet';
    $rs = sql("SHOW FULL COLUMNS FROM `{$table_name}`");
    if (!$step) {
        echo <<<javascript
<script>
function getliuwei(){
    pugong=document.getElementById('pugong');
    pufang=document.getElementById('pufang');
    tegong=document.getElementById('tegong');
    tefang=document.getElementById('tefang');
    minjie=document.getElementById('minjie');
    hp=document.getElementById('hp');
    zongliuwei=(Number(pugong.value)+Number(pufang.value)+Number(tegong.value)+Number(tefang.value)+Number(hp.value)/2)*Number(minjie.value);
    liuwei=document.getElementById('liuwei');
    liuwei.innerHTML=parseFloat(zongliuwei).toFixed(2);
}
</script> 
javascript;
        $url = cmd::addcmd("change_pet,$pet_id,1", "修改宠物", false, true);
        echo "<form action='{$url}' method='post'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Value = value::getvalue($table_name, $col_Name, 'id', $pet_id);
            if ($col_Value) {
                $col_Default = $col_Value;
            }
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='创建'>修改宠物</button></form>六维:<span id='liuwei' onclick='getliuwei();'>0.00</span>";
        br();
    } else {
        $col_Update_str = "";
        $col_Update_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Update_arr[$col_Name] = post::get($col_Name);
        }
        foreach ($col_Update_arr as $col_Name_tmp_str => $col_Name_tmp_value) {
            $col_Update_str .= "`{$col_Name_tmp_str}`='{$col_Name_tmp_value}',";
        }
        $col_Update_str = trim($col_Update_str, ',');
        sql("UPDATE `{$table_name}` SET $col_Update_str WHERE `id`=$pet_id LIMIT 1");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "成功修改{$col_Update_arr['name']}。";
            br();
        }
        set_pet();
        return;
    }
    cmd::addcmd('set_pet', '返回上级');
    echo <<<footer
<script>
getliuwei();
</script> 
footer;
}

//显示宠物列表
function set_pet($star = 1)
{
    $GLOBALS['t_arr'][0] = $star;
    ctm::show_ctm('design/ctm_setpet');
    cmd::add_last_cmd("design_game");
}

//宠物技能列表
function pet_set_skill($pet_id, $step = 0, $add_skill_id = 0, $swap_num = 0)
{
    $pet_name = value::getvalue('pet', 'name', 'id', $pet_id);
    $study_lvl = value::getvalue('pet', 'study_lvl', 'id', $pet_id);
    $study_skill_str = value::getvalue('pet', 'study_skill', 'id', $pet_id);
    $study_skill_arr = explode(',', $study_skill_str);
    $study_skill_count = count($study_skill_arr);
    //step 0 查看 1 开始添加 2 添加技能 3上移 4下移 5删除
    if (!$step) {
        echo $pet_name;
        br();
        for ($i = 0; $i < $study_skill_count; $i++) {
            if ($study_skill_arr[$i]) {
                $sql = 'SELECT  `name`, `miaoshu`, `shuxing`,`fangshi`, `leixing`, `mingzhong`, `weili`, `pp`, `zhongji`, `yczt`, `ycztmz`, `min_lianji`, `max_lianji` FROM `skill` WHERE `id`=' . $study_skill_arr[$i];
                $result = sql($sql);
                list($sname, $smiaoshu, $sshuxing, $sfangshi, $sleixing, $smingzhong, $sweili, $spp, $szhongji, $syczt, $sycztmz, $smin_lianji, $smax_lianji) = $result->fetch_row();
                if ($sname) {
                    $sstudy_lvl = $i * $study_lvl;
                    $sstudy_lvl = $sstudy_lvl > 0 ? $sstudy_lvl : 1;
                    $sleixing = skill::get_skill_leixing($study_skill_arr[$i]);
                    if (strstr($sleixing, '攻')) {
                        $sleixing = "<span style='color:#ff00ff'>" . $sleixing . "</span>";
                    }
                    echo "<span style='color:#0000ff'>({$sstudy_lvl}级)</span><br>[{$sshuxing}]<span style='color:#ff0000'>{$sname}</span>(PP:{$spp})" . $sleixing;
                    if ($i) {
                        echo ' ';
                        cmd::addcmd('pet_set_skill,' . $pet_id . ',3,0,' . $i, '上移');
                    }
                    if ($i < $study_skill_count - 1) {
                        echo ' ';
                        cmd::addcmd('pet_set_skill,' . $pet_id . ',4,0,' . $i, '下移');
                    }
                    echo ' ';
                    cmd::addcmd('pet_set_skill,' . $pet_id . ',5,0,' . $i, '删除');
                    br();
                    echo '描述:' . skill::get_skill_desc($study_skill_arr[$i]) . '。<br>' . "威力:{$sweili} 命中:{$smingzhong}<br>重击:{$szhongji} 连击:{$smin_lianji}-{$smax_lianji}次";
                    br();
                }
            }
        }
        cmd::addcmd('pet_set_skill,' . $pet_id . ',1', '添加技能');
        br();
    } else if ($step == 1) {
        $cmd = cmd::addcmd('pet_set_skill,' . $pet_id . ',1', '开始添加', false);
        $add_shuxing_sql = "";
        $shuxing = value::real_escape_string($_POST['shuxing']);
        if (!$shuxing) {
            $shuxing = value::get_user_value('set_skill_shuxing', 0, false);
        } else {
            value::set_user_value('set_skill_shuxing', $shuxing);
        }
        if ($shuxing > 1) {
            $shuxing_arr = array("无", "金", "木", "水", "火", "土");
            $add_sql = "`shuxing` = '" . $shuxing_arr[$shuxing - 2] . "'";
        }
        $add_fangshi_sql = "";
        $fangshi = value::real_escape_string($_POST['fangshi']);
        if (!$fangshi) {
            $fangshi = value::get_user_value('set_skill_fangshi', 0, false);
        } else {
            value::set_user_value('set_skill_fangshi', $fangshi);
        }
        if ($fangshi > 1) {
            $add_sql .= "AND `fangshi` = '" . ($fangshi - 1) . "' ";
        }
        $add_leixing_sql = "";
        $leixing = value::real_escape_string($_POST['leixing']);
        if (!$leixing) {
            $leixing = value::get_user_value('set_skill_leixing', 0, false);
        } else {
            value::set_user_value('set_skill_leixing', $leixing);
        }
        if ($leixing > 1) {
            $add_sql .= "AND `leixing` = '" . ($leixing - 1) . "' ";
        }
        if (!$add_sql) {
            $add_sql = " 1 ";
        } else {
            $add_sql = trim($add_sql, "AND");
        }
        cmd::addcmd('pet_set_skill,' . $pet_id, '返回宠物');
        br();
        echo <<<skill
  <form action="game.php?cmd={$cmd}" method="post" autocomplete="on">
            <span>属性:</span>
    <select id="shuxing" name="shuxing">
        <option value="1">全部</option>
        <option value="2">无</option>
        <option value="3">金</option>
        <option value="4">木</option>
        <option value="5">水</option>
        <option value="6">火</option>
        <option value="7">土</option>
    </select>
    <select id="fangshi" name="fangshi">
        <option value="1">全部</option>
        <option value="2">直接</option>
        <option value="3">前置</option>
    </select>
    <select id="leixing" name="leixing">
        <option value="1">全部</option>
        <option value="2">攻击</option>
        <option value="3">魔攻</option>
        <option value="4">防御</option>
        <option value="5">其它</option>
    </select>
        <input name='submit' type='submit' title='进入' value='进入' style='margin-top: 5px;width:50px;height: 24px;'/>
    </form>
<script>
    var select = document.getElementById('fangshi');
    select.options[$fangshi-1].selected = true;
    var select = document.getElementById('shuxing');
    select.options[$shuxing-1].selected = true;
    var select = document.getElementById('leixing');
    select.options[$leixing-1].selected = true;
</script>
skill;
        $sql = "SELECT  `id`,`name`, `miaoshu`, `shuxing`,`fangshi`, `leixing`, `mingzhong`, `weili`, `pp`, `zhongji`, `yczt`, `ycztmz`, `min_lianji`, `max_lianji`,(`weili`* `mingzhong`/ 100/ `fangshi`* (1 + `zhongji` / 100)* (`max_lianji` + `min_lianji`)/ 2) FROM `skill` WHERE $add_sql ORDER BY (`weili`* `mingzhong`/ 100/ `fangshi`* (1 + `zhongji` / 100)* (`max_lianji` + `min_lianji`)/ 2),`mingzhong`";
        $result = sql($sql);
        while (list($s_id, $sname, $smiaoshu, $sshuxing, $sfangshi, $sleixing, $smingzhong, $sweili, $spp, $szhongji, $syczt, $sycztmz, $smin_lianji, $smax_lianji, $zhenshiweili) = $result->fetch_row()) {
            $s_ok = true;
            for ($i = 0; $i < $study_skill_count; $i++) {
                if ($s_id == $study_skill_arr[$i]) {
                    $s_ok = false;
                }
            }
            if ($s_ok) {
                $sleixing = skill::get_skill_leixing($s_id);
                if (strstr($sleixing, '攻')) {
                    $sleixing = "<span style='color:#ff00ff'>" . $sleixing . "</span>";
                }
                echo "[{$sshuxing}]<span style='color:#ff0000'>{$sname}</span>(PP:{$spp})" . $sleixing . ' ';
                cmd::addcmd('pet_set_skill,' . $pet_id . ',2,' . $s_id, '添加');
                br();
                $zhenshiweili = (int)$zhenshiweili;
                echo '描述:' . skill::get_skill_desc($s_id) . '。<br>' . "威力:{$sweili} 命中:{$smingzhong}<br>重击:{$szhongji} 连击:{$smin_lianji}-{$smax_lianji}次<br>真实威力:{$zhenshiweili}";
                br();
            }
        }
        cmd::addcmd('pet_set_skill,' . $pet_id, '返回宠物');
        br();
    } else if ($step == 2) {
        $add_skill_name = value::get_skill_value($add_skill_id, 'name');
        if ($study_skill_count > 9) {
            echo "<span style='color:#ff0000'>{$pet_name}已经学了10个技能,无法学习{$add_skill_name}。</span>";
            br();
            pet_set_skill($pet_id);
            return;
        }
        for ($i = 0; $i < $study_skill_count; $i++) {
            if ($add_skill_id == $study_skill_arr[$i]) {
                echo "<span style='color:#ff0000'>{$pet_name}已经学过{$add_skill_name}。</span>";
                br();
                pet_set_skill($pet_id);
                return;
            }
        }
        if ($study_skill_str) {
            $study_skill_str .= ',' . $add_skill_id;
        } else {
            $study_skill_str = $add_skill_id;
        }
        value::setvalue('pet', 'study_skill', $study_skill_str, 'id', $pet_id);
        echo "<span style='color:#0000ff'>{$pet_name}学会{$add_skill_name}。</span>";
        br();
        if ($study_skill_count != 9) {
            pet_set_skill($pet_id, 1);
        } else {
            pet_set_skill($pet_id);
        }
        return;
    } else if ($step == 3) {
        $t_skill_id = $study_skill_arr[$swap_num - 1];
        $study_skill_arr[$swap_num - 1] = $study_skill_arr[$swap_num];
        $study_skill_arr[$swap_num] = $t_skill_id;
        $new_study_skill_str = "";
        for ($i = 0; $i < $study_skill_count; $i++) {
            if ($i) {
                $new_study_skill_str = $new_study_skill_str . ',' . $study_skill_arr[$i];
            } else {
                $new_study_skill_str = $study_skill_arr[$i];
            }
        }
        value::setvalue('pet', 'study_skill', $new_study_skill_str, 'id', $pet_id);
        pet_set_skill($pet_id);
        return;
    } else if ($step == 4) {
        $t_skill_id = $study_skill_arr[$swap_num + 1];
        $study_skill_arr[$swap_num + 1] = $study_skill_arr[$swap_num];
        $study_skill_arr[$swap_num] = $t_skill_id;
        $new_study_skill_str = "";
        for ($i = 0; $i < $study_skill_count; $i++) {
            if ($i) {
                $new_study_skill_str .= ',' . $study_skill_arr[$i];
            } else {
                $new_study_skill_str = $study_skill_arr[$i];
            }
        }
        value::setvalue('pet', 'study_skill', $new_study_skill_str, 'id', $pet_id);
        pet_set_skill($pet_id);
        return;
    } else if ($step == 5) {
        array_splice($study_skill_arr, $swap_num, 1);
        $study_skill_count = count($study_skill_arr);
        $new_study_skill_str = "";
        for ($i = 0; $i < $study_skill_count; $i++) {
            if ($i) {
                $new_study_skill_str .= ',' . $study_skill_arr[$i];
            } else {
                $new_study_skill_str = $study_skill_arr[$i];
            }
        }
        value::setvalue('pet', 'study_skill', $new_study_skill_str, 'id', $pet_id);
        pet_set_skill($pet_id);
        return;
    }
    cmd::addcmd('e0', '宠物列表');
}

//技能函数
//创建技能
function add_skill($step = 0)
{
    $table_name = "skill";
    $rs = sql("SHOW FULL COLUMNS FROM `$table_name`");
    if (!$step) {
        echo <<<javascript
<script>
function getweili(){
    str_gjfs="";
    
    weili=document.getElementById('weili');
    mingzhong=document.getElementById('mingzhong');
    zhongji=document.getElementById('zhongji');
    min_linji=document.getElementById('min_lianji');
    max_lianji=document.getElementById('max_lianji');
    
    fangshi=document.getElementById('fangshi');
    leixing=document.getElementById('leixing');
  
    switch(fangshi.value){
        case '1':
            str_gjfs+="直接";
            break;
        case '2':
            str_gjfs+="前置";
            break;
    }
    switch(leixing.value){
        case '1':
            str_gjfs+="攻击";
            break;
        case '2':
            str_gjfs+="魔攻";
            break;
        case '3':
            str_gjfs+="防御";
            break;
        case '4':
            str_gjfs+="其它";
            break;
    }
    
        yczt=document.getElementById('yczt');
        ycztmz=document.getElementById('ycztmz');
        str_yczt=ycztmz.value+"%";
     switch (Number(yczt.value)) {
            case 0:
                str_yczt+="正常";
                break;
            case 1:
                str_yczt+="中毒";
                break;
            case 2:
                str_yczt+="麻痹";
                break;
            case 3:
                str_yczt+="烧伤";
                break;
            case 4:
                str_yczt+="冰冻";
                break;
            case 5:
                str_yczt+="混乱";
                break;
            case 6:
                str_yczt+="迷惑";
                break;
            case 7:
                str_yczt+="睡眠";
                break;
            case 8:
                str_yczt+="剧毒";
                break;
            case 9:
                str_yczt+="束缚";
                break;
            case 10:
                str_yczt+="吸血";
                break;
        }
    
    str_zhenshiweili=Math.round(Number(weili.value)*Number(mingzhong.value)/100/fangshi.value*(1+Number(zhongji.value)/100)*(Number(min_lianji.value)+Number(max_lianji.value))/2);
     
    zhenshiweili=document.getElementById('zhenshiweili');
    zhenshiweili.innerHTML=str_zhenshiweili;
    gjfs=document.getElementById('gjfs');
    gjfs.innerHTML=str_gjfs;
    ycxg=document.getElementById('ycxg');
    ycxg.innerHTML=str_yczt;
}
</script> 
javascript;
        $url = cmd::addcmd("add_skill,1", "创建技能", false, true);
        echo "<form action='{$url}' method='post'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='创建'>创建技能</button></form>真实威力:<span id='zhenshiweili'>0</span><br>攻击方式:<span id='gjfs'>直接攻击</span><br>异常效果:<span id='ycxg'>没有</span>";
        br();
        cmd::add_last_cmd("design_game");
    } else {
        $col_Name_str = "";
        $col_Name_arr = array();
        $col_Value_str = "";
        $col_Value_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            array_push($col_Name_arr, $col_Name);
            array_push($col_Value_arr, post::get($col_Name));
        }
        foreach ($col_Name_arr as $col_Name_tmp_str) {
            $col_Name_str .= "`{$col_Name_tmp_str}`,";
        }
        foreach ($col_Value_arr as $col_Value_tmp_str) {
            if ($col_Value_tmp_str != "") {
                $col_Value_str .= "'{$col_Value_tmp_str}',";
            } else {
                $col_Value_str .= "NULL,";
            }
        }
        sql("INSERT INTO `$table_name` (" . trim($col_Name_str, ",") . ") VALUES (" . trim($col_Value_str, ",") . ")");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "创建成功";
            br();
        }
        add_skill();
        return;
    }
}

//修改技能
function change_skill($skill_id, $step = 0)
{
    $table_name = "skill";
    $rs = sql("SHOW FULL COLUMNS FROM `$table_name`");
    if (!$step) {
        echo <<<javascript
<script>
function getweili(){
    str_gjfs="";
   
    weili=document.getElementById('weili');
    mingzhong=document.getElementById('mingzhong');
    zhongji=document.getElementById('zhongji');
    min_linji=document.getElementById('min_lianji');
    max_lianji=document.getElementById('max_lianji');
    
    fangshi=document.getElementById('fangshi');
    leixing=document.getElementById('leixing');
  
    switch(fangshi.value){
        case '1':
            str_gjfs+="直接";
            break;
        case '2':
            str_gjfs+="前置";
            break;
    }
    switch(leixing.value){
        case '1':
            str_gjfs+="攻击";
            break;
        case '2':
            str_gjfs+="魔攻";
            break;
        case '3':
            str_gjfs+="防御";
            break;
        case '4':
            str_gjfs+="其它";
            break;
    }
    
        yczt=document.getElementById('yczt');
        ycztmz=document.getElementById('ycztmz');
        str_yczt=ycztmz.value+"%";
     switch (Number(yczt.value)) {
            case 0:
                str_yczt+="正常";
                break;
            case 1:
                str_yczt+="中毒";
                break;
            case 2:
                str_yczt+="麻痹";
                break;
            case 3:
                str_yczt+="烧伤";
                break;
            case 4:
                str_yczt+="冰冻";
                break;
            case 5:
                str_yczt+="混乱";
                break;
            case 6:
                str_yczt+="迷惑";
                break;
            case 7:
                str_yczt+="睡眠";
                break;
            case 8:
                str_yczt+="剧毒";
                break;
            case 9:
                str_yczt+="束缚";
                break;
            case 10:
                str_yczt+="吸血";
                break;
        }
    
    str_zhenshiweili=Math.round(Number(weili.value)*Number(mingzhong.value)/100/fangshi.value*(1+Number(zhongji.value)/100)*(Number(min_lianji.value)+Number(max_lianji.value))/2);
     
    zhenshiweili=document.getElementById('zhenshiweili');
    zhenshiweili.innerHTML=str_zhenshiweili;
    gjfs=document.getElementById('gjfs');
    gjfs.innerHTML=str_gjfs;
    ycxg=document.getElementById('ycxg');
    ycxg.innerHTML=str_yczt;
}
</script> 
javascript;
        $url = cmd::addcmd("change_skill,$skill_id,1", "修改技能", false, true);
        echo "<form action='{$url}' method='post'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Default = value::get_skill_value($skill_id, $col_Name);
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='修改'>修改技能</button></form>真实威力:<span id='zhenshiweili'>0</span><br>攻击方式:<span id='gjfs'>直接攻击</span><br>异常效果:<span id='ycxg'>没有</span>";
        br();
        echo "<script>getweili();</script>";
        cmd::addcmd("set_skill", "返回技能");
    } else {
        $col_Update_str = "";
        $col_Update_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Update_arr[$col_Name] = post::get($col_Name);
        }
        foreach ($col_Update_arr as $col_Name_tmp_str => $col_Name_tmp_value) {
            $col_Update_str .= "`{$col_Name_tmp_str}`='{$col_Name_tmp_value}',";
        }
        $col_Update_str = trim($col_Update_str, ',');
        sql("UPDATE `{$table_name}` SET $col_Update_str WHERE `id`=$skill_id LIMIT 1");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "成功修改{$col_Update_arr['name']}。";
            br();
        }
        set_skill();
        return;
    }
}

//显示技能列表
function set_skill()
{
    ctm::show_ctm('design/ctm_setskill');
    cmd::add_last_cmd("design_game");
}


//任务函数
//创建任务
function add_task($step = 0, $task_id = 0)
{
    $table_name = "task";
    $rs = sql("SHOW FULL COLUMNS FROM `$table_name`");
    if (!$step) {
        $url = cmd::addcmd("add_task,1", "创建任务", false, true);
        echo "<form action='{$url}' method='post'  id='form1'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            if ($task_id) {
                $col_Default = value::get_task_value($task_id, $col_Name);
            }
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='创建'>创建任务</button></form>";
        cmd::add_last_cmd("design_game");
    } else {
        $col_Name_str = "";
        $col_Name_arr = array();
        $col_Value_str = "";
        $col_Value_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            array_push($col_Name_arr, $col_Name);
            array_push($col_Value_arr, post::get($col_Name));
        }
        foreach ($col_Name_arr as $col_Name_tmp_str) {
            $col_Name_str .= "`{$col_Name_tmp_str}`,";
        }
        foreach ($col_Value_arr as $col_Value_tmp_str) {
            if ($col_Value_tmp_str != "") {
                $col_Value_str .= "'{$col_Value_tmp_str}',";
            } else {
                $col_Value_str .= "NULL,";
            }
        }
        sql("INSERT INTO `$table_name` (" . trim($col_Name_str, ",") . ") VALUES (" . trim($col_Value_str, ",") . ")");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "创建成功";
            br();
        }
        set_task();
        return;
    }
}

//修改任务
function change_task($task_id, $step = 0)
{
    $table_name = "task";
    $rs = sql("SHOW FULL COLUMNS FROM `$table_name`");
    if (!$step) {
        $url = cmd::addcmd("change_task,$task_id,1", "修改任务", false, true);
        echo "<form action='{$url}' method='post' id='form1'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Default = value::get_task_value($task_id, $col_Name);
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='修改'>修改任务</button></form>";
        cmd::addcmd("set_task", "返回任务");
    } else {
        $col_Update_str = "";
        $col_Update_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Update_arr[$col_Name] = post::get($col_Name);
        }
        foreach ($col_Update_arr as $col_Name_tmp_str => $col_Name_tmp_value) {
            $col_Update_str .= "`{$col_Name_tmp_str}`='{$col_Name_tmp_value}',";
        }
        $col_Update_str = trim($col_Update_str, ',');
        sql("UPDATE `{$table_name}` SET $col_Update_str WHERE `id`=$task_id LIMIT 1");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "成功修改{$col_Update_arr['name']}。";
            br();
        }
        set_task();
        return;
    }
}

//显示任务列表
function set_task($yeshu = 0)
{
    //返回地图
    cmd::addcmd('e5', '返回地图');
    br();
    $user_id = uid();
    //提取数量
    $sql = "SELECT COUNT(*) FROM task";
    $rs = sql($sql);
    list($count) = $rs->fetch_row();
    $danye_count = 15;
    $start_list_num = $yeshu * $danye_count;
    $sql = "SELECT `id`,`name`,`lvl` FROM task LIMIT {$start_list_num},{$danye_count}";
    $rs = sql($sql);
    while (list($id, $name, $lvl) = $rs->fetch_row()) {
        echo "{$name}({$lvl}级) ";
        cmd::addcmd("add_task,0,{$id}", "复制");
        echo " ";
        cmd::addcmd("change_task,{$id}", "修改");
        br();
    }
    //显示上下页
    $fanye_ok = false;
    $max_page_num = (int)(($count - 1) / $danye_count) + 1;
    if ($yeshu > 0) {
        if ($yeshu > 1) {
            cmd::addcmd('set_task,0', '首页');
            echo "|";
        }
        cmd::addcmd('set_task,' . ($yeshu - 1), '上—页');
        $fanye_ok = true;
    }
    if ($count > $danye_count * ($yeshu + 1)) {
        if ($fanye_ok) {
            echo '|';
        }
        cmd::addcmd('set_task,' . ($yeshu + 1), '下—页');
        if ($yeshu + 2 < $max_page_num) {
            echo "|";
            cmd::addcmd('set_task,' . ($max_page_num - 1), '末页');
        }
        $fanye_ok = true;
    }
    //是否有操作
    if ($fanye_ok) {
        br();
    }
    cmd::add_last_cmd("design_game");
}

function user_show_recharge3()
{
    $start_date = post::get('start_date');
    $end_date = post::get('end_date');
    if (!$start_date) {
        $start_date = date("Y-m-01");
    }
    if (!$end_date) {
        $end_date = date("Y-m-d");
    }
    $cmd = cmd::addcmd('user_show_recharge3', '', false);
    echo <<<petform
<form action="game.php?cmd={$cmd}" method="post">
<input type='text' name='start_date' value="$start_date" placeholder="开始日期">
至
<input type='text' name='end_date' value="$end_date" placeholder="结束日期">
<input type='submit' value='确定'>
</form>
petform;
    $dbname = $GLOBALS['mysql_dbname'];
    sql("USE `{$dbname}`");
    $sql = "SELECT DISTINCT(community) FROM recharge;";
    $rs = sql($sql);
    $community_arr = array();
    while (list($community) = $rs->fetch_row()) {
        array_push($community_arr, $community);
    }
    foreach ($community_arr as $community) {
        $sql = "SELECT CHAR_LENGTH(`time`) FROM `recharge` WHERE `community` = '{$community}' LIMIT 1;";
        $rs = sql($sql);
        list($time_len) = $rs->fetch_row();
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date . " 23:59:59");
        for ($i = 0; $i < $time_len - 10; $i++) {
            $start_time .= "0";
            $end_time .= "0";
        }
        $sql = "SELECT `user`,`id`,`amount`,`time` FROM `recharge` WHERE `community` = '{$community}' AND `time` >= '{$start_time}' AND `time` <= '{$end_time}'";
        $rs = sql($sql);
        while (list($user, $id, $amount) = $rs->fetch_row()) {
            $amount1 = $amount / 10;
            echo $id . "、玩家(ID：" . $user . ")充值" . $amount1 . "元";
            br();
        }
    }
}
//流水数据
function user_show_recharge()
{
    $start_date = post::get('start_date');
    $end_date = post::get('end_date');
    if (!$start_date) {
        $start_date = date("Y-m-01");
    }
    if (!$end_date) {
        $end_date = date("Y-m-d");
    }
    $cmd = cmd::addcmd('user_show_recharge', '', false);
    echo <<<petform
<form action="game.php?cmd={$cmd}" method="post">
<input type='text' name='start_date' value="$start_date" placeholder="开始日期">
至
<input type='text' name='end_date' value="$end_date" placeholder="结束日期">
<input type='submit' value='确定'>
</form>
petform;
    $dbname = $GLOBALS['mysql_dbname'];
    sql("USE `{$dbname}`");
    $sql = "SELECT DISTINCT(community) FROM recharge;";
    $rs = sql($sql);
    $community_arr = array();
    while (list($community) = $rs->fetch_row()) {
        array_push($community_arr, $community);
    }
    foreach ($community_arr as $community) {
        echo "本月总金额:";
        $sql = "SELECT CHAR_LENGTH(`time`) FROM `recharge` WHERE `community` = '{$community}' LIMIT 1;";
        $rs = sql($sql);
        list($time_len) = $rs->fetch_row();
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date . " 23:59:59");
        for ($i = 0; $i < $time_len - 10; $i++) {
            $start_time .= "0";
            $end_time .= "0";
        }
        $sql = "SELECT SUM(`amount` / 10) FROM `recharge` WHERE `community` = '$community' AND `time` >= $start_time AND `time` <= $end_time;";
        $rs = sql($sql);
        list($shouru) = $rs->fetch_row();
        $shouru = $shouru ? $shouru : 0;
        $shouru = sprintf("%.2f", $shouru);
        echo $shouru, "元 ";
        switch ($community) {
            case 'xxm':
                $lirun = sprintf("%.2f", $shouru * 0.4);
                break;
            case 'mp':
                $lirun = sprintf("%.2f", $shouru * 0.5);
                break;
        }
        echo "利润:", $lirun, "元 ";
    }
    br();
    cmd::addcmd('user_show_recharge3', '查看明细');
    br();
    echo "-";
    br();
    for ($i = 0; $i < 6; $i++) {
        $start_time = strtotime(date("Y-m-d H:i:s", mktime(0, 0, 0, date("m") - $i - 1, 1, date("Y"))));
        $end_time = strtotime(date("Y-m-d H:i:s", mktime(23, 59, 59, date("m") - $i, 0, date("Y"))));
        echo date("Y年m月", $start_time), ":";
        br();
        foreach ($community_arr as $community) {
            echo "总金额:";
            $sql = "SELECT CHAR_LENGTH(`time`) FROM `recharge` WHERE `community` = '{$community}' LIMIT 1;";
            $rs = sql($sql);
            list($time_len) = $rs->fetch_row();
            for ($j = 0; $j < $time_len - 10; $j++) {
                $start_time .= "0";
                $end_time .= "0";
            }
            $sql = "SELECT SUM(`amount` / 10) FROM `recharge` WHERE `community` = '$community' AND `time` >= $start_time AND `time` <= $end_time;";
            $rs = sql($sql);
            list($shouru) = $rs->fetch_row();
            $shouru = $shouru ? $shouru : 0;
            $shouru = sprintf("%.2f", $shouru);
            echo $shouru, "元 ";
            switch ($community) {
                case 'xxm':
                    $lirun = sprintf("%.2f", $shouru * 0.4);
                    break;
                case 'mp':
                    $lirun = sprintf("%.2f", $shouru * 0.5);
                    break;
            }
            echo "利润:", $lirun, "元 ";
            br();
            $start_time = strtotime(date("Y-m-d H:i:s", mktime(0, 0, 0, date("m") - $i - 1, 1, date("Y"))));
            $end_time = strtotime(date("Y-m-d H:i:s", mktime(23, 59, 59, date("m") - $i, 0, date("Y"))));

        }
    }
    sql("USE `{$GLOBALS['game_area_dbname']}`");
    cmd::addcmd("add_cdk", "生成兑换");
    br();
    cmd::add_last_cmd("design_game");
}

function user_show_recharge1()
{
    echo "CDK充值明细：";
    br();
    echo "-";
    br();
    $dbname = $GLOBALS['mysql_dbname'];
    $start_time = strtotime(date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), 1, date("Y"))));
    sql("USE `{$dbname}`");
    $sql = "SELECT SUM(`rmb`) FROM `cdk` WHERE `rmb`>0 AND `is_use`>0;";
    $rs = sql($sql);
    list($shouru) = $rs->fetch_row();
    $shouru = sprintf("%.2f", $shouru);
    echo "总金额：";
    echo $shouru, "元 ";
    br();
    $lirun = sprintf("%.2f", $shouru * 0.5);
    echo "分成:", $lirun, "元 ";
    br();
    echo "-";
    br();
    $sql = "SELECT `user_id`,`id`,`rmb` FROM `cdk` WHERE `rmb`>0 AND `is_use`>0";
    $rs = sql($sql);
    while (list($user_id, $id, $rmb) = $rs->fetch_row()) {
        echo $id . "、玩家ID：<" . $user_id . ">CDK充值" . $rmb . "元";
        br();
    }
    sql("USE `{$GLOBALS['game_area_dbname']}`");
    cmd::addcmd("add_cdk", "生成兑换");
    br();
    cmd::add_last_cmd("design_game");
}

//调整数据
function change_user_data($step)
{
    $user_id = uid();
    if (!$step) {
        $cmd = cmd::addcmd('change_user_data,1', '用户添加宠物', false);
        echo <<<petform
输入用户ID或昵称:
<br>
<form action="game.php?cmd={$cmd}" method="post">
<input type='text' name='user_id' value='{$user_id}'>
<br>
输入宠物id 用空格隔开:
<br>
<input type='text' name='pet_name_arr'>
<br>
输入装备id 用空格隔开:
<br>
<input type='text' name='prop_name_arr' value=''>
<br>
输入装备星级 用空格隔开:
<br>
<input type='text' name='prop_star_arr' value='3'>
<br>
输入装备数量 用空格隔开:
<br>
<input type='text' name='prop_count_arr' value=''>
<br>
输入物品id 用空格隔开:
<br>
<input type='text' name='item_name' value=''>
<br>
输入物品数量 用空格隔开:
<br>
<input type='text' name='item_count' value=''>
<br>
<input type='submit' value='确定'>
</form>
petform;
    } else {
        $input_ok = true;
        $name_count = 0;
        $is_id = false;
        $user_id = value::real_escape_string($_POST['user_id']);
        $pet_name_arr = value::real_escape_string($_POST['pet_name_arr']);
        $pet_xingge_arr = value::real_escape_string($_POST['pet_xingge_arr']);
        $pet_texing_arr = value::real_escape_string($_POST['pet_texing_arr']);
        $pet_sex_arr = value::real_escape_string($_POST['pet_sex_arr']);
        $prop_exp_count = (int)value::real_escape_string($_POST['prop_exp_count']);
        $prop_exp = value::real_escape_string($_POST['prop_exp']);
        $item_name = value::real_escape_string($_POST['item_name']);
        $item_count = value::real_escape_string($_POST['item_count']);
        $prop_name_arr = value::real_escape_string($_POST['prop_name_arr']);
        $prop_star_arr = value::real_escape_string($_POST['prop_star_arr']);
        $prop_count_arr = value::real_escape_string($_POST['prop_count_arr']);
        if ($user_id > 0) {
            $is_id = true;
        }
        if (!$is_id) {
            $sql = "SELECT `id` FROM `game_user` WHERE `name`='{$user_id}' LIMIT 1";
            $result = sql($sql);
            list($user_id) = $result->fetch_row();
        }
        if ($user_id > 0) {
            //宠物
            $pet_id_arr = array();
            if ($pet_name_arr) {
                $pet_name_arr = explode(' ', $pet_name_arr);
                $name_count = count($pet_name_arr);
            }
            for ($i = 0; $i < $name_count; $i++) {
                $oid = $pet_name_arr[$i];
                if ($oid) {
                    array_push($pet_id_arr, $oid);
                } else {
                    $input_ok = false;
                    echo "宠物名称输入错误!";
                    br();
                    break;
                }
            }
            if ($input_ok && $pet_name_arr) {
                $id_count = count($pet_id_arr);
                for ($i = 0; $i < $id_count; $i++) {
                    $oid = pet::new_pet($pet_id_arr[$i], 1, 0, $user_id);
                    value::set_pet_value($oid, 'master_mode', 1);
                    echo "<span style='color:#0000ff'>赠送的", pet::get($oid, 'name'), "成功。</span>";
                    br();
                }
            }
            //装备
            if ($prop_name_arr) {
                $prop_name_arr = explode(' ', $prop_name_arr);
                $prop_name_count = count($prop_name_arr);
                $prop_star_arr = explode(' ', $prop_star_arr);
                $prop_count_arr = explode(' ', $prop_count_arr);
                for ($i = 0; $i < $prop_name_count; $i++) {
                    $prop_id = $prop_name_arr[$i];
                    if ($prop_id) {
                        $prop_liangci = value::get_prop_value($prop_id, 'liangci');
                        $prop_name = value::get_prop_value($prop_id, 'name');
                        $zssl = $prop_count_arr[$i] ? $prop_count_arr[$i] : 1;
                        for ($j = 0; $j < $zssl; $j++) {
                            prop::user_get_prop(prop::new_prop($prop_id, 0, $prop_star_arr[$i]), $user_id, 1, false, true);
                        }
                        echo "<span style='color:#0000ff'>赠送{$prop_count_arr[$i]}{$prop_liangci}{$prop_name}成功。</span>";
                        br();
                    } else {
                        echo "道具名称[", $prop_name_arr[$i], "]输入错误!";
                        br();
                    }
                }
            }
            //物品
            if ($item_name) {
                $item_name_arr = explode(' ', $item_name);
                $item_name_count = count($item_name_arr);
                $item_count_arr = explode(' ', $item_count);
                $item_count_count = count($item_count_arr);
                if ($item_name_count == $item_count_count) {
                    for ($i = 0; $i < $item_name_count; $i++) {
                        $item_id = $item_name_arr[$i];
                        if ($item_id) {
                            $item_liangci = value::get_item_value($item_id, 'liangci');
                            $item_name = value::get_item_value($item_id, 'name');
                            item::add_item($item_id, $item_count_arr[$i], false, $user_id, true);
                            echo "<span style='color:#0000ff'>赠送{$item_count_arr[$i]}{$item_liangci}{$item_name}成功。</span>";
                            br();
                        } else {
                            echo "物品名称[", $item_name_arr[$i], "]输入错误!";
                            br();
                        }
                    }
                }
            }
        } else {
            echo "<span style='color:#ff0000'>用户输入错误。</span>";
            br();
        }
        e0();
        return;
    }
    cmd::add_last_cmd("design_game");
}

//添加地图宠物
function map_add_pet($pet_id)
{
    $in_map_id = value::get_game_user_value('in_map_id');
    $area_id = value::getvalue('map', 'area_id', 'id', $in_map_id);
    $pet_name = value::getvalue('map', 'name', 'id', $in_map_id);
    $yaoguai = value::getvalue('map', 'yaoguai', 'id', $in_map_id);
    if ($yaoguai) {
        $yaoguai .= "," . $pet_id;
    } else {
        $yaoguai = $pet_id;
    }
    $sql = "UPDATE `map` SET `yaoguai`='{$yaoguai}' WHERE `area_id`='{$area_id}' AND `name`='{$pet_name}'";
    sql($sql);
    echo "<b style='color:#0000ff'>成功在" . $pet_name . "放置" . value::getvalue('pet', 'name', 'id', $pet_id) . "</b>";
    br();
    set_pet(value::getvalue('pet', 'star', 'id', $pet_id));
}

//移除地图宠物
function map_del_pet($pet_id)
{
    $in_map_id = value::get_game_user_value('in_map_id');
    $area_id = value::getvalue('map', 'area_id', 'id', $in_map_id);
    $pet_name = value::getvalue('map', 'name', 'id', $in_map_id);
    $yaoguai = value::getvalue('map', 'yaoguai', 'id', $in_map_id);
    if ($yaoguai != $pet_id) {
        if (strstr($yaoguai, ',' . $pet_id)) {
            $yaoguai = str_replace(',' . $pet_id, "", $yaoguai);
        } else {
            $yaoguai = str_replace($pet_id . ',', "", $yaoguai);
        }
    } else {
        $yaoguai = "";
    }
    $sql = "UPDATE `map` SET `yaoguai`='{$yaoguai}' WHERE `area_id`='{$area_id}' AND `name`='{$pet_name}'";
    sql($sql);
    echo "<b style='color:#ff0000'>成功在" . $pet_name . "移除" . value::getvalue('pet', 'name', 'id', $pet_id) . "</b>";
    br();
    set_pet(value::getvalue('pet', 'star', 'id', $pet_id));
}


//添加cdk
function add_cdk()
{
    $dbname = $GLOBALS['mysql_dbname'];
    $cdk = post::get('cdk');
    $rmb = post::get('rmb');
    if ($cdk) {
        $is_yuchong = post::get('is_yuchong');
        sql("USE `{$dbname}`");
        sql("LOCK TABLES `cdk`");
        sql("INSERT INTO `cdk` (`cdk`,`rmb`,`is_yuchong`,`event`,`game`) VALUES ('{$cdk}','{$rmb}',{$is_yuchong},'','sanguo');");
        sql("UNLOCK TABLES `cdk`");
        echo "生成成功!";
        br();
    } else {
        $url = cmd::addcmd("add_cdk", "兑换礼包", false, true);
        echo <<<cdkform
生成CDK
<form action="{$url}" method="post">
cdkform;
        sql("USE `{$dbname}`");
        $sql = "DESC `cdk`;";
        $rs = sql($sql);
        while ($tmp_arr = $rs->fetch_array()) {
            $tname = $tmp_arr['Field'];
            $tvalue = $tmp_arr['Default'];
            switch ($tname) {
                case 'cdk':
                    $tvalue = md5(time() . mt_rand(1, 100) . time() . 'cdk');
                    break;
            }
            echo <<<html
{$tname}:<input type="text" name="{$tname}"  value="{$tvalue}"><br>
html;
        }
        echo <<<html
<input type="submit" value="提交">
</form>
html;
    }
    sql("USE `{$GLOBALS['game_area_dbname']}`");
    cmd::addcmd('add_gz_cdk', '返回上级');
}

//添加cdk
function add_cdk1()
{
    $dbname = $GLOBALS['mysql_dbname'];
    $cdk = post::get('cdk');
    $rmb = post::get('rmb');
    if ($cdk) {
        $is_yuchong = post::get('is_yuchong');
        sql("USE `{$dbname}`");
        sql("LOCK TABLES `cdk`");
        sql("INSERT INTO `cdk` (`cdk`,`rmb`,`is_yuchong`,`event`,`game`) VALUES ('{$cdk}','{$rmb}',{$is_yuchong},'rq_e310','sanguo');");
        sql("UNLOCK TABLES `cdk`");
        echo "生成成功!";
        br();
    } else {
        $url = cmd::addcmd("add_cdk1", "兑换礼包", false, true);
        echo <<<cdkform
生成CDK
<form action="{$url}" method="post">
cdkform;
        sql("USE `{$dbname}`");
        $sql = "DESC `cdk`;";
        $rs = sql($sql);
        while ($tmp_arr = $rs->fetch_array()) {
            $tname = $tmp_arr['Field'];
            $tvalue = $tmp_arr['Default'];
            switch ($tname) {
                case 'cdk':
                    $tvalue = md5(time() . mt_rand(1, 100) . time() . 'cdk');
                    break;
            }
            echo <<<html
{$tname}:<input type="text" name="{$tname}"  value="{$tvalue}"><br>
html;
        }
        echo <<<html
<input type="submit" value="提交">
</form>
html;
    }
    sql("USE `{$GLOBALS['game_area_dbname']}`");
    cmd::addcmd('add_gz_cdk', '返回上级');
}

function add_cdk2()
{
    $dbname = $GLOBALS['mysql_dbname'];
    $cdk = post::get('cdk');
    $rmb = post::get('rmb');
    if ($cdk) {
        $is_yuchong = post::get('is_yuchong');
        sql("USE `{$dbname}`");
        sql("LOCK TABLES `cdk`");
        sql("INSERT INTO `cdk` (`cdk`,`rmb`,`is_yuchong`,`event`,`game`) VALUES ('{$cdk}','{$rmb}',{$is_yuchong},'xc_e310','sanguo');");
        sql("UNLOCK TABLES `cdk`");
        echo "生成成功!";
        br();
    } else {
        $url = cmd::addcmd("add_cdk2", "兑换礼包", false, true);
        echo <<<cdkform
生成CDK
<form action="{$url}" method="post">
cdkform;
        sql("USE `{$dbname}`");
        $sql = "DESC `cdk`;";
        $rs = sql($sql);
        while ($tmp_arr = $rs->fetch_array()) {
            $tname = $tmp_arr['Field'];
            $tvalue = $tmp_arr['Default'];
            switch ($tname) {
                case 'cdk':
                    $tvalue = md5(time() . mt_rand(1, 100) . time() . 'cdk');
                    break;
            }
            echo <<<html
{$tname}:<input type="text" name="{$tname}"  value="{$tvalue}"><br>
html;
        }
        echo <<<html
<input type="submit" value="提交">
</form>
html;
    }
    sql("USE `{$GLOBALS['game_area_dbname']}`");
    cmd::addcmd('add_gz_cdk', '返回上级');
}

function add_cdk3()
{
    $dbname = $GLOBALS['mysql_dbname'];
    $cdk = post::get('cdk');
    $rmb = post::get('rmb');
    if ($cdk) {
        $is_yuchong = post::get('is_yuchong');
        sql("USE `{$dbname}`");
        sql("LOCK TABLES `cdk`");
        sql("INSERT INTO `cdk` (`cdk`,`rmb`,`is_yuchong`,`event`,`game`) VALUES ('{$cdk}','{$rmb}',{$is_yuchong},'xc_e310_1','sanguo');");
        sql("UNLOCK TABLES `cdk`");
        echo "生成成功!";
        br();
    } else {
        $url = cmd::addcmd("add_cdk3", "兑换礼包", false, true);
        echo <<<cdkform
生成CDK
<form action="{$url}" method="post">
cdkform;
        sql("USE `{$dbname}`");
        $sql = "DESC `cdk`;";
        $rs = sql($sql);
        while ($tmp_arr = $rs->fetch_array()) {
            $tname = $tmp_arr['Field'];
            $tvalue = $tmp_arr['Default'];
            switch ($tname) {
                case 'cdk':
                    $tvalue = md5(time() . mt_rand(1, 100) . time() . 'cdk');
                    break;
            }
            echo <<<html
{$tname}:<input type="text" name="{$tname}"  value="{$tvalue}"><br>
html;
        }
        echo <<<html
<input type="submit" value="提交">
</form>
html;
    }
    sql("USE `{$GLOBALS['game_area_dbname']}`");
    cmd::addcmd('add_gz_cdk', '返回上级');
}

function add_cdk4()
{
    $dbname = $GLOBALS['mysql_dbname'];
    $cdk = post::get('cdk');
    $rmb = post::get('rmb');
    if ($cdk) {
        $is_yuchong = post::get('is_yuchong');
        sql("USE `{$dbname}`");
        sql("LOCK TABLES `cdk`");
        sql("INSERT INTO `cdk` (`cdk`,`rmb`,`is_yuchong`,`event`,`game`) VALUES ('{$cdk}','{$rmb}',{$is_yuchong},'xc_e310_2','sanguo');");
        sql("UNLOCK TABLES `cdk`");
        echo "生成成功!";
        br();
    } else {
        $url = cmd::addcmd("add_cdk4", "兑换礼包", false, true);
        echo <<<cdkform
生成CDK
<form action="{$url}" method="post">
cdkform;
        sql("USE `{$dbname}`");
        $sql = "DESC `cdk`;";
        $rs = sql($sql);
        while ($tmp_arr = $rs->fetch_array()) {
            $tname = $tmp_arr['Field'];
            $tvalue = $tmp_arr['Default'];
            switch ($tname) {
                case 'cdk':
                    $tvalue = md5(time() . mt_rand(1, 100) . time() . 'cdk');
                    break;
            }
            echo <<<html
{$tname}:<input type="text" name="{$tname}"  value="{$tvalue}"><br>
html;
        }
        echo <<<html
<input type="submit" value="提交">
</form>
html;
    }
    sql("USE `{$GLOBALS['game_area_dbname']}`");
    cmd::addcmd('add_gz_cdk', '返回上级');
}

//宠物 停机减少成长度
function minus_pet_chengzhang($time)
{
    $sql = "UPDATE game_pet SET chengzhangdu=chengzhangdu-$time WHERE master_mode=5";
    sql($sql);
}

//游戏流通货币总价值
function sum_game_money()
{
    $zmoney = 0;
    $rs = sql("SELECT SUM(CEIL(`value`)) FROM `game_user_value` WHERE `valuename` LIKE '%.i.1';");
    list($zlingshi) = $rs->fetch_row();
    $zlingshi = (int)$zlingshi;
    $user_arr = array();
    $lingshi_arr = array();
    $show_list = 1;
    $sql = "SELECT `id`,`name` FROM game_user WHERE 1";
    $rs = sql($sql);
    while (list($oid, $name) = $rs->fetch_row()) {
        if ($name) {
            $money = 0;
            $rs2 = sql("SELECT `valuename`,`value` FROM game_user_value WHERE userid=$oid AND valuename like '%.i.%' AND `value`!='0'");
            while (list($item, $count) = $rs2->fetch_row()) {
                $id = explode('.', $item);
                $id = $id[2];
                if ($id == 1) {
                    $lingshi_arr[$name] = $count;
                }
                $money += (int)($count * value::get_item_value($id, "money") * 0.5);
            }
            $money += item::get_zhp_money($oid);
            $money += item::get_money($oid);
            $user_arr[$name] = $money;
            $zmoney += $money;
        }
    }
    $e = (int)($zmoney / 100000000);
    $w = (int)($zmoney % 100000000 / 10000);
    echo "游戏流通货币总量:{$e}亿{$w}万个金币";
    br();
    $w = (int)($zlingshi / 10000);
    $q = (int)($zlingshi % 10000 / 1000);
    echo "游戏流通元宝总量:{$w}万{$q}千颗元宝";
    if ($show_list) {
        arsort($lingshi_arr);
        $i = 0;
        foreach ($lingshi_arr as $name => $lingshi) {
            br();
            $i++;
            echo "{$i}.{$name}:{$lingshi}颗元宝";
        }
        arsort($user_arr);
        $i = 0;
        foreach ($user_arr as $name => $money) {
            br();
            $i++;
            echo "{$i}.{$name}:{$money}个金币";
        }
    }
}

//生成npc宠物str
function get_npc_pet_str()
{
    $str = "";
    $sql = "SELECT
  `name`
FROM
  `pet`
WHERE
  `star` > 4
    AND `is_shengshou` = 0
    AND `is_xiongshou` = 0
    AND `is_xinchong` = 0
    AND `is_hechengchongwu` = 0
    AND `is_wushen` = 0
    AND `is_shanhaijing` = 0
    AND `is_baoxiang_open` = 1
ORDER BY RAND() DESC
LIMIT 1;";
    $rs = sql($sql);
    list($name) = $rs->fetch_row();
    $str .= $name . " ";
    $sql = "SELECT
  `name`
FROM
  `pet`
WHERE
  `star` > 4
    AND `is_xinchong` = 1
    AND `is_hechengchongwu` = 0
    AND `is_wushen` = 0
    AND `is_shanhaijing` = 0
ORDER BY RAND() DESC
LIMIT 1;";
    $rs = sql($sql);
    list($name) = $rs->fetch_row();
    $str .= $name . " ";
    $sql = "SELECT
  `name`
FROM
  `pet`
WHERE
  `star` > 4
    AND `is_hechengchongwu` = 1
    AND `is_wushen` = 0
    AND `is_shanhaijing` = 0
ORDER BY RAND() DESC
LIMIT 1;";
    $rs = sql($sql);
    list($name) = $rs->fetch_row();
    $str .= $name . " ";
    $sql = "SELECT
  `name`
FROM
  `pet`
WHERE
  `star` > 4 AND `is_shanhaijing` = 1
ORDER BY RAND() DESC
LIMIT 1;";
    $rs = sql($sql);
    list($name) = $rs->fetch_row();
    $str .= $name . " ";
    $sql = "SELECT
  `name`
FROM
  `pet`
WHERE
  `star` > 4 AND `is_longwangjiuzi` = 1
ORDER BY RAND() DESC
LIMIT 1;";
    $rs = sql($sql);
    list($name) = $rs->fetch_row();
    $str .= $name . " ";
    $sql = "SELECT
  `name`
FROM
  `pet`
WHERE
  `star` > 4 AND (`is_shengshou` = 1 OR `is_xiongshou` = 1)
ORDER BY RAND() DESC
LIMIT 1;";
    $rs = sql($sql);
    list($name) = $rs->fetch_row();
    $str .= $name . " ";
    $str = trim($str, " ");
    echo $str;
}

//高级模式
function admin_cmd()
{
    set_time_limit(0);
    echo "[高级模式]";
    br();
    //执行代码
    $code_str = $_POST['code_str'];
    if ($code_str) {
        if (mb_substr($code_str, -1) != ";") {
            $code_str .= ";";
        }
        echo "执行结果:true";
        br();
        eval($code_str);
        user::set("last_code_str", post::get("code_str"));
    } else {
        $code_str = user::get("last_code_str");
    }
    //代码片段
    $url = cmd::addcmd("admin_cmd", "", false, true);
    echo <<<html
    <form action="$url" method="post">
    请输入要执行的代码:
    <br>
    <textarea name="code_str" id="code" cols="30" rows="10">{$code_str}</textarea>
    <br>
    <input type="submit" value="提交代码">
    </form>
html;
    cmd::add_last_cmd("design_game");
}


//任意进入地图开关
function in_map_any()
{
    $in_map_any = value::get_user_value("in_map_any");
    value::set_user_value("in_map_any", $in_map_any ? 0 : 1);
    echo "你现在" . ($in_map_any ? "无法" : "可以") . "任意进入限制地图!";
    br();
    design_game();
}

//设计游戏
function design_game()
{
    echo '[地图系统]';
    br();
    cmd::addcmd('add_map', '创建地图');
    br();
    cmd::addcmd('add_map,1', '修改地图');
    br();
    echo "任意进入:";
    cmd::addcmd('in_map_any', value::get_user_value("in_map_any") ? "关" : "开");
    br();
    //NPC设计操作
    echo '[NPC系统]';
    br();
    cmd::addcmd('set_npc', 'NPC列表');
    br();
    cmd::addcmd('add_npc', '创建NPC');
    br();
    cmd::addcmd('add_npc2', '创建NPC2');
    br();
    //宠物设计操作
    echo '[宠物系统]';
    br();
    cmd::addcmd('set_pet', '宠物列表');
    br();
    cmd::addcmd('add_pet', '创建宠物');
    br();
    cmd::addcmd('e42', '进化系统');
    br();
    cmd::addcmd('e167', '合成系统');
    br();
    cmd::addcmd('e121', '数值系统');
    br();
    //技能设计操作
    echo '[技能系统]';
    br();
    cmd::addcmd('add_skill', '创建技能');
    br();
    cmd::addcmd('set_skill', '调整技能');
    br();
    //任务设计操作
    echo '[任务系统]';
    br();
    cmd::addcmd('add_task', '创建任务');
    br();
    cmd::addcmd('set_task', '调整任务');
    br();
    //数据管理操作
    echo '[数据系统]';
    br();
    cmd::addcmd('send_guangbo', '发送广播');
    br();
    cmd::addcmd('set_bbs_banzhu', '设置版主');
    br();
    cmd::addcmd('change_user_data', '调整数据');
    if (value::get_user_value("game_master") > 0) {
        br();
        cmd::addcmd('user_show_recharge', '流水数据');
        br();
        cmd::addcmd('admin_cmd', '高级模式');
    }
}

//游戏引擎
function game_engine($yeshu = -1)
{
    //表格
    $current_table_name = post::get("table");
    if (!$current_table_name) {
        $current_table_name = user::get("engine_table");
        if (!$current_table_name) {
            $current_table_name = "item";
        }
    } else {
        user::set("engine_table", $current_table_name);
    }
    //页数
    $p_yeshu = post::get("yeshu");
    if ($p_yeshu) {
        $yeshu = $p_yeshu - 1;
    }
    if ($yeshu < 0) {
        $yeshu = user::get("engine_yeshu");
    }
    //条件
    $condition = str_replace(";", "", $_POST["condition"]);
    if (!$condition) {
        $condition = user::get("engine_condition");
        if (!$condition) {
            $condition = "true";
        }
    } else {
        user::set("engine_condition", $condition);
        $yeshu = 0;
    }
    user::set("engine_yeshu", $yeshu);
    //游戏引擎
    $url = cmd::addcmd2url("game_engine");
    $rs = sql("SHOW TABLE STATUS;");
    while ($tmp_arr = $rs->fetch_array(MYSQLI_ASSOC)) {
        $table_info_arr[] = $tmp_arr;
    }
    echo <<<html
<form action="$url" method="post">
<select name="table" id="t">
<option value="{$current_table_name}">{$current_table_name}</option>
html;
    foreach ($table_info_arr as $table_info) {
        echo <<<html
<option value="{$table_info['Name']}">{$table_info['Comment']}</option>
html;
    }
    echo <<<html
</select>
<input type="submit" value="切换">
<br>
当前表格:{$current_table_name}
<br>
查询条件: <input type="text" name="condition" value="{$condition}">
<input type="submit" value="查询">
<br>
html;
    $sql = "SELECT COUNT(*) FROM {$current_table_name} WHERE {$condition}";
    $rs = sql($sql);
    if (!$GLOBALS['mysqli']->error) {
        list($count) = $rs->fetch_row();
        $danye_count = 15;
        $start_list_num = $yeshu * $danye_count;
        $sql = "SELECT * FROM  {$current_table_name} WHERE {$condition} LIMIT {$start_list_num},{$danye_count}";
        $rs = sql($sql);
        $i = 0;
        while ($t_row = $rs->fetch_array(MYSQLI_ASSOC)) {
            $i++;
            echo "{$t_row['name']}({$t_row['id']}) ";
            cmd::addcmd("game_engine_add_row,{$current_table_name},0,{$t_row['id']}", "复制");
            echo " ";
            cmd::addcmd("game_engine_update_row,{$current_table_name},{$t_row['id']}", "修改");
            echo " ";
            cmd::addcmd("game_engine", "删除");
            br();
        }
        if (!$i) {
            echo "没要找到数据!";
            br();
        }
        //显示上下页
        $fanye_ok = false;
        $max_page_num = (int)(($count - 1) / $danye_count) + 1;
        if ($yeshu > 0) {
            if ($yeshu > 1) {
                cmd::addcmd('game_engine,0', '首页');
                echo "|";
            }
            cmd::addcmd('game_engine,' . ($yeshu - 1), '上—页');
            $fanye_ok = true;
        }
        if ($count > $danye_count * ($yeshu + 1)) {
            if ($fanye_ok) {
                echo '|';
            }
            cmd::addcmd('game_engine,' . ($yeshu + 1), '下—页');
            if ($yeshu + 2 < $max_page_num) {
                echo "|";
                cmd::addcmd('game_engine,' . ($max_page_num - 1), '末页');
            }
            $fanye_ok = true;
        }
        //是否有操作
        if ($fanye_ok) {
            br();
            $show_yeshu = $yeshu + 1;
            echo <<<html
<input type="text" name="yeshu" style="width: 40px" value="{$show_yeshu}">
<input type="submit" value="跳转">
html;
        }
        echo <<<html
</form>
html;
    } else {
        echo "查询条件出错!";
        br();
    }
    cmd::addcmd("game_engine_add_row,{$current_table_name},0", "创建新行");
}

//游戏引擎 增加行
function game_engine_add_row($tablename, $step = 0, $old_id = 0)
{
    $table_name = $tablename;
    if ($old_id) {
        $rs = sql("SELECT * FROM {$table_name} WHERE `id`={$old_id} LIMIT 1");
        $old_id_row_arr = $rs->fetch_array(MYSQLI_ASSOC);
    }
    $rs = sql("SHOW FULL COLUMNS FROM `$table_name`");
    if (!$step) {
        $url = cmd::addcmd("game_engine_add_row,{$table_name},1", "", false, true);
        echo "<form action='{$url}' method='post'  id='form1'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            if ($old_id && mb_strtolower($col_Name) != 'id') {
                $col_Default = $old_id_row_arr[$col_Name];
            }
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='创建'>创建{$table_name}</button></form>";
        cmd::add_last_cmd("game_engine");
    } else {
        $col_Name_str = "";
        $col_Name_arr = array();
        $col_Value_str = "";
        $col_Value_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            array_push($col_Name_arr, $col_Name);
            array_push($col_Value_arr, post::get($col_Name));
        }
        foreach ($col_Name_arr as $col_Name_tmp_str) {
            $col_Name_str .= "`{$col_Name_tmp_str}`,";
        }
        foreach ($col_Value_arr as $col_Value_tmp_str) {
            if ($col_Value_tmp_str != "") {
                $col_Value_str .= "'{$col_Value_tmp_str}',";
            } else {
                $col_Value_str .= "NULL,";
            }
        }
        sql("INSERT INTO `$table_name` (" . trim($col_Name_str, ",") . ") VALUES (" . trim($col_Value_str, ",") . ")");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "创建成功";
            br();
        }
        game_engine();
        return;
    }
}

//游戏引擎 更新行
function game_engine_update_row($tablename, $old_id, $step = 0)
{
    $table_name = $tablename;
    $rs = sql("SELECT * FROM {$table_name} WHERE `id`={$old_id} LIMIT 1");
    $old_id_row_arr = $rs->fetch_array(MYSQLI_ASSOC);
    $rs = sql("SHOW FULL COLUMNS FROM `$table_name`");
    if (!$step) {
        $url = cmd::addcmd("game_engine_update_row,$table_name,$old_id,1", "修改任务", false, true);
        echo "<form action='{$url}' method='post' id='form1'>";
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Default = $old_id_row_arr[$col_Name];
            $col_Comment = $col_Comment ? $col_Comment : $col_Name;
            preg_match("/\d+/", $col_Type, $t_row);
            $t_length = $t_row[0];
            $t_row = (int)($t_length / 100);
            echo <<<input
{$col_Comment}:<textarea id="{$col_Name}" name="{$col_Name}" rows="{$t_row}" cols="20px" maxlength="{$t_length}">{$col_Default}</textarea><br>
input;
        }
        echo "<button type='submit' value='修改'>修改{$table_name}</button></form>";
        cmd::add_last_cmd("game_engine");
    } else {
        $col_Update_str = "";
        $col_Update_arr = array();
        while (list($col_Name, $col_Type, $col_Collation, $col_Null, $col_Key, $col_Default, $col_Extra, $col_Privileges, $col_Comment) = $rs->fetch_row()) {
            $col_Update_arr[$col_Name] = post::get($col_Name);
        }
        foreach ($col_Update_arr as $col_Name_tmp_str => $col_Name_tmp_value) {
            $col_Update_str .= "`{$col_Name_tmp_str}`='{$col_Name_tmp_value}',";
        }
        $col_Update_str = trim($col_Update_str, ',');
        sql("UPDATE `{$table_name}` SET $col_Update_str WHERE `id`=$old_id LIMIT 1");
        if ($GLOBALS['mysqli']->error) {
            echo $GLOBALS['mysqli']->error;
        } else {
            echo "更新成功";
            br();
        }
        game_engine_update_row($table_name, $old_id);
        return;
    }
}

//测试函数
function test()
{
    $i = 0;
    $sql = "SELECT `userid`,`value` FROM game_user_value WHERE valuename LIKE '%.used_lingshi'";
    $result = sql($sql);
    while (list($oid, $ovalue) = $result->fetch_row()) {
        $i++;
        $phname = value::get_game_user_value('name', $oid);
        echo $i . '.';
        if (value::get_game_user_value('is_online', $oid)) {
            cmd::addcmd('e12,' . $oid . ',15', $phname);
        } else {
            echo $phname;
        }
        echo '(' . $ovalue . ')';
        br();
        item::add_lingshi((int)($ovalue * 0.0315), false, $oid);
    }
}
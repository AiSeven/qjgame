<?php
//用户ID
$user_id = uid();
$npc_id = value::get_game_user_value('pk.npc.id', $user_id);
$wj_name = value::get_game_user_value('name', $user_id);
$npc_name = value::get_npc_value($npc_id, 'name');
//提取对战宠物ID
$u_pet_id = value::get_game_user_value('pk.now_pet.id', $user_id);
$o_pet_id = 0;
$npc_p_count = 0;
$npc_p_str = "";
$sql = "SELECT `id`,`name`,`lvl`,`is_dead` FROM `game_pet` WHERE `npc_id`=" . $npc_id . " AND `enemy_user`=" . $user_id . " ORDER BY `id` ASC LIMIT 0,5";
$result = sql($sql);
while (list($oid, $oname, $olvl, $odead) = $result->fetch_row()) {
    if (!$odead) {
        if (!$npc_p_count) {
            $o_pet_id = $oid;
            $npc_p_str = "{$olvl}级{$oname}";
        } else {
            $npc_p_str .= ",{$olvl}级{$oname}";
        }
        $npc_p_count++;
    }
}
if ((!$u_pet_id && user::get_game_user('is_dead', $user_id)) || !$o_pet_id) {
    e137();
    return;
}
//提取己方宠物数据
$sql = "SELECT `name`,`hp`,`max_hp`,`zhuangtai`,`lvl`,`texing`,`zhongcheng` FROM `game_pet` WHERE `id`=" . $u_pet_id . " LIMIT 1";
$result = sql($sql);
list($u_name, $u_hp, $u_max_hp, $u_zhuangtai, $u_lvl, $u_texing, $u_zhongcheng) = $result->fetch_row();
$u_shuxing = value::getvalue('pet', 'shuxing', 'id', value::get_pet_value($u_pet_id, 'pet_id'));
$u_max_hp = pet::get_max_hp($u_pet_id);
//反击天赋
$u_pk_shuxing = pet::get_shuxing_str(value::get_pet_value2('pk.shuxing', $u_pet_id, false));
if ($u_pk_shuxing) {
    $u_shuxing = $u_pk_shuxing;
}
//偷学天赋
$u_pk_texing = value::get_pet_value2('pk.texing', $u_pet_id, false);
if ($u_pk_texing) {
    $u_texing = $u_pk_texing - 1;
}
$u_pet_texing = explode(':', pet::get_texing($u_texing));
//提取对方宠物数据
$sql = "SELECT `name`,`hp`,`max_hp`,`zhuangtai`,`lvl`,`texing` FROM `game_pet` WHERE `id`=" . $o_pet_id . " LIMIT 1";
$result = sql($sql);
list($o_name, $o_hp, $o_max_hp, $o_zhuangtai, $o_lvl, $o_texing) = $result->fetch_row();
$o_shuxing = value::getvalue('pet', 'shuxing', 'id', value::get_pet_value($o_pet_id, 'pet_id'));
$o_max_hp = pet::get_max_hp($o_pet_id);
$image = value::getvalue('pet', 'image', 'id', value::get_pet_value($o_pet_id, 'pet_id'));
//反击天赋
$o_pk_shuxing = pet::get_shuxing_str(value::get_pet_value2('pk.shuxing', $o_pet_id, false));
if ($o_pk_shuxing) {
    $o_shuxing = $o_pk_shuxing;
}
//偷学天赋
$o_pk_texing = value::get_pet_value2('pk.texing', $o_pet_id, false);
if ($o_pk_texing) {
    $o_texing = $o_pk_texing - 1;
}
$o_pet_texing = explode(':', pet::get_texing($o_texing));
//宠物图片
if (!value::get_user_value('kg.xszdxx')) {
    if ($image != "") {
        pet::img($image, true, false);
        br();
    }
}
//战斗状况
$u = new game_user_object($user_id);
$uSkillMorenId1 = $u->get('next.skill.id1');
$uSkillMorenId2 = $u->get('next.skill.id2');
$uSkillMorenId3 = $u->get('next.skill.id3');
cmd::addcmd('e10010', '技能');
echo ' ';
if (!$uSkillMorenId1) {
    cmd::addcmd('e10014,1', '技能键');
}
if ($uSkillMorenId1) {
    cmd::addcmd('e10009,' . $uSkillMorenId1, value::get_skill_value($uSkillMorenId1, 'name'));
    echo ' ';
}
echo ' ';
if (!$uSkillMorenId2) {
    cmd::addcmd('e10014,2', '技能键');
}
if ($uSkillMorenId2) {
    cmd::addcmd('e10009,' . $uSkillMorenId2, value::get_skill_value($uSkillMorenId2, 'name'));
    echo ' ';
}
echo ' ';
if ($uSkillMorenId3) {
    cmd::addcmd('e10009,' . $uSkillMorenId3, value::get_skill_value($uSkillMorenId3, 'name'));
    echo ' ';
}
if (!$uSkillMorenId3) {
    cmd::addcmd('e10014,3', '技能键');
}
br();
cmd::addcmd('e10008', '物品');
echo "  ";
//快捷物品
for ($i = 0; $i < 3; $i++) {
    $kjwp_id = value::get_user_value('kjwp.' . $i . '.id', $user_id, false);
    if ($i) {
        echo '  ';
    }
    if ($kjwp_id && item::get_item($kjwp_id)) {
        cmd::addcmd("e10008,1,{$kjwp_id}", value::get_item_value($kjwp_id, 'name'));
    } else {
        cmd::addcmd("e10011,{$i}", '物品键');
        value::set_user_value("kjwp.{$i}.id", 0);
    }
}
br();
echo "<img src=df.png>:Lv" . $o_lvl . "-" . $o_name . "(" . $o_shuxing . ")";
br();
echo "生命:(" . (int)$o_hp . "/" . (int)$o_max_hp . ")";
br();
echo "<img src='br.jpg'>";
//己方属性
$sql = "SELECT * FROM `game_user` WHERE `id`={$user_id} LIMIT 1";
$rs2 = sql($sql);
$uv = $rs2->fetch_array(MYSQLI_ASSOC);
$uv['hp'] = intval($uv['hp']);
$uv['max_hp'] = user::get_max_hp($user_id);
br();
echo <<<html
<img src=wf.png>:Lv{$uv['lvl']}-{$uv['name']}
<br>
生命:({$uv['hp']}/{$uv['max_hp']})
<br>
魔法:({$uv['mp']}/{$uv['max_mp']})
<br>
html;
if ($u_pet_id && !pet::get($u_pet_id, 'is_dead')) {
    echo "Lv" . $u_lvl . "-" . $u_name . "(" . $u_shuxing . ")";
    br();
    echo "生命:(" . (int)$u_hp . "/" . (int)$u_max_hp . ")";
    br();
}
//下回合技能
$u_next_skill_id1 = value::get_pet_value2('pk.next_skill.id1', $u_pet_id);
$u_next_skill_id2 = value::get_pet_value2('pk.next_skill.id2', $u_pet_id);
$u_next_skill_id3 = value::get_pet_value2('pk.next_skill.id3', $u_pet_id);
//输出命令
cmd::addcmd('e94,0,' . $u_pet_id, '宠技');
echo "  ";
cmd::addcmd('e92', '宠物');
echo "  ";
cmd::addcmd('e91', '交换');
echo ' ';
cmd::addcmd('jn_e96', '设置');
echo ' ';
cmd::addcmd('e93', '逃跑');
br();
echo "宠:";
//默认技能
if (!$u_next_skill_id1) {
    cmd::addcmd('e94,1,' . $u_pet_id, '技能键');
}
if ($u_next_skill_id1) {
    $u_next_skill_name1 = value::get_skill_value($u_next_skill_id1, 'name');
    $u_next_skill_pp1 = value::get_pet_value2('skill.' . $u_next_skill_id1 . '.pp', $u_pet_id);
    cmd::addcmd('e94,4,' . $u_pet_id . ',' . $u_next_skill_id1, $u_next_skill_name1 . '(' . $u_next_skill_pp1 . ')');
    echo "  ";
}
echo ' ';
if (!$u_next_skill_id2) {
    cmd::addcmd('e94,2,' . $u_pet_id, '技能键');
}
if ($u_next_skill_id2) {
    $u_next_skill_name2 = value::get_skill_value($u_next_skill_id2, 'name');
    $u_next_skill_pp2 = value::get_pet_value2('skill.' . $u_next_skill_id2 . '.pp', $u_pet_id);
    cmd::addcmd('e94,5,' . $u_pet_id . ',' . $u_next_skill_id2, $u_next_skill_name2 . '(' . $u_next_skill_pp2 . ')');
    echo "  ";
}
echo ' ';
if (!$u_next_skill_id3) {
    cmd::addcmd('e94,3,' . $u_pet_id, '技能键');
}
if ($u_next_skill_id3) {
    $u_next_skill_name3 = value::get_skill_value($u_next_skill_id3, 'name');
    $u_next_skill_pp3 = value::get_pet_value2('skill.' . $u_next_skill_id3 . '.pp', $u_pet_id);
    cmd::addcmd('e94,6,' . $u_pet_id . ',' . $u_next_skill_id3, $u_next_skill_name3 . '(' . $u_next_skill_pp3 . ')');
    echo "  ";
}
br();
//快捷物品
echo "宠:";
for ($i = 3; $i < 7; $i++) {
    $kjwp_id = value::get_user_value('kjwp.' . $i . '.id', $user_id, false);
    if ($i) {
        echo '  ';
    }
    if ($kjwp_id && item::get_item($kjwp_id)) {
        cmd::addcmd("e92,1," . $kjwp_id, value::get_item_value($kjwp_id, 'name'));
    } else {
        cmd::addcmd("e95,{$i}", '物品键');
    }
}
br();
if (!value::get_user_value('kg.xszdxx')) {
    //显示战斗信息
    $obj = new game_user_object($user_id);
    $pk_chats = $obj->get("pk_chats", "string");
    if ($pk_chats) {
        echo $pk_chats;
        $obj->del("pk_chats");
    }
}
cmd::set_show_return_game(false);

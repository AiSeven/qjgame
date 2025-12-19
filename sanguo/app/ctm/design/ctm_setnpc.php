<?php
//返回地图
cmd::addcmd('e5', '返回地图');
br();
?>
    <form action="<?php
    echo "game.php?sid=" . $GLOBALS['sid'] . "&cmd=" . cmd::addcmd('set_npc', '操作', false); ?>" method="post"
          autocomplete="on">
        <span>区域:</span>
        <select name="area_id" title="区域">
            <option value="1">银杏村</option>
            <option value="2">银杏山谷野外</option>
            <option value="3">银杏废矿</option>
            <option value="4">比奇</option>
            <option value="5">比奇北郊外</option>
            <option value="6">比奇南郊外</option>
            <option value="7">半兽洞穴</option>
            <option value="8">废矿矿山入口</option>
            <option value="9">地下1层第二间房</option>
            <option value="10">废矿东部洞穴</option>
            <option value="11">银杏村</option>
            <option value="12">银杏山谷野外</option>
            <option value="13">稻山野林</option>
            <option value="14">比奇</option>
            <option value="15">比奇北郊外</option>
            <option value="16">比奇南郊外</option>
            <option value="17">半兽洞穴</option>
            <option value="18">废矿矿山入口</option>
            <option value="19">地下1层第二间房</option>
            <option value="20">废矿东部洞穴</option>
        </select>
        <input name='submit' type='submit' title='进入' value='进入' style='margin-top: 5px;width:50px;height: 24px;'/>
    </form>
<?php
//获取地图id npc文本
$in_map_id = value::get_game_user_value('in_map_id');
$area_id = value::getvalue('map', 'area_id', 'id', $in_map_id);
$npc = explode(',', value::getvalue('map', 'npc', 'id', $in_map_id));
if (!$_POST['area_id']) {
//获取当前区域npc
    $sql = "SELECT `id`, `name`, `desc`, `sex`, `talk` FROM `npc` WHERE `area_id`=" . $area_id . " ORDER BY `id` ASC";
} else {
    $sql = "SELECT `id`, `name`, `desc`, `sex`, `talk` FROM `npc` WHERE `area_id`=" . $_POST['area_id'] . " ORDER BY `id` ASC";
}
$result = sql($sql);
while (list($id, $name, $desc, $sex, $talk) = $result->fetch_row()) {
    $have = false;
    echo $name . '(n' . $id . ')';
    for ($i = 0; $i < count($npc); $i++) {
        if ($id == $npc[$i]) {
            $have = true;
        }
    }
    if (!$have) {
        echo " ";
        cmd::addcmd('map_add_npc,' . $id, '放置');
    } else {
        echo " ";
        $cmd = cmd::addcmd('map_del_npc,' . $id, '移除', false);
        echo "<a href='game.php?sid=" . $GLOBALS['sid'] . "&cmd=" . $cmd . "'><span style='color:#ff0000'>移除</span></a>";
    }
    echo " ";
    cmd::addcmd('add_npc,' . $id, '修改');
    echo " ";
    cmd::addcmd('npc_set_pet,' . $id, '宠物');
    echo " ";
    cmd::addcmd('change_npc,' . $id, '修改2');
    echo " ";
    cmd::addcmd("add_npc2,0,{$id}", "复制");
    echo " ";
    cmd::addcmd('del_npc,' . $id, '删除');
    br();
    $sql2 = "SELECT `name` FROM `map` WHERE `npc` LIKE '" . $id . "' OR `npc` LIKE '" . $id . ",%' OR `npc` LIKE '%," . $id . ",%' OR `npc` LIKE '%," . $id . "'";
    $result2 = sql($sql2);
    $map_str = "";
    $c = 0;
    while (list($map_name) = $result2->fetch_row()) {
        $c++;
        if ($c == 1) {
            $map_str = $map_name;
        } else {
            $map_str = $map_str . " " . $map_name;
        }
    }
    if ($map_str) {
        echo '地图:<span style=\'color:#ff6100;\'>' . $map_str . '</span>';
        br();
    } else {
        echo '地图:<span style=\'color:#9f79ee;\'>未放置</span>';
        br();
    }
    echo '性别:' . $sex;
    br();
    echo '描述:' . $desc;
    br();
    echo '对话:' . $talk;
    br();
    $pet_id_str = value::get_npc_value($id, 'pet_id');
    if ($pet_id_str) {
        $pet_str = "";
        $pet_lvl_str = value::get_npc_value($id, 'pet_lvl');
        $pet_id_arr = explode(',', $pet_id_str);
        $pet_lvl_arr = explode(',', $pet_lvl_str);
        $id_count = count($pet_id_arr);
        for ($i = 0; $i < $id_count; $i++) {
            $pet_name = value::getvalue('pet', 'name', 'id', $pet_id_arr[$i]);
            if ($pet_id_arr[$i] == 138 || $pet_id_arr[$i] == 139) {
                $pet_name .= " ";
            }
            if ($i) {
                $pet_str .= ",<span style='color: #ff0000'>" . $pet_lvl_arr[$i] . "</span>级<span style='color: #ff0000'>" . $pet_name . "</span>";
            } else {
                $pet_str .= "<span style='color: #ff0000'>" . $pet_lvl_arr[$i] . "</span>级<span style='color: #ff0000'>" . $pet_name . "</span>";
            }
        }
        echo '宠物:' . $pet_str;
        br();
    }
}
?>
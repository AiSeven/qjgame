<?php
//返回地图
cmd::addcmd('e5', '返回地图');
br();
?>
<form action="<?php
echo "game.php?sid=" . $GLOBALS['sid'] . "&cmd=" . cmd::addcmd('set_pet', '操作', false); ?>" method="post"
      autocomplete="on">
    <span>星级:</span>
    <select id="star" name="star">
        <option value="1">一星</option>
        <option value="2">二星</option>
        <option value="3">三星</option>
        <option value="4">四星</option>
        <option value="5">五星</option>
    </select>
    <select id="shuxing" name="shuxing">
        <option value="1">全</option>
        <option value="2">金</option>
        <option value="3">木</option>
        <option value="4">水</option>
        <option value="5">火</option>
        <option value="6">土</option>
    </select>
    <input name='submit' type='submit' title='进入' value='进入' style='margin-top: 5px;width:50px;height: 24px;'/>
</form>
<?php
//获取地图id 宠物文本
$in_map_id = value::get_game_user_value('in_map_id');
$yaoguai = explode(',', value::getvalue('map', 'yaoguai', 'id', $in_map_id));
$star = value::real_escape_string($_POST['star']);
if (!$star) {
    $star = value::get_user_value('set_pet_star', 0, false);
} else {
    value::set_user_value('set_pet_star', $star);
}
if (!$star) {
    if ($GLOBALS['t_arr'][0] > 1) {
        $star = $GLOBALS['t_arr'][0];
    } else {
        $star = 1;
    }
}
$add_sql = "";
$shuxing = value::real_escape_string($_POST['shuxing']);
if (!$shuxing) {
    $shuxing = value::get_user_value('set_pet_shuxing', 0, false);
} else {
    value::set_user_value('set_pet_shuxing', $shuxing);
}
if ($shuxing > 1) {
    $shuxing_arr = array("金", "木", "水", "火", "土");
    $add_sql = "AND `shuxing` LIKE '%" . $shuxing_arr[$shuxing - 2] . "%'";
}
$sql = "SELECT `id`, `name`, `desc`, `image`,((`hp`/2+`pugong`+`pufang`+`tegong`+`tefang`)*minjie) FROM `pet` WHERE `star`=" . $star . " $add_sql ORDER BY ((`hp`/2+`pugong`+`pufang`+`tegong`+`tefang`)*minjie),`id` ASC";
$result = sql($sql);
while (list($id, $name, $desc, $image, $liuwei) = $result->fetch_row()) {
    $have = false;
    echo $name . '(p' . $id . ')';
    for ($i = 0; $i < count($yaoguai); $i++) {
        if ($id == $yaoguai[$i]) {
            $have = true;
        }
    }
    if (!$have) {
        echo " ";
        cmd::addcmd('map_add_pet,' . $id, '放置');
    } else {
        echo " ";
        $cmd = cmd::addcmd('map_del_pet,' . $id, '移除', false);
        echo "<a href='game.php?sid=" . $GLOBALS['sid'] . "&cmd=" . $cmd . "'><span style='color:#ff0000'>移除</span></a>";
    }
    echo " ";
    $cmd = cmd::addcmd('pet_set_skill,' . $id, '技能');
    echo " ";
    $cmd = cmd::addcmd('change_pet,' . $id, '修改');
    br();
    pet::img($image, true, false);
    br();
    //显示属性
    $shuxing_str = value::getvalue('pet', 'shuxing', 'id', $id);
    echo "属性:{$shuxing_str}";
    br();
    echo "六维:", number_format($liuwei, 1), "";
    br();
    //显示技能
    $study_lvl = value::getvalue('pet', 'study_lvl', 'id', $id);
    $study_skill_str = value::getvalue('pet', 'study_skill', 'id', $id);
    $study_skill_arr = explode(',', $study_skill_str);
    $study_skill_count = count($study_skill_arr);
    $skill_str = '';
    for ($i = 0; $i < $study_skill_count; $i++) {
        $sstudy_lvl = $i * $study_lvl;
        $sstudy_lvl = $sstudy_lvl > 0 ? $sstudy_lvl : 1;
        if ($study_skill_arr[$i]) {
            $leixing = value::get_skill_value($study_skill_arr[$i], 'leixing');
            if ($leixing > 2) {
                $color = "666666";
            } else {
                $color = "0000ff";
            }
            if ($i) {
                $skill_str .= " <span style='color:#{$color}'>" . value::get_skill_value($study_skill_arr[$i], 'name') . "({$sstudy_lvl}级)</span>";
            } else {
                $skill_str = "<span style='color:#{$color}'>" . value::get_skill_value($study_skill_arr[$i], 'name') . "({$sstudy_lvl}级)</span>";
            }
        }
    }
    if ($skill_str) {
        echo '技能:' . $skill_str;
        if ($i > 8) {
            echo "✔";
        }
        br();
    } else {
        echo '技能:<span style=\'color:#9f79ee;\'>未学习</span>';
        br();
    }
    //显示所在地图
    $sql2 = "SELECT DISTINCT `name` FROM `map` WHERE `yaoguai` LIKE '" . $id . "' OR `yaoguai` LIKE '" . $id . ",%' OR `yaoguai` LIKE '%," . $id . ",%' OR `yaoguai` LIKE '%," . $id . "'";
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
    //显示描述
    echo '描述:' . $desc;
    br();
}
?>
<script>
    var select = document.getElementById('star');
    select.options[<?php echo $star - 1; ?>].selected = true;
    var select = document.getElementById('shuxing');
    select.options[<?php echo $shuxing - 1; ?>].selected = true;
</script>

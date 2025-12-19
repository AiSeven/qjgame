<?php
//返回地图
cmd::addcmd('e5', '返回地图');
br();
?>
<form action="<?php
echo "game.php?sid=" . $GLOBALS['sid'] . "&cmd=" . cmd::addcmd('set_skill', '操作', false); ?>" method="post"
      autocomplete="on">
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
<?php
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
$sql = "SELECT  `id`,`name`, `miaoshu`, `shuxing`,`fangshi`, `leixing`, `mingzhong`, `weili`, `pp`, `zhongji`, `yczt`, `ycztmz`, `min_lianji`, `max_lianji`,(`weili`* `mingzhong`/ 100/ `fangshi`* (1 + `zhongji` / 100)* (`max_lianji` + `min_lianji`)/ 2) FROM `skill` WHERE $add_sql ORDER BY (`weili`* `mingzhong`/ 100/ `fangshi`* (1 + `zhongji` / 100)* (`max_lianji` + `min_lianji`)/ 2),`mingzhong`";
$result = sql($sql);
while (list($s_id, $sname, $smiaoshu, $sshuxing, $sfangshi, $sleixing, $smingzhong, $sweili, $spp, $szhongji, $syczt, $sycztmz, $smin_lianji, $smax_lianji, $zhenshiweili) = $result->fetch_row()) {
    $sleixing = skill::get_skill_leixing($s_id);
    if (strstr($sleixing, '攻')) {
        $sleixing = "<span style='color:#ff00ff'>" . $sleixing . "</span>";
    }
    echo "[{$sshuxing}]<span style='color:#ff0000'>{$sname}</span>(PP:{$spp})" . $sleixing . ' ';
    cmd::addcmd('change_skill,' . $s_id, '修改');
    br();
    $zhenshiweili = (int)$zhenshiweili;
    echo '描述:' . skill::get_skill_desc($s_id) . '<br>' . "威力:{$sweili} 命中:{$smingzhong}<br>重击:{$szhongji} 连击:{$smin_lianji}-{$smax_lianji}次<br>真实威力:{$zhenshiweili}";
    br();
}
?>
<script>
    var select = document.getElementById('fangshi');
    select.options[<?php echo $fangshi - 1; ?>].selected = true;
    var select = document.getElementById('shuxing');
    select.options[<?php echo $shuxing - 1; ?>].selected = true;
    var select = document.getElementById('leixing');
    select.options[<?php echo $leixing - 1; ?>].selected = true;
</script>

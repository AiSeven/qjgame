<?php
//方向
$direction = $GLOBALS['t_arr'][0];
//确认
$q_ok = $GLOBALS['t_arr'][1];
$in_map_id = value::get_game_user_value('in_map_id');
$in_map_name = value::getvalue('map', 'name', 'id', $in_map_id);
$link_map_id = value::getvalue('map', $direction, 'id', $in_map_id);
$link_map_name = value::getvalue('map', 'name', 'id', $link_map_id);
if ($direction == 'exit_b') {
    $dname = "北";
    $n_direction = 'exit_n';
} else if ($direction == 'exit_x') {
    $dname = "西";
    $n_direction = 'exit_d';
} else if ($direction == 'exit_d') {
    $dname = "东";
    $n_direction = 'exit_x';
} else {
    $dname = "南";
    $n_direction = 'exit_b';
}
if ($q_ok && $direction) {
    $sql = "UPDATE `map` SET `" . $direction . "`='0' WHERE `id`='" . $in_map_id . "'";
    sql($sql);
    $sql = "UPDATE `map` SET `" . $n_direction . "`='0' WHERE `id`='" . $link_map_id . "'";
    sql($sql);
    echo "<b style='color:#ff0000'>断开成功</b>";
    br();
    e5();
    return;
}
echo "<span style='color:#ff0000'>确定要<b>断开</b>(s" . $in_map_id . ")" . $in_map_name . "与" . $dname . "边" . "(s" . $link_map_id . ")" . $link_map_name . "的连接吗?</span>";
br();
cmd::addcmd('cut_map,' . $direction . ',1', '<b>确定断开</b>');

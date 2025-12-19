<?php
$in_map_id = value::get_game_user_value('in_map_id');
//获取参数 1=批量修改
$cmd = $GLOBALS['t_arr'][0];
$in_map_name = value::getvalue('map', 'name', 'id', $in_map_id);
$in_map_desc = value::getvalue('map', 'desc', 'id', $in_map_id);
$in_area_id = value::getvalue('map', 'area_id', 'id', $in_map_id);
//默认描述
if ($_POST['desc']) {
    $desc = value::real_escape_string($_POST['desc']);
} else {
    $desc = '这里是' . $_POST['name'] . '。';
}
//处理数据
if ($_POST['name'] && $desc) {
    //获取地图数据
    $area_id = value::real_escape_string($_POST['area_id']);
    $name = value::real_escape_string($_POST['name']);
    $lvl = value::real_escape_string($_POST['lvl']);
    $map_kd = value::real_escape_string($_POST['kd']);
    $map_zd = value::real_escape_string($_POST['zd']);
    $cj_id = value::real_escape_string($_POST['cj_id']);
    $yaoguai = value::real_escape_string($_POST['yaoguai']);
    $is_pk = value::real_escape_string($_POST['is_pk']);
    $is_danrenfuben = value::real_escape_string($_POST['is_danrenfuben']);
    $is_duorenfuben = value::real_escape_string($_POST['is_duorenfuben']);
    $is_cunzhangjia = value::real_escape_string($_POST['is_cunzhangjia']);
    $is_fengyaoguan = value::real_escape_string($_POST['is_fengyaoguan']);
    $is_yizhan = value::real_escape_string($_POST['is_yizhan']);
    $is_zahuopu = value::real_escape_string($_POST['is_zahuopu']);
    $is_xiuxingchang = value::real_escape_string($_POST['is_xiuxingchang']);
    $is_jianyu = value::real_escape_string($_POST['is_jianyu']);
    $is_gonghuilingdi = value::real_escape_string($_POST['is_gonghuilingdi']);
    $is_chalou = value::real_escape_string($_POST['is_chalou']);
    $is_jingjichang = value::real_escape_string($_POST['is_jingjichang']);
    $fanwei = value::real_escape_string($_POST['fanwei']);
    $count = value::real_escape_string($_POST['count']);
    $exit_b = 0;
    $exit_n = 0;
    $exit_x = 0;
    $exit_d = 0;
    $map_count = $cj_id;
    $i2 = 1;
    //开始增加地图
    if ($cmd != 1) {
        if ($map_count > 0 && $count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $i1++;
                if ($i1 > $map_kd) {
                    $i1 = 1;
                    if ($i2 < $map_zd) {
                        $i2 += 1;
                    }
                }
                if ($i2 > 1) {
                    $exit_b = $map_count + $i - $map_kd + 1;
                    $exit_b1 = $map_count + $count;
                    if ($exit_b < $map_count || $exit_b > $exit_b1) {
                        $exit_b = 0;
                    }
                }
                if ($i2 != $map_zd && $i2 < $map_zd) {
                    $exit_n = $map_count + $i + $map_kd + 1;
                    $exit_n1 = $map_count + $count;
                    if ($exit_n < $map_count || $exit_n > $exit_n1) {
                        $exit_n = 0;
                    }
                }
                if ($i2 == $map_zd) {
                    $exit_n = 0;
                }
                if ($i1 > 1) {
                    $exit_x = $map_count + $i;
                    $exit_x1 = $map_count + $count;
                    if ($exit_x < $map_count || $exit_x > $exit_x1) {
                        $exit_x = 0;
                    }
                }
                if ($i1 == 1) {
                    $exit_x = 0;
                }
                if ($i1 != $map_kd && $i1 < $map_kd) {
                    $exit_d = $map_count + $i + 2;
                    $exit_d1 = $map_count + $count;
                    if ($exit_d < $map_count || $exit_d > $exit_d1) {
                        $exit_d = 0;
                    }
                }
                if ($i1 == $map_kd) {
                    $exit_d = 0;
                }
                $name1 = $name . "(" . $i2 . "," . $i1 . ")";
                $sql = "INSERT INTO `map`(`area_id`, `exit_b`, `exit_n`, `exit_x`, `exit_d`, `name`, `desc`, `lvl`, `yaoguai`, `is_pk`, `is_danrenfuben`, `is_duorenfuben`, `is_cunzhangjia`, `is_fengyaoguan`, `is_yizhan`, `is_zahuopu`,`is_xiuxingchang`, `is_jianyu`, `is_gonghuilingdi`,`is_chalou`,`is_jingjichang`) VALUES ('" . $area_id . "','" . $exit_b . "','" . $exit_n . "','" . $exit_x . "','" . $exit_d . "','" . $name1 . "','" . $desc . "','" . $lvl . "','" . $yaoguai . "','" . $is_pk . "','" . $is_danrenfuben . "','" . $is_duorenfuben . "','" . $is_cunzhangjia . "','" . $is_fengyaoguan . "','" . $is_yizhan . "','" . $is_zahuopu . "','" . $is_xiuxingchang . "','" . $is_jianyu . "','" . $is_gonghuilingdi . "','" . $is_chalou . "','" . $is_jingjichang . "')";
                sql($sql);
            }
            echo "<b style='color:#0000ff'>成功创建" . $count . "张" . $name . "地图!</b>";
            br();
        }
        if ($map_count < 1 && $count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $name1 = $name . $i . "层";
                $exit_d = 0;
                $exit_n = 0;
                $exit_x = 0;
                $exit_d = 0;
                $sql = "INSERT INTO `map`(`area_id`, `exit_b`, `exit_n`, `exit_x`, `exit_d`, `name`, `desc`, `lvl`, `yaoguai`, `is_pk`, `is_danrenfuben`, `is_duorenfuben`, `is_cunzhangjia`, `is_fengyaoguan`, `is_yizhan`, `is_zahuopu`,`is_xiuxingchang`, `is_jianyu`, `is_gonghuilingdi`,`is_chalou`,`is_jingjichang`) VALUES ('" . $area_id . "','" . $exit_b . "','" . $exit_n . "','" . $exit_x . "','" . $exit_d . "','" . $name1 . "','" . $desc . "','" . $lvl . "','" . $yaoguai . "','" . $is_pk . "','" . $is_danrenfuben . "','" . $is_duorenfuben . "','" . $is_cunzhangjia . "','" . $is_fengyaoguan . "','" . $is_yizhan . "','" . $is_zahuopu . "','" . $is_xiuxingchang . "','" . $is_jianyu . "','" . $is_gonghuilingdi . "','" . $is_chalou . "','" . $is_jingjichang . "')";
                sql($sql);
            }
            echo "<b style='color:#0000ff'>成功创建" . $count . "张" . $name . "地图!</b>";
            br();
        }
    } else {
        //批量修改地图
        if ($fanwei == 0) {
            $sql = "UPDATE `map` SET `area_id`=" . $area_id . ",`name`='" . $count . "',`desc`='" . $desc . "',`lvl`=" . $lvl . ",`yaoguai`='" . $yaoguai . "',`is_pk`=" . $is_pk . ",`is_danrenfuben`=" . $is_danrenfuben . ",`is_duorenfuben`=" . $is_duorenfuben . ",`is_cunzhangjia`=" . $is_cunzhangjia . ",`is_fengyaoguan`=" . $is_fengyaoguan . ",`is_yizhan`=" . $is_yizhan . ",`is_zahuopu`=" . $is_zahuopu . ",`is_xiuxingchang`=" . $is_xiuxingchang . ",`is_jianyu`=" . $is_jianyu . ",`is_gonghuilingdi`=" . $is_gonghuilingdi . ",`is_chalou`=" . $is_chalou . ",`is_jingjichang`=" . $is_jingjichang . " WHERE `name`='" . $name . "' AND `area_id`='" . $in_area_id . "'";
            $biaoshu = "此区域";
        } else if ($fanwei == 1) {
            $sql = "UPDATE `map` SET `area_id`=" . $area_id . ",`name`='" . $count . "',`desc`='" . $desc . "',`lvl`=" . $lvl . ",`yaoguai`='" . $yaoguai . "',`is_pk`=" . $is_pk . ",`is_danrenfuben`=" . $is_danrenfuben . ",`is_duorenfuben`=" . $is_duorenfuben . ",`is_cunzhangjia`=" . $is_cunzhangjia . ",`is_fengyaoguan`=" . $is_fengyaoguan . ",`is_yizhan`=" . $is_yizhan . ",`is_zahuopu`=" . $is_zahuopu . ",`is_xiuxingchang`=" . $is_xiuxingchang . ",`is_jianyu`=" . $is_jianyu . ",`is_gonghuilingdi`=" . $is_gonghuilingdi . ",`is_chalou`=" . $is_chalou . ",`is_jingjichang`=" . $is_jingjichang . " WHERE `id`='" . $in_map_id . "'";
            $biaoshu = "当前";
        } else if ($fanwei == 2) {
            $sql = "UPDATE `map` SET `area_id`=" . $area_id . ",`name`='" . $count . "',`desc`='" . $desc . "',`lvl`=" . $lvl . ",`yaoguai`='" . $yaoguai . "',`is_pk`=" . $is_pk . ",`is_danrenfuben`=" . $is_danrenfuben . ",`is_duorenfuben`=" . $is_duorenfuben . ",`is_cunzhangjia`=" . $is_cunzhangjia . ",`is_fengyaoguan`=" . $is_fengyaoguan . ",`is_yizhan`=" . $is_yizhan . ",`is_zahuopu`=" . $is_zahuopu . ",`is_xiuxingchang`=" . $is_xiuxingchang . ",`is_jianyu`=" . $is_jianyu . ",`is_gonghuilingdi`=" . $is_gonghuilingdi . ",`is_chalou`=" . $is_chalou . ",`is_jingjichang`=" . $is_jingjichang . " WHERE `name`='" . $name . "'";
            $biaoshu = "所有";
        } else if ($fanwei == 3) {
            $sql = "UPDATE `map` SET `area_id`=" . $area_id . ",`desc`='" . $desc . "',`lvl`=" . $lvl . ",`yaoguai`='" . $yaoguai . "',`is_pk`=" . $is_pk . ",`is_danrenfuben`=" . $is_danrenfuben . ",`is_duorenfuben`=" . $is_duorenfuben . ",`is_cunzhangjia`=" . $is_cunzhangjia . ",`is_fengyaoguan`=" . $is_fengyaoguan . ",`is_yizhan`=" . $is_yizhan . ",`is_zahuopu`=" . $is_zahuopu . ",`is_xiuxingchang`=" . $is_xiuxingchang . ",`is_jianyu`=" . $is_jianyu . ",`is_gonghuilingdi`=" . $is_gonghuilingdi . ",`is_chalou`=" . $is_chalou . ",`is_jingjichang`=" . $is_jingjichang . " WHERE `area_id`='" . $in_area_id . "'";
            $biaoshu = "批量创建地图添加怪物";
        }
        sql($sql);
        echo "<b style='color:#0000ff'>成功修改" . $biaoshu . $name . "地图!</b>";
        br();
    }
}
?>
    <form action="<?php
    echo "game.php?sid=" . $GLOBALS['sid'] . "&cmd=" . cmd::addcmd('add_map,' . $cmd, '操作', false); ?>" method="post"
          autocomplete="on">
        <span>区域:</span>
        <select name="area_id" id="area">
            <?php
            $map_area_name_arr = config::getConfigByName("map_area_name");
            foreach ($map_area_name_arr as $k => $v) {
                echo "<option value=\"{$k}\">$v</option>";
            }
            ?>
        </select><br>
        <span>名称:</span>
        <input type="text" name="name" <?php echo "value='" . $in_map_name . "'";
        if ($cmd == 1) {
            echo "readonly='readonly'";
        } ?>><br>
        <?php
        if ($cmd == 1) {
            echo "<span>新名: </span><input type=\"text\" name=\"count\" value=\"" . $in_map_name . "\">";
            br();
            echo "范围: <select name=\"fanwei\"><option value=\"0\">区域</option><option value=\"1\">单张</option><option value=\"2\">全部</option><option value=\"3\">批量地图添加怪物</option></select>";
            br();
        }
        ?>
        <span>描述:</span>
        <textarea name="desc" style="width:167px;height:50px;"><?php echo $in_map_desc; ?></textarea><br>
        <span>宠物等级:</span>
        <input type="text" name="lvl" value="<?php echo value::getvalue('map', 'lvl', 'id', $in_map_id); ?>"><br>
        <span>放置宠物:</span>
        <input type="text" name="yaoguai"
               value="<?php echo value::getvalue('map', 'yaoguai', 'id', $in_map_id); ?>"><br>
        <span>可PK:</span>
        <select name="is_pk" id="is_pk">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否单人副本:</span>
        <select name="is_danrenfuben" id="is_danrenfuben">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否多人副本:</span>
        <select name="is_duorenfuben" id="is_duorenfuben">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否村长家:</span>
        <select name="is_cunzhangjia" id="is_cunzhangjia">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否仓库:</span>
        <select name="is_fengyaoguan" id="is_fengyaoguan">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否驿站:</span>
        <select name="is_yizhan" id="is_yizhan">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否杂货铺:</span>
        <select name="is_zahuopu" id="is_zahuopu">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否修行场:</span>
        <select name="is_xiuxingchang" id="is_xiuxingchang">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否监狱:</span>
        <select name="is_jianyu" id="is_jianyu">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否帮派领地:</span>
        <select name="is_gonghuilingdi" id="is_gonghuilingdi">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否茶楼:</span>
        <select name="is_chalou" id="is_chalou">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>是否竞技场:</span>
        <select name="is_jingjichang" id="is_jingjichang">
            <option value="0">否</option>
            <option value="1">是</option>
        </select><br>
        <span>宽度:</span>
        <input type="text" name="kd" value="<?php echo value::getvalue('map', 'kd', 'id', $in_map_id); ?>"><br>
        <span>长度:</span>
        <input type="text" name="zd" value="<?php echo value::getvalue('map', 'zd', 'id', $in_map_id); ?>"><br>
        <span>创建ID:</span>
        <input type="text" name="cj_id" value="<?php echo value::getvalue('map', 'cj_id', 'id', $in_map_id); ?>"><br>
        <?php
        if ($cmd != 1) {
            echo "<span>创建几张地图:</span><input type=\"text\" name=\"count\" value=\"1\"><br><input name='submit' type='submit' title='增加' value='增加' style='margin-top: 5px;width:80px;height: 25px;'/>";
        } else {
            echo "<input name='submit' type='submit' title='修改' value='修改' style='margin-top: 5px;width:80px;height: 25px;'/>";
        }
        cmd::set_return_game_br(false);
        ?>
    </form>
    <script>
        var select = document.getElementById('area');
        select.options[<?php echo(value::getvalue('map', 'area_id', 'id', $in_map_id) - 1); ?>].selected = true;
        var select = document.getElementById('is_pk');
        select.options[<?php echo value::getvalue('map', 'is_pk', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_danrenfuben');
        select.options[<?php echo value::getvalue('map', 'is_danrenfuben', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_duorenfuben');
        select.options[<?php echo value::getvalue('map', 'is_duorenfuben', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_cunzhangjia');
        select.options[<?php echo value::getvalue('map', 'is_cunzhangjia', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_fengyaoguan');
        select.options[<?php echo value::getvalue('map', 'is_fengyaoguan', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_yizhan');
        select.options[<?php echo value::getvalue('map', 'is_yizhan', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_zahuopu');
        select.options[<?php echo value::getvalue('map', 'is_zahuopu', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_xiuxingchang');
        select.options[<?php echo value::getvalue('map', 'is_xiuxingchang', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_jianyu');
        select.options[<?php echo value::getvalue('map', 'is_jianyu', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_gonghuilingdi');
        select.options[<?php echo value::getvalue('map', 'is_gonghuilingdi', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_chalou');
        select.options[<?php echo value::getvalue('map', 'is_chalou', 'id', $in_map_id); ?>].selected = true;
        var select = document.getElementById('is_jingjichang');
        select.options[<?php echo value::getvalue('map', 'is_jingjichang', 'id', $in_map_id); ?>].selected = true;
    </script>
<?php
?>
<?php
//获取模式
if (!$GLOBALS['t_arr'][0]) {
    $mode = value::real_escape_string($_POST['mode']);
} else {
    $mode = $GLOBALS['t_arr'][0];
}
//获取传输数据
if ($mode) {
    $name = value::real_escape_string($_POST['new_name']);
} else {
    $name = value::real_escape_string($_POST['name']);
}
$sex = value::real_escape_string($_POST['sex']) == 0 ? '男' : '女';
$sex_n = value::real_escape_string($_POST['sex']) == 0 ? '他' : '她';
$sex_nn = value::real_escape_string($_POST['sex']) == 0 ? '帅哥' : '美女';
$area_id = value::real_escape_string($_POST['area_id']);
//默认描述 对话
if ($_POST['desc']) {
    $desc = value::real_escape_string($_POST['desc']);
} else {
    $desc = $sex_n . '就是' . $name . '。';
}
if ($_POST['talk']) {
    $talk = value::real_escape_string($_POST['talk']);
} else {
    $talk = $sex_n . '没有什么话对你说。';
}
//处理数据
if ($name) {
    //获取模式修改或添加
    if (!$mode) {
        //开始增加NPC
        $sql = "INSERT INTO `npc`(`area_id`, `name`, `desc`, `sex`, `talk`) VALUES ('" . $area_id . "','" . $name . "','" . $desc . "','" . $sex . "','" . $talk . "')";
        echo "<b style='color:#0000ff'>成功创建" . $name . $sex_nn . "!<br>描述:" . $desc . "<br>对话:" . $talk . "</b>";
        br();
    } else {
        $sql = "UPDATE `npc` SET `area_id`=" . $area_id . ",`name`='" . $name . "',`desc`='" . $desc . "',`sex`='" . $sex . "',`talk`='" . $talk . "' WHERE `id`=" . $mode;
        echo "<b style='color:#0000ff'>成功修改" . $name . $sex_nn . "!<br>描述:" . $desc . "<br>对话:" . $talk . "</b>";
        br();
    }
    sql($sql);
    cmd::addcmd('add_npc', '返回上级');
    return;
}
?>
    <form action="<?php
    echo "game.php?sid=" . $GLOBALS['sid'] . "&cmd=" . cmd::addcmd('add_npc,' . $cmd, '操作', false); ?>" method="post"
          autocomplete="on">
        <span>区域:</span>
        <select name="area_id" id="area">
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
            <option value="11">地下1层第三间房</option>
            <option value="12">地下1层采矿所</option>
            <option value="13">天桥2</option>
            <option value="14">地下2层采矿所</option>
            <option value="15">天桥3</option>
            <option value="16">矿石储藏所</option>
            <option value="17">天桥1</option>
            <option value="18">废矿南部洞穴</option>
            <option value="19">地下1层第一间房</option>
            <option value="20">尸王殿</option>
            <option value="21">盟重</option>
            <option value="22">盟重南郊外</option>
            <option value="23">盟重北郊外</option>
            <option value="24">石阁庙（野外）</option>
            <option value="25">石阁1层</option>
            <option value="26">石阁2层</option>
            <option value="27">石阁3层</option>
            <option value="28">石阁4层</option>
            <option value="29">石阁5层</option>
            <option value="30">石阁6层</option>
            <option value="31">石阁7层</option>
            <option value="32">道馆</option>
            <option value="33">道馆郊外</option>
            <option value="34">沃玛神殿入口</option>
            <option value="35">沃玛神殿1层</option>
            <option value="36">沃玛神殿2层</option>
            <option value="37">沃玛神殿</option>
            <option value="38">祖玛神殿（野外）</option>
            <option value="39">祖玛神殿1层</option>
            <option value="40">祖玛神殿2层</option>
            <option value="41">祖玛神殿3层</option>
            <option value="42">祖玛神殿4层</option>
            <option value="43">祖玛神殿5层</option>
            <option value="44">祖玛阁迷宫</option>
            <option value="45">祖玛神殿7层</option>
            <option value="46">祖玛神殿7层</option>
            <option value="47">祖玛神殿7层</option>
            <option value="48">祖玛教主之家</option>
            <option value="49">沙巴克</option>
            <option value="50">灌木林</option>
            <option value="51">赤月山谷1层</option>
            <option value="52">赤月山谷2层</option>
            <option value="53">赤月山谷3层</option>
            <option value="54">赤月山谷4层</option>
            <option value="55">赤月山谷5层</option>
            <option value="56">暗之boss地图</option>
            <option value="57">魔龙郊外入口</option>
            <option value="58">魔龙林间胜地</option>
            <option value="59">魔龙旧寨</option>
            <option value="60">魔龙祭坛</option>
            <option value="61">魔龙岭</option>
            <option value="62">魔龙沼泽</option>
            <option value="63">魔龙关</option>
            <option value="64">魔龙血域</option>
            <option value="65">ST会员副本1层</option>
            <option value="66">ST会员副本2层</option>
            <option value="67">ST会员副本3层</option>
            <option value="68">ST会员副本4层</option>
            <option value="69">ST会员副本5层</option>
            <option value="70">ST会员圣地</option>
            <option value="71">新手炼级场</option>
            <option value="72">蚂蚁洞穴一层</option>
            <option value="73">蚂蚁洞穴二层</option>
            <option value="74">蚂蚁洞穴三层</option>
            <option value="75">潘夜岛一层</option>
            <option value="76">潘夜岛二层</option>
            <option value="77">潘夜岛三层</option>
            <option value="78">铗虫谷1层</option>
            <option value="79">铗虫谷2层</option>
            <option value="80">铗虫谷3层</option>
            <option value="81">万年谷一层</option>
            <option value="82">万年谷二层</option>
            <option value="83">万年谷三层</option>
            <option value="84">万年谷四层</option>
            <option value="85">万年谷五层</option>
            <option value="86">雷炎洞穴1层</option>
            <option value="87">雷炎洞穴2层</option>
            <option value="88">雷炎洞穴3层</option>
            <option value="89">攻城战地图</option>
            <option value="90">经验秘境</option>
            <option value="91">羽翼试练</option>
            <option value="92">贵宾副本1层</option>
            <option value="93">贵宾副本2层</option>
            <option value="94">贵宾副本3层</option>
            <option value="95">会员之家一层</option>
            <option value="96">会员之家二层</option>
            <option value="97">会员之家三层</option>
        </select><br>
        <span>名字:</span>
        <input type="text" name="name" value="<?php if ($mode) {
            echo value::getvalue('npc', 'name', 'id', $mode);
        } ?>" <?php if ($mode) {
            echo "readonly='readonly'";
        } ?>><br>
        <?php
        if ($mode) {
            echo "<span>新名: </span><input type=\"text\" name=\"new_name\" value=\"" . value::getvalue('npc', 'name', 'id', $mode) . "\">";
            br();
        }
        ?>
        <span>描述:</span>
        <textarea name="desc" style="width:167px;height:50px;"><?php if ($mode) {
                echo value::getvalue('npc', 'desc', 'id', $mode);
            } ?></textarea><br>
        <span>对话:</span>
        <textarea name="talk" style="width:167px;height:50px;"><?php if ($mode) {
                echo value::getvalue('npc', 'talk', 'id', $mode);
            } ?></textarea><br>
        <span>性别:</span>
        <select name="sex" id="sex">
            <option value="0">帅哥</option>
            <option value="1">美女</option>
        </select><br>
        <input name='mode' type='hidden' value='<?php echo $GLOBALS['t_arr'][0]; ?>'/>
        <?php
        if (!$mode) {
            echo "<input name='submit' type='submit' title='创建' value='创建' style='margin-top: 5px;width:80px;height: 25px;'/>";
        } else {
            echo "<input name='submit' type='submit' title='修改' value='修改' style='margin-top: 5px;width:80px;height: 25px;'/>";
        }
        ?>
    </form>
    <script>
        var select = document.getElementById('area');
        select.options[<?php echo(value::getvalue('map', 'area_id', 'id', value::get_game_user_value('in_map_id')) - 1); ?>].selected = true;
        var select = document.getElementById('sex');
        select.options[<?php echo(value::getvalue('npc', 'sex', 'id', $mode) == '女' ? 1 : 0); ?>].selected = true;
    </script>
<?php
cmd::addcmd('set_npc', 'NPC调整');
?>
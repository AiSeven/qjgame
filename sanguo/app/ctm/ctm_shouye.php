<?php
    $user_id = uid();
    $name = value::get_game_user_value('name', $user_id);
    $user_cj = value::get_user_value('user_cj', $user_id);
    if (!$user_cj) {
        if (!$name) {
            e182();
            return;
        } else {
            echo "<img src='res/img/js/js1.gif'>";
            br();
            echo "<span style='color:green'>     正当人们绝望无比,快要放弃生命的时候,很偶然,人们发现了一个神秘的入口直穿死亡山谷下面的祖玛寺庙通往地面。<br>但是通路在地下几十丈的深处,是错综复杂的巨大迷宫,四处潜伏着众多妖魔怪兽,进入迷宫的人类一个个都惨遭灾难。<br>于是,一百余名精锐勇士被挑选出来,他们将从在这个通道中突围出去,去寻求援兵。</span>";
            br();
            br();
            cmd::addcmd('e402', '继续');
            cmd::set_show_return_game(false);
            return;
        }
    }
?>
<?php
echo "<img src='logo2.png'>";
br();
echo"最原汁原味的传奇游戏,最经典的版本。<br> 不断加入全新特色玩法,沙巴克城战、魔龙城国战…… 最经典的传奇,最精彩的玩法,只为让你...";
br();
cmd::addcmd('e5', '进入游戏');
br();
cmd::addcmd('e44', '游戏论坛');
br();
echo "【官方动态】";
br();
cmd::addcmd('hd_tg1', '进群领取奖励');
br();
cmd::addcmd('hd_tg2', '推荐玩家奖励');
br();
cmd::addcmd('hd_tg3', '推广宣传奖励');
br();
cmd::addcmd('hd_tg4', '全民找茬奖励');
br();
echo "【客服中心】";
br();
echo "客服QQ：738323424";
br();
$user_id = uid();
if ($user_id < 1) {
    echo "官方群号：qq-779369837";
    br();
} else {
    echo "官方群号：qq-779369837";
    br();
} 
echo "传奇报时：「" . date("H:i") . "」";
 br();
echo "技能修复说明：";
 br();
echo "新手前3个任务都会送技能秘籍卷*999，使用后可以获得基础剑术.基础剑术需要在背包书籍里面学习，学习一个即可。然后去技能里面设置为默认技能，丢失就在学习一次即可。防止中途丢失技能，会随机跳转到技能页";
br();
br();
echo "<a href=\"https://qm.qq.com/q/RF2pLliagq\"><<仙盟会游戏</a>";
cmd::set_show_return_game(false);
?>
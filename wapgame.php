<?php
//忽略危险
error_reporting(E_ALL || ~E_NOTICE);
//开始游戏会话
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>ST-绿色传奇（仙盟会）</title>
    <style>
        body {
            font: Normal 18px "Microsoft YaHei";
            text-align: center;
        }

        a {
            text-decoration: none;
        }

        @media (min-width: 768px) {
            body {
                margin: 0 auto;
                width: 414px;
            }
        }

        .logo {
            margin-top: 5px;
        }
    </style>
</head>
<body>
<?php
//引入游戏文件
//配置文件
require_once('config.php');
//获取验证SID
$sid = $_GET['sid'];
$game = $_GET['game'];
$area = $_GET['area'];
//验证登录SID
if ($sid) {
    //连接mysql数据库
    $mysqli = new mysqli($mysql_host, $mysql_user_name, $mysql_passwd, $mysql_dbname, $mysql_port);
    //编码语句
    $query = "set names utf8mb4";
    //执行编码
    $mysqli->query($query);
    //查询用户
    $sid = $mysqli->real_escape_string($sid);
    $sql = "SELECT `id`,`name`,`password`,`mobile`,`last_login_time` FROM `user` WHERE `sid`='{$sid}' LIMIT 1";
    $result = $mysqli->query($sql);
    list($uid, $uname, $upasswd, $umob, $ullt) = $result->fetch_row();
    if ($uid) {
        //获取社区
        $community = get_community($uid);
        //保存社区COOKIES
        $community_url = get_community_url($uid);
        if ($community_url) {
            setcookie('community_url', $community_url, time() + 3600 * 24 * 365);
        }
        //输出书签头部
        echo "<img src='logo2.png'><br>";
        echo "欢迎，<span style='color: #0000ff'>ID  {$uid}  </span>来到绿色传奇!更多好玩游戏-Www.仙盟会<br>";
        echo "请选择区服： <br>";
        //是否需要设置安全密码
        if (mb_strlen($upasswd) <0) {
            $need_form = true;
            $pwd = post::get('password');
            $c_pwd = post::get('confirm_password');
            $mob = post::get('mobile');
            if ($pwd && $c_pwd && $mob) {
                if ($pwd == $c_pwd) {
                    if (mb_strlen($pwd) >= 0 && mb_strlen($pwd) <= 16) {
                        if (mb_strlen($mob) >= 0 && is_numeric($mob)) {
                            $need_form = false;
                            $sql = "UPDATE `user` SET `password`='{$pwd}',`mobile`='{$mob}' WHERE `sid`='{$sid}' LIMIT 1";
                            $mysqli->query($sql);
                            echo <<<html
设置成功，请牢记您的帐号密码!
<br>
游戏账号:<span style="color: #0000ff">{$uname}</span>
<br>
安全密码:<span style="color: #ff0000">{$pwd}</span>
<br>
安全手机:<span style="color: #ff7f00">{$mob}</span>
<br>
<a href="">进入游戏</a>
<br>
html;
                        } else {
                            echo "安全手机错误请您重新设置!<br>";
                        }
                    } else {
                        echo "密码长度错误请您重新设置!<br>";
                    }
                } else {
                    echo "个次输入的密码不一致!<br>";
                }
            }
            //需要密码
            if ($need_form) {
                echo <<<html
<br>                
                   安全验证<br>  
保护您的账户安全,请您设置游戏安全密码!
<form action="" method="post">
<input type="password" name="password" placeholder="请输入密码(8-16位字符)">
<br>
<input type="password" name="confirm_password" placeholder="请确认密码(8-16位字符)">
<br>
<input type="text" name="mobile" placeholder="安全手机号(11位字符)">
<br>
<input type="submit" value="确认设置">
</form>
html;
            }
        } else {
            $auth_login = false;
            $xz_time = 36000000000 * 24 * 365;
            if (time() - strtotime($ullt) < $xz_time) {
                $auth_login = true;
            } else {
                $pwd = post::get('password');
                if ($pwd) {
                    if ($pwd == $upasswd) {
                        $sql = "UPDATE `user` SET `last_login_time`=NOW() WHERE `sid`='{$sid}' LIMIT 1";
                        $mysqli->query($sql);
                        $auth_login = true;
                    } else {
                        echo "安全密码错误!<br>";
                    }
                }
            }
            if ($auth_login) {
                //设置用户ID
                $_SESSION['uid'] = $uid;
                $_SESSION['wapgame_url'] = $_SERVER["REQUEST_URI"];
                //输出游戏链接
                $game_arr = getConfigByName("game");
                foreach ($game_arr as $game_key => $game_data) {
                    $html_str = "";
                    $last_area = array();
                    foreach ($game_data as $game_com => $game_com_data) {
                        foreach ($game_com_data as $c => $c_data) {
                            if ($c == $community || !$community) {
                                foreach ($c_data as $c_a => $c_n) {
                                    if ($c_data['game'] == $game || !$game) {
                                        if ($c_a == "area") {
                                            foreach ($c_n as $a => $n) {
                                                if (!$last_area[$game_key][$a]) {
                                                    $chinese_a = ToChineseNum($a);
                                                    $html_str .= <<<html
<a data-clipboard-text="T7zfnU25V8" class="alipay" href="{$game_key}/game.php?cmd=1&g={$a}">{$game_data['name']}({$n})<img src='new.gif'></a>
<br>
html;
                                                    $last_area[$game_key][$a] = 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ($html_str) {
                            echo $html_str;
                        }
                    }
                }
            } else {
                echo <<<html
              安全验证(绿色传奇仙盟会)<br>                
<form action="" method="post">
<input type="password" name="password" placeholder="请输入安全密码">
<input type="submit" value="开始验证">
</form>
html;
            }
        }
        //输出书签脚部
        if ($uid < 1) {
            $source1 = "738323424";
        } else {
            $source1 = "qq-738323424";
        } 
        echo <<<footer
        官方群号：{$source1}<br>
footer;
    } else {
        //提示玩家登录
        need_login();
    }
    //关闭mysql数据库
    $mysqli->close();
} else {
    //提示玩家登录
    need_login();
}
?>
</body>
</html>

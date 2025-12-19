<?php
//忽略危险
error_reporting(E_ALL || ~E_NOTICE);
//头部文件
header("charset=utf8");
//引入游戏文件
//配置文件
require_once('config.php');
//开始游戏会话
session_start();
//连接mysql数据库
$mysqli = new mysqli($mysql_host, $mysql_user_name, $mysql_passwd, $mysql_dbname, $mysql_port);
//设置连接字符集
$sql = "set names utf8mb4";
$mysqli->query($sql);
//用户数据
$user_name = "";
//来源社区
$source = $_GET['source'];
//是否充值成功
$success = false;
//登陆参数
$game = $_GET['game'];
$area = $_GET['area'];
$user = $_GET['user'];
$time = $_GET['time'];
$hash = $_GET['hash'];
$order = $_GET['order'];
$amount = $_GET['amount'];
if ($amount < 1000) {
    $amount1 = $amount;
} else if ($amount < 2000) {
    $amount1 = (int)($amount * 1.1);
} else if ($amount < 5000) {
    $amount1 = (int)($amount * 1.2);
} else if ($amount < 10000) {
    $amount1 = (int)($amount * 1.4);
} else if ($amount < 30000) {
    $amount1 = (int)($amount * 1.6);
} else if ($amount < 50000) {
    $amount1 = (int)($amount * 2);
} else if ($amount >= 50000) {
    $amount1 = (int)($amount * 2.5);
}
//获取社区数据数组
$community_arr = getConfigByName("community");
//平台密钥
$community_game_key = $community_arr[$source]["key"];
//游戏编号
$community_game_num = $community_arr[$source]["game"];
//社区名称
$community_name = $community_arr[$source]["name"];
//社区网址
$community_url = $community_arr[$source]["url"];
//验证hash
$community_user_hash = eval("return " . $community_arr[$source]["hash"] . ";");
//开始验证
if ($game && $area && $user && $time && $community_user_hash) {
    if ($hash == $community_user_hash) {
        $user_name = $user;
        $success = true;
    }
}
//登录结果
if ($success) {
    //提取用户id
    $result = $mysqli->query("SELECT `id` FROM `user` WHERE `name` = '$user_name' LIMIT 1");
    list($uid) = $result->fetch_row();
    if ($uid) {
        $sql = "SELECT 1 FROM `recharge` WHERE `community`='$source' AND `order` = '$order' LIMIT 1";
        $result = $mysqli->query($sql);
        list($is_recharged) = $result->fetch_row();
        if (!$is_recharged) {
            $sql = "INSERT INTO `recharge` (`Id`,`user_name`,`community`,`game`,`area`,`user`,`time`,`order`,`amount`) VALUES (NULL,'$user_name','$source','$game','$area','$user','$time','$order','$amount')";
            $mysqli->query($sql);
            if (!$mysqli->error) {
                /*根据游戏进行区服充值*/               
                $game_arr = getConfigByName("game");
                foreach ($game_arr as $game_key => $game_data) {
                    $last_area = 0;
                    foreach ($game_data as $game_com => $game_com_data) {
                        foreach ($game_com_data as $c => $c_data) {
                            if ($c == $source) {
                                foreach ($c_data as $c_a => $c_n) {
                                    if ($c_data['game'] == $game) {
                                        $game_db_name = $game_data['area'][$area];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                $sql = "USE {$game_db_name}";
                $mysqli->query($sql);
                //增加灵石
                $sql = "SELECT 1 FROM `game_user_value` WHERE `valuename`='{$uid}.i.1' LIMIT 1";
                $rs = $mysqli->query($sql);
                list($have_item) = $rs->fetch_row();
                if ($have_item) {
                    $sql = "UPDATE `game_user_value` SET `value`=`value`+{$amount1} WHERE `valuename`='{$uid}.i.1' LIMIT 1";
                    $mysqli->query($sql);
                } else {
                    $sql = "INSERT INTO `game_user_value` (`userid`,`valuename`,`value`) VALUES ('{$uid}','{$uid}.i.1','$amount1')";
                    $mysqli->query($sql);
                }
                if (!$mysqli->error) {
                    //累积金额
                    $amount_rmb = (int)($amount / 10);
                    $sql = "SELECT 1 FROM `game_user_value` WHERE `valuename`='{$uid}.recharge_money' LIMIT 1";
                    $rs = $mysqli->query($sql);
                    list($have_oldv) = $rs->fetch_row();
                    if ($have_oldv) {
                        $sql = "UPDATE `game_user_value` SET `value`=`value`+{$amount_rmb} WHERE `valuename`='{$uid}.recharge_money' LIMIT 1";
                        $mysqli->query($sql);
                    } else {
                        $sql = "INSERT INTO `game_user_value` (`userid`,`valuename`,`value`) VALUES ('{$uid}','{$uid}.recharge_money','{$amount_rmb}')";
                        $mysqli->query($sql);
                    }
                    $sql = "SELECT 1 FROM `game_user_value` WHERE `valuename`='{$uid}.sc_money' LIMIT 1";
                    $rs = $mysqli->query($sql);
                    list($have_oldv) = $rs->fetch_row();
                    if ($have_oldv) {
                        $sql = "UPDATE `game_user_value` SET `value`=`value`+{$amount_rmb} WHERE `valuename`='{$uid}.sc_money' LIMIT 1";
                        $mysqli->query($sql);
                    } else {
                        $sql = "INSERT INTO `game_user_value` (`userid`,`valuename`,`value`) VALUES ('{$uid}','{$uid}.sc_money','{$amount_rmb}')";
                        $mysqli->query($sql);
                    }
                    //返回响应
                    echo 1;
                } else {
                    echo 0;
                    $sql = "USE {$mysql_dbname}";
                    $mysqli->query($sql);
                    $sql = "DELETE FROM `recharge` WHERE `community`='$source' AND `order` = '$order' LIMIT 1";
                    $mysqli->query($sql);
                }
            }
        } else {
            echo 0;
        }
    } else {
        echo 0;
    }
} else {
    echo 0;
}
//关闭数据库
$mysqli->close();

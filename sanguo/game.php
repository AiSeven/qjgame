<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>绿色传奇（仙盟会）</title>
    <style>
        body {
            font: Normal 18px "Microsoft YaHei";
        }

        @media (min-width: 768px) {
            div {
                margin: 0 auto;
                width: 414px;
            }
        }

        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
<div>
    <?php
    //忽略危险
    error_reporting(E_ALL || ~E_NOTICE);
    //开始会话
    session_start();
    //引入游戏文件
    //配置文件
    require_once("app/conf/config.php");
    //系统函数
    require_once("app/sys/system.php");
    //系统类
    require_once("app/sys/class.php");
    //事件系统
    require_once("app/sys/event.php");
    //任务系统
    require_once("app/sys/task.php");
    //基础框架
    //开始时间戳
    $stime = c_msectime();
    //玩家是否登录
    $is_login = true;
    //玩家是否退出
    $is_exit = false;
    //全局参数
    $t_arr = array();
    
    // 数据库连接测试（仅在需要时取消注释）
    /*
    echo '<div style="background:#f0f0f0;padding:10px;margin-bottom:20px;">';
    echo '<h3>数据库配置测试</h3>';
    echo '配置信息:<br>';
    echo '- sg_mp001: ' . $mysql_game_user . '/' . $mysql_game_pass . '<br>';
    echo '- wapgame: ' . $mysql_db_user . '/' . $mysql_db_pass . '<br>';
    echo '</div>';
    */
    //获取uid
    $uid = $_SESSION['uid'];
    //获取cmd
    $cmd = $_GET['cmd'];
    //获取进入区服
    $enter_game_area = $_GET['g'];
    //设置游戏区服
    if ($enter_game_area) {
        $_SESSION['game_area'] = $enter_game_area;
    }
    $game_area = $_SESSION['game_area'];
    //在线游戏
    if (!$GLOBALS['system_update']) {
        //验证uid
        if ($uid) {
            if ($game_area) {
                //设置游戏数据库
                $game_area_dbname = $mysql_game_area_db_name[$game_area - 1];
                //根据数据库名选择正确的用户名和密码
                if ($game_area_dbname == 'sg_mp001') {
                    $db_user = $mysql_game_user;
                    $db_pass = $mysql_game_pass;
                } else if ($game_area_dbname == 'wapgame') {
                    $db_user = $mysql_db_user;
                    $db_pass = $mysql_db_pass;
                } else {
                    $db_user = $mysql_user_name;
                    $db_pass = $mysql_passwd;
                }
                //游戏数据库操作
                // 尝试使用localhost连接
                $mysqli = new mysqli('localhost', $db_user, $db_pass, $game_area_dbname, $mysql_port);
                // 如果localhost连接失败，尝试使用127.0.0.1
                if ($mysqli->connect_error) {
                    $mysqli = new mysqli($mysql_host, $db_user, $db_pass, $game_area_dbname, $mysql_port);
                }
                // 检查连接是否成功
                if ($mysqli->connect_error) {
                    die("数据库连接失败: " . $mysqli->connect_error);
                }
                $GLOBALS['mysqli'] = $mysqli;
                //执行编码
                $mysqli->set_charset('utf8mb4');
                //Redis数据库操作
                // Redis已被移除，数据存储已迁移到MySQL
                // 创建目录用于测试记录
                $GLOBALS['redis'] = null;

                //获取用户数据
                $sql = "SELECT `user_id`,`is_online`,`sid` FROM `game_user` WHERE `user_id` = {$uid} LIMIT 1";
                $result = $mysqli->query($sql);
                //玩家是否在线
                $user_id = 0;
                $is_online = 0;
                $sid = '';
                if ($result && $result->num_rows > 0) {
                    list($user_id, $is_online, $sid) = $result->fetch_row();
                }
                //玩家是否注册
                if ($user_id) {
                    //玩家正在登录
                    if ($enter_game_area) {
                        value::set_game_user_value('sid', session_id());
                        if ($is_online) {
                            //刷新模板
                            call_func('e0');
                        } else {
                            //登录事件
                            call_func('e2,' . $game_area . ',true');
                        }
                    } else {
                        //玩家正在游戏
                        //游戏会话验证
                        if (session_id() == $sid) {
                            //玩家是否在线
                            if ($is_online) {
                                //echo "金币:", value::get_user_value('money', $uid), "";br();
                                if ($cmd) {
                                    $cmd = $mysqli->real_escape_string($cmd);
                                    $user_event = cmd::getcmd($cmd);
                                    //获取事件
                                    if ($user_event) {
                                        //调用用户操作事件
                                        call_func($user_event);
                                    } else {
                                        //刷新模板
                                        call_func('e0');
                                    }
                                } else {
                                    //刷新模板
                                    call_func('e0');
                                }
                            } else {
                                $is_login = false;
                                cmd::set_show_return_game(false);
                            }
                        } else {
                            $is_exit = true;
                            cmd::set_show_return_game(false);
                        }
                    }
                } else {
                    //注册事件
                    call_func('e1,' . $game_area);
                }
                //返回游戏链接
                cmd::add_return_game();
                //设置最大cmd
                cmd::set_max_cmd();
                //关闭数据库
                //$mysqli->close();
                // Redis已移除，无需关闭连接
            } else {
                $is_login = false;
            }
        } else {
            $is_exit = true;
        }
        //玩家已经离线
        if (!$is_login) {
            //提示玩家登录
            c_need_login();
        }
        //玩家已经退出
        if ($is_exit) {
            //提示玩家登录
            c_need_login();
            //结束游戏会话
            unset($_SESSION['uid']);
        }
    } else {
        //在线更新
        c_update();
    }
    //结束时间戳
    $endtime = c_msectime() - $stime;
    //基础框架
    ?>
</div>
</body>
</html>

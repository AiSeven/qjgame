<?php
//系统类
//类全局变量
$t = time();

//属性类
class value
{
    //查询函数
    static function query($sql)
    {
        if (!isset($GLOBALS['mysqli']) || $GLOBALS['mysqli']->connect_error) {
            die('数据库连接失败: ' . $GLOBALS['mysqli']->connect_error);
        }
        $result = $GLOBALS['mysqli']->query($sql);
        if (!$result) {
            // 可以选择记录错误日志或简单返回
            error_log('数据库查询错误: ' . $GLOBALS['mysqli']->error . ' - SQL: ' . $sql);
        }
        return $result;
    }

    //插入函数
    static function insert($tablename, $valuenamestr, $valuestr)
    {
        $game_area_dbname = $GLOBALS['game_area_dbname'];
        $sql = "INSERT INTO `$game_area_dbname`.`$tablename` ($valuenamestr) VALUES ($valuestr)";
        self::query($sql);
        return true;
    }

    //验证函数
    static function real_escape_string($string)
    {
        return $GLOBALS['mysqli']->real_escape_string($string);
    }

    //获取属性函数
    static function getvalue($tablename, $valuename, $qvaluename = 'id', $qvalue = 'null')
    {
        $condition = "";
        if ($qvalue == 'null') {
            $qvaluename_arr = json_decode($qvaluename, true);
            foreach ($qvaluename_arr as $tkey => $tvalue) {
                $condition .= " `{$tkey}` = '{$tvalue}' AND";
            }
            $condition = trim($condition, 'AND');
            if (!$condition) {
                return false;
            }
        } else {
            $condition = "`{$qvaluename}` = '{$qvalue}'";
        }
        $sql = "SELECT `{$valuename}` FROM `{$tablename}` WHERE $condition LIMIT 1";
        $result = self::query($sql);
        if (!$result) {
            return false;
        }
        $row = $result->fetch_row();
        if ($row) {
            return $row[0];
        }
        return false;
    }

    //设置属性
    static function setvalue($tablename, $valuename, $value, $qvaluename = 'id', $qvalue = 'null')
    {
        $condition = "";
        if ($qvalue == 'null') {
            $qvaluename_arr = json_decode($qvaluename, true);
            foreach ($qvaluename_arr as $tkey => $tvalue) {
                $condition .= " `{$tkey}` = '{$tvalue}' AND";
            }
            $condition = trim($condition, 'AND');
            if (!$condition) {
                return false;
            }
        } else {
            $condition = "`{$qvaluename}` = '{$qvalue}'";
        }
        $sql = "UPDATE `{$tablename}` SET `{$valuename}`='{$value}' WHERE $condition LIMIT 1";
        self::query($sql);
        return $value;
    }

    //设置属性函数(没有时插入数据)
    static function setvalue2($tablename, $valuename, $value, $qvaluename = 'id', $qvalue = 'null', $insert_valurnamestr = NULL, $insert_valuestr = NULL)
    {
        if ($qvalue == 'null') {
            $qvalue = uid();
        }
        if (self::getvalue($tablename, $valuename, $qvaluename, $qvalue) != "") {
            $sql = "SELECT COUNT(*) FROM `{$tablename}` WHERE `valuename`='{$valuename}' AND `{$qvaluename}`='{$qvalue}'";
            $result = self::query($sql);
            if ($result) {
                $row = $result->fetch_row();
                $count = $row ? $row[0] : 0;
            } else {
                $count = 0;
            }
            if ($count > 1) {
                self::query("DELETE FROM `{$tablename}` WHERE `valuename`='{$valuename}' AND `{$qvaluename}`='{$qvalue}'");
                self::insert($tablename, $insert_valurnamestr, $insert_valuestr);
            } else {
                $sql = "UPDATE `{$tablename}` SET `{$valuename}`='{$value}' WHERE `{$qvaluename}`='{$qvalue}' LIMIT 1";
                self::query($sql);
            }
        } else {
            self::insert($tablename, $insert_valurnamestr, $insert_valuestr);
        }
    }

    //增加属性函数
    static function addvalue($tablename, $valuename, $value, $qvaluename = 'id', $qvalue = 'null', $insert_valurnamestr = NULL, $insert_valuestr = NULL)
    {
        $condition = "";
        if ($qvalue == 'null') {
            $qvaluename_arr = json_decode($qvaluename, true);
            foreach ($qvaluename_arr as $tkey => $tvalue) {
                $condition .= " `{$tkey}` = '{$tvalue}' AND";
            }
            $condition = trim($condition, 'AND');
            if (!$condition) {
                return false;
            }
        } else {
            $condition = "`{$qvaluename}` = '{$qvalue}'";
        }
        if (($old_value = self::getvalue($tablename, $valuename, $qvaluename, $qvalue)) != '') {
            $new_value = $value + $old_value;
            $sql = "UPDATE `{$tablename}` SET `{$valuename}`='{$new_value}' WHERE $condition LIMIT 1";
            self::query($sql);
            return $new_value;
        } else {
            if ($insert_valurnamestr && $insert_valuestr) {
                self::insert($tablename, $insert_valurnamestr, $insert_valuestr);
                return $value;
            } else {
                return 0;
            }
        }
    }

    //获取人物属性函数
    static function get_user_value($uservaluename, $uid = 0, $auto = true)
    {
        if (!$uid) {
            $uid = uid();
        }
        $r = self::getvalue('game_user_value', 'value', 'valuename', $uid . "." . $uservaluename);
        if ($r != '') {
            return $r;
        } else {
            if ($auto) {
                self::insert('game_user_value', "`userid`,`petid`,`valuename`,`value`", "'" . $uid . "','0','" . $uid . "." . $uservaluename . "','0'");
            }
            return 0;
        }
    }

    //设置人物属性函数
    static function set_user_value($uservaluename, $value, $uid = 0)
    {
        if (!$uid) {
            $uid = uid();
        }
        self::get_user_value($uservaluename, $uid);
        self::setvalue2('game_user_value', 'value', $value, 'valuename', $uid . "." . $uservaluename);
    }

    //增加人物属性函数
    static function add_user_value($uservaluename, $value, $uid = 0)
    {
        if (!$uid) {
            $uid = uid();
        }
        $old_value = self::get_user_value($uservaluename, $uid);
        $new_value = $old_value + $value;
        self::setvalue2('game_user_value', 'value', $new_value, 'valuename', $uid . "." . $uservaluename);
        return $new_value;
    }

    //插入人物属性函数
    static function insert_user_value($uservaluename, $value, $uid = 0)
    {
        if (!$uid) {
            $uid = uid();
        }
        self::insert('game_user_value', "`userid`,`petid`,`valuename`,`value`", " '{$uid}','0','{$uid}.{$uservaluename}','{$value}'");
    }

    //获取game_pet 宠物属性函数
    static function get_pet_value($pet_id, $valuename)
    {
        return self::getvalue('game_pet', $valuename, 'id', $pet_id);
    }

    //设置 game_pet 宠物属性函数
    static function set_pet_value($pet_id, $valuename, $value)
    {
        return self::setvalue('game_pet', $valuename, $value, 'id', $pet_id);
    }
    //
    //增加game_pet 宠物属性函数
    static function add_pet_value($pet_id, $value, $count, $show = true, $mode = 0)
    {
        $name = self::get_pet_value($pet_id, 'name');
        $min_value = -1;
        $max_value = 0;
        //mode为1 value 为 valuename
        if (!$mode) {
            if ($value == 1) {
                $valuename = '生命';
                $t_valuename = "hp";
                $miaoshu = "恢复";
                $max_value = (int)pet::get_max_hp($pet_id);
                $min_value = 0;
            } else if ($value == 2) {
                $valuename = '经验值';
                $t_valuename = "exp";
                $miaoshu = "获得";
                $max_value = 0;
            } else if ($value == 3) {
                $t_valuename = "zhongcheng";
                $max_value = 100;
                $min_value = 0;
            } else if ($value == 4) {
                $valuename = "属性点";
                $miaoshu = "获得";
                $t_valuename = "shuxingdian";
                $max_value = 0;
            } else if ($value == 5) {
                $valuename = "忠诚度";
                $miaoshu = "恢复";
                $t_valuename = "zhongcheng";
                $max_value = 100;
            } else if ($value == 6) {
                $valuename = "最大等级";
                $miaoshu = "提升";
                $t_valuename = "max_lvl";
                $max_value = 100;
            } else if ($value == 7) {
                $valuename = "经验值";
                $miaoshu = "获得";
                $t_valuename = "exp";
                $max_value = 0;
            }
        } else {
            $t_valuename = $value;
            $max_value = 0;
            $show = false;
        }
        $old_value = self::get_pet_value($pet_id, $t_valuename);
        $new_value = $count + $old_value;
        //是否有最大值限制
        if ($max_value) {
            $new_value = $new_value > $max_value ? $max_value : $new_value;
        }
        //是否有最小值限制
        if ($min_value > -1) {
            $new_value = $new_value < $min_value ? $min_value : $new_value;
        }
        self::set_pet_value($pet_id, $t_valuename, $new_value);
        //是否显示描述
        if ($show) {
            echo $name . $miaoshu . '了' . (int)($new_value - $old_value) . "点{$valuename}。";
            br();
        }
        return $new_value;
    }

    //获取宠物属性函数
    static function get_pet_value2($petvaluename, $pet_id, $auto = true)
    {
        $obj = new game_pet_object($pet_id);
        return $obj->get($petvaluename);
    }

    //设置宠物属性函数
    static function set_pet_value2($petvaluename, $value, $pet_id)
    {
        $obj = new game_pet_object($pet_id);
        $obj->set($petvaluename, $value);
        return $value;
    }

    //增加宠物属性函数
    static function add_pet_value2($petvaluename, $value, $pet_id)
    {
        $obj = new game_pet_object($pet_id);
        return $obj->incrby($petvaluename, $value);
    }

    //插入宠物属性函数
    static function insert_pet_value2($petvaluename, $value, $pet_id)
    {
        $obj = new game_pet_object($pet_id);
        $obj->set($petvaluename, $value);
        return $value;
    }

    //获取pet 宠物种族属性函数
    static function get_pet_zz_value($pet_id, $valuename)
    {
        return self::getvalue('pet', $valuename, 'id', self::get_pet_value($pet_id, 'pet_id'));
    }

    //获取map 地图属性函数
    static function get_map_value($map_id, $valuename, $in_map_show = false)
    {
        $value = self::getvalue('map', $valuename, 'id', $map_id);
        if ($in_map_show) {
            $union_id = self::get_game_user_value('gonghui.id');
            if ($valuename == 'name' && self::get_map_value($map_id, 'is_gonghuilingdi') && $union_id) {
                if ($value == '帮派领地') {
                    $value = union::get_name($union_id);
                }
            }
        }
        return $value;
    }

    //获取npc npc属性函数
    static function get_npc_value($npc_id, $valuename)
    {
        return self::getvalue('npc', $valuename, 'id', $npc_id);
    }

    //获取item 物品属性函数
    static function get_item_value($item_id, $valuename)
    {
        return self::getvalue('item', $valuename, 'id', $item_id);
    }

    //获取skill 技能属性函数
    static function get_skill_value($skill_id, $valuename)
    {
        return self::getvalue('skill', $valuename, 'id', $skill_id);
    }

    //获取task 任务属性函数
    static function get_task_value($task_id, $valuename)
    {
        return self::getvalue('task', $valuename, 'id', $task_id);
    }

    //获取prop 道具属性函数
    static function get_prop_value($prop_id, $valuename)
    {
        return self::getvalue('prop', $valuename, 'id', $prop_id);
    }

    //获取prop 道具种族属性函数
    static function get_game_prop_zz_value($prop_id, $valuename)
    {
        return self::get_prop_value(self::get_game_prop_value($prop_id, 'prop_id'), $valuename);
    }

    //获取game_prop 道具属性函数
    static function get_game_prop_value($prop_id, $valuename)
    {
        return self::getvalue('game_prop', $valuename, 'id', $prop_id);
    }

    //设置game_prop 道具属性函数
    static function set_game_prop_value($prop_id, $valuename, $value)
    {
        return self::setvalue('game_prop', $valuename, $value, 'id', $prop_id);
    }

    //增加 game_prop 道具属性函数
    static function add_game_prop_value($prop_id, $valuename, $value)
    {
        $old_value = self::getvalue('game_prop', $valuename, 'id', $prop_id);
        $old_value += $value;
        $new_value = $old_value > 0 ? $old_value : 0;
        self::set_game_prop_value($prop_id, $valuename, $new_value);
        return $new_value;
    }

    //获取game_user 用户属性函数
    static function get_game_user_value($valuename, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return self::getvalue('game_user', $valuename, 'id', $user_id);
    }

    //设置 game_user 用户属性函数
    static function set_game_user_value($valuename, $value, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return self::setvalue('game_user', $valuename, $value, 'id', $user_id);
    }

    //增加 game_user 用户属性函数
    static function add_game_user_value($valuename, $value, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $new_value = self::get_game_user_value($valuename, $user_id) + $value;
        return self::set_game_user_value($valuename, $new_value, $user_id);
    }

    //增加 game_user 用户属性函数
    static function add_exp($valuename, $value, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        if ($value < 0) {
            echo '你失去了' . $value . '经验';
            br();
        } else {
            echo '你获得了' . $value . '经验';
            br();
        }
        $new_value = self::get_game_user_value($valuename, $user_id) + $value;
        return self::set_game_user_value($valuename, $new_value, $user_id);
    }

    //设置系统属性函数
    static function set_system_value($valuename, $value)
    {
        self::setvalue2('game_value', "value", $value, "valuename", "c.{$valuename}", "userid,petid,valuename,value", "'1','1','c.{$valuename}','{$value}'");
    }

    //获取系统属性函数
    static function get_system_value($valuename)
    {
        $r = self::getvalue('game_value', 'value', 'valuename', "c.{$valuename}");
        if (!$r) {
            self::set_system_value($valuename, '0');
            return 0;
        } else {
            return $r;
        }
    }

    //获取game_bbs 帖子属性函数
    static function get_bbs_value($bbs_id, $valuename)
    {
        return self::getvalue('game_bbs', $valuename, 'id', $bbs_id);
    }

    //设置 game_bbs 帖子属性函数
    static function set_bbs_value($bbs_id, $valuename, $value)
    {
        return self::setvalue('game_bbs', $valuename, $value, 'id', $bbs_id);
    }

    //加减 game_bbs 帖子属性函数
    static function add_bbs_value($bbs_id, $valuename, $value)
    {
        return self::setvalue('game_bbs', $valuename, self::get_bbs_value($bbs_id, $valuename) + $value, 'id', $bbs_id);
    }
}

//命令类
class cmd
{
    //最大命令
    static $max_cmd = 0;
    //能否返回游戏
    static $show_return_game = true;
    //返回游戏是否换行
    static $return_game_br = true;
    //cmd数组
    static $cmd_arr = array();

    //增加cmd函数
    static function addcmd($event, $name = '操作', $show = true, $return_url = false)
    {
        $cmd = self::get_max_cmd();
        self::$cmd_arr["cmd." . $cmd] = $event;
        
        // 同时保存到用户对象中
        $uid = uid();
        $obj = new game_user_object($uid);
        $obj->set("cmd." . $cmd, $event);
        
        if ($show) {
            url::addurl($cmd, $name);
        }
        if ($return_url) {
            return "game.php?cmd=" . $cmd . "&t={$GLOBALS['t']}";
        } else {
            return $cmd;
        }
    }

    //增加cmd返回url函数
    static function addcmd2url($event)
    {
        return self::addcmd($event, "", false, true);
    }

    //获取cmd命令
    static function getcmd($cmd)
    {
        $uid = uid();
        $obj = new game_user_object($uid);
        
        // 先尝试从内存数组中获取
        if (isset(self::$cmd_arr["cmd." . $cmd])) {
            return self::$cmd_arr["cmd." . $cmd];
        }
        
        // 如果内存数组中没有，则从数据库中获取
        $event = $obj->get("cmd." . $cmd);
        
        // 当找不到CMD映射时，返回false
        if (!$event) {
            return false;
        }
        
        // 将从数据库获取的命令添加到内存数组中
        self::$cmd_arr["cmd." . $cmd] = $event;
        
        return $event;
    }

    //获取max_cmd命令
    static function get_max_cmd()
    {
        if (!self::$max_cmd) {
            self::$max_cmd = value::get_game_user_value('max_cmd');
        }
        self::$max_cmd++;
        if (self::$max_cmd > 998) {
            self::$max_cmd = 2;
        }
        return self::$max_cmd;
    }

    //设置max_cmd命令
    static function set_max_cmd()
    {
        // 不再需要insert_cmd_arr，因为addcmd已经直接保存到数据库
        value::set_game_user_value('max_cmd', self::$max_cmd);
    }

    //设置show_return_game
    static function set_show_return_game($show = true)
    {
        self::$show_return_game = $show;
    }

    //设置return_game_br
    static function set_return_game_br($show = true)
    {
        self::$return_game_br = $show;
    }

    //删除全部cmd函数
    static function delallcmd($id = 0)
    {
        if (!$id) {
            $id = uid();
        }
        $obj = new game_user_object($id);
        $obj->adel("cmd.*");
    }

    //添加返回游戏
    static function add_return_game($is_br = true)
    {
        if (self::$show_return_game) {
            if ($is_br && self::$return_game_br) {
                br();
            }
            self::addcmd('e5', '返回游戏');
        }
    }

    //添加返回上级
    static function add_last_cmd($event)
    {
        self::addcmd($event, '返回上级');
    }

    //插入cmd数组
    // 此方法已废弃，因为addcmd已直接保存到数据库
    static function insert_cmd_arr()
    {
        // 不再需要将cmd_arr批量保存到数据库
        // 保留此方法是为了向后兼容
        return;
    }
}

//链接类
class url
{
    //添加链接
    static function addurl($cmd, $name)
    {
        echo "<a href=\"game.php?cmd=" . $cmd . "&t={$GLOBALS['t']}\" title=\"{$name}\">{$name}</a>";
    }
}


//模板类
class ctm
{
    //显示模版函数
    static function show_ctm($ctm_name, $change_ctm = true)
    {
        if ($change_ctm) {
            value::set_game_user_value('in_ctm', $ctm_name);
        }
        require_once("app/ctm/{$ctm_name}.php");
    }
}

//物品类
Class item
{
    //增加 减少物品数量 可以输入负数 是否显示物品提示
    static function add_item($id, $count, $xianshi = true, $uid = 0, $qiangzhi = false, $is_skill_chat = false)
    {
        if (!$uid) {
            $uid = uid();
        }
        $old_count = value::get_user_value('i.' . $id, $uid, false);
        $name = value::get_item_value($id, 'name');
        $liangci = value::get_item_value($id, 'liangci');
        $fuzhong = value::get_item_value($id, 'fuzhong');
        $miaoshu = "获得";
        if ($count < 0) {
            if ($old_count < (-1 * $count)) {
                if ($xianshi) {
                    echo '你身上没有足够的' . $name . '了。';
                    br();
                }
                return false;
            }
            $miaoshu = "失去";
        } else {
            //物品负重 空数量 非强制
            if ($fuzhong) {
                $beibaofuzhong = user::get_fuzhong();
                $beibaorongliang = user::get_rongliang();
                if (($beibaofuzhong + $fuzhong) > $beibaorongliang) {
                    echo "抱歉,您的背包已满。";
                    br();
                    return false;
                }
            }
        }
        $miaoshu_str = "{$miaoshu}了" . abs($count) . $liangci . $name;
        if ($xianshi) {
            if (!$is_skill_chat) {
                echo "你" . $miaoshu_str . "。";
                br();
            } else {
                skill::add_skill_chat("你{$miaoshu_str}。", $uid, 0);
            }
        }
        $xs_old_count = value::get_user_value('i.' . $id, $uid, false) + $count;
        c_log('add_item()', value::get_game_user_value('name', $uid) . "(id:" . $uid . ")在事件{add_item}{$miaoshu_str}(i{$id}),剩{$xs_old_count}数量\r\n");
        $count += self::get_item($id, $uid);
        self::set_item($id, $count, $uid);
        return true;
    }

    //失去物品 无法输入负数
    static function lose_item($id, $count, $xianshi = true, $uid = 0)
    {
        if (!$uid) {
            $uid = uid();
        }
        $name = value::getvalue('item', 'name', 'id', $id);
        $have_count = self::get_item($id, $uid);
        if ($count < 1 || !$have_count || $count > $have_count) {
            if ($xianshi) {
                echo '你身上没有足够的' . $name . '了。';
                br();
            }
            return false;
        } else {
            self::add_item($id, -1 * $count, $xianshi, $uid);
            return true;
        }
    }

    //获取物品数量
    static function get_item($id, $uid = 0)
    {
        if (!$uid) {
            $uid = uid();
        }
        $count = value::getvalue('game_user_value', 'value', 'valuename', $uid . '.i.' . $id);
        if (!$count) {
            $count = 0;
        }
        return $count;
    }

    //设置物品数量
    static function set_item($id, $count, $uid = 0)
    {
        if (!$uid) {
            $uid = uid();
        }
        value::setvalue2('game_user_value', 'value', $count, 'valuename', $uid . ".i." . $id, "`id`,`userid`,`petid`,`valuename`,`value`", "NULL,'" . $uid . "','0','" . $uid . ".i.{$id}','" . $count . "'");
    }

    //获取身上元宝数量
    static function get_lingshi($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return self::get_item(1, $user_id);
    }

    //增加元宝数量
    static function add_lingshi($count, $xianshi = true, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        if ($count) {
            return self::add_item(1, (int)$count, $xianshi, $user_id);
        } else {
            return self::get_item(1, $user_id);
        }
    }

    //获取身上金钱数量
    static function get_money($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return value::get_user_value('money', $user_id);
    }

    //获取杂货铺金钱数量
    static function get_zhp_money($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return value::get_user_value('zahuopu.money', $user_id);
    }

    //增加杂货铺金钱数量
    static function add_zhp_money($money, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $money = (int)$money;
        if ($money < 1) {
            if ($money * -1 > self::get_zhp_money($user_id)) {
                return false;
            }
        }
        value::add_user_value('zahuopu.money', $money, $user_id);
        return true;
    }

    static function add_zhp_money1($money, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $money = (int)$money;
        if ($money < 1) {
            if ($money * -1 > self::get_money($user_id)) {
                return false;
            }
        }
        value::add_user_value('money', $money, $user_id);
        return true;
    }

    //增加金钱数量
    static function add_money($count, $uid = 0, $xianshi = true)
    {
        if (!$uid) {
            $uid = uid();
        }
        $money = value::getvalue('game_user_value', 'value', 'valuename', $uid . ".money");
        if ($count < 0) {
            if ($money < (-1 * $count)) {
                if ($xianshi) {
                    echo '抱歉,你的金币不足';
                    br();
                }
                return false;
            }
            if ($xianshi) {
                echo '你失去了' . (-1 * $count) . '个金币';
                br();
            }
            $xs_money = value::getvalue('game_user_value', 'value', 'valuename', $uid . ".money") + $count;
            c_log('add_money()', value::get_game_user_value('name', $uid) . "(id:" . uid() . ")在事件{add_money}失去了" . (-1 * $count) . "个金币，剩{$xs_money}数量\r\n");
        } else {
            if ($xianshi) {
                echo '你获得了' . $count . '个金币';
                br();
            }
            $xs_money = value::getvalue('game_user_value', 'value', 'valuename', $uid . ".money") + $count;
            c_log('add_money()', value::get_game_user_value('name', $uid) . "(id:" . uid() . ")在事件{add_money}获得了" . $count . "个金币，剩{$xs_money}数量\r\n");
        }
        $count += $money;
        if ($count < 0) {
            $count = 0;
        }
        value::setvalue2('game_user_value', 'value', $count, 'valuename', $uid . ".money", "`id`,`userid`,`petid`,`valuename`,`value`", "NULL,'" . $uid . "','0','" . $uid . ".money','" . $count . "'");
        return true;
    }

    //地图增加物品
    static function map_add_item($map_id, $item_id, $count, $team_id = 0, $user_id = 0, $union_id = 0)
    {
        $valuename = 'map.' . $map_id;
        if ($team_id) {
            $valuename .= ".t." . $team_id;
        }
        if ($union_id) {
            $valuename .= ".u." . $union_id;
        }
        if ($user_id) {
            $valuename .= "." . $user_id;
        }
        $valuename .= ".i." . $item_id;
        value::insert('game_value', " `id`,`userid`,`petid`,`valuename`,`value`", "NULL,0,0,'{$valuename}','{$count}'");
    }

    //给宠物使用物品
    static function pet_use_item($pet_id, $item_id)
    {
        //宠物归属
        if (!user::have_pet(0, $pet_id)) {
            echo "该宠物已经不属于你了。";
            br();
            return;
        }
        if ($item_id == 206) {
            item::add_item(206, -1);
            item::add_item(1, 10);
        }
        if ($item_id == 207) {
            item::add_item(207, -1);
            item::add_item(2, 10);
        }
        if ($item_id == 208) {
            item::add_item(208, -1);
            item::add_item(3, 10);
        }
        if ($item_id == 209) {
            item::add_item(209, -1);
            item::add_item(4, 10);
        }
        if ($item_id == 210) {
            item::add_item(210, -1);
            item::add_item(5, 10);
        }
        if ($item_id == 211) {
            item::add_item(211, -1);
            item::add_item(6, 10);
        }
        if ($item_id == 214 && value::get_pet_value($pet_id, 'lvl') == 10) {
            value::set_pet_value($pet_id, 'max_lvl', 20);
            item::add_item(214, -1);
        }
        if ($item_id == 216) {
            $jy_exp = item::get_item(216);
            value::add_pet_value($pet_id, 7, $jy_exp);
            item::add_item(216, -$jy_exp);
        }
        $name = value::get_pet_value($pet_id, 'name');
        $item_name = value::get_item_value($item_id, 'name');
        //宠物是否死亡
        if (value::get_pet_value($pet_id, 'is_dead')) {
            //复活丸
            if ($item_id == 83) {
                if (item::lose_item($item_id, 1)) {
                    value::set_pet_value($pet_id, 'is_dead', 0);
                    value::set_pet_value($pet_id, 'hp', pet::get_max_hp($pet_id));
                    echo "{$name}服下了{$item_name},仙气环绕,起死回生!";
                    br();
                }
            } else {
                echo "宠物已死亡,无法使用物品。";
                br();
            }
            return;
        } else {
            if ($item_id == 83) {
                echo "{$name}尚未死亡,无法服用复活丸。";
                br();
                return;
            }
        }
        //特殊使用事件
        $event = value::getvalue('item', 'event', 'id', $item_id);
        if ($event) {
            call_user_func($event, $pet_id, $item_id);
            return;
        }
        $max_hp = (int)pet::get_max_hp($pet_id);
        //分类物品使用事件
        if (item::lose_item($item_id, 1, false)) {
            //补血药品
            switch ($item_id) {
                case 2:
                    value::add_pet_value($pet_id, 1, 30);
                    break;
                case 3:
                    value::add_pet_value($pet_id, 1, 70);
                    break;
                case 4:
                    value::add_pet_value($pet_id, 1, 110);
                    break;
                case 5:
                    value::add_pet_value($pet_id, 1, 170);
                    break;
                case 6:
                    value::add_pet_value($pet_id, 1, 200);
                    break;
                case 770:
                    value::add_pet_value($pet_id, 1, $max_hp);
                    break;
            }
            //状态药品
            if ($item_id > 946 && $item_id < 951) {
                $zhuangtai = value::get_pet_value($pet_id, 'zhuangtai');
                $zhengquejiedu = $item_id - 12;
                if ($zhuangtai == $zhengquejiedu) {
                    echo $name . "从异常状态中恢复了。";
                    br();
                    value::set_pet_value($pet_id, 'zhuangtai', '0');
                } else {
                    echo $name . "服下了1" . value::get_item_value($item_id, 'liangci') . value::get_item_value($item_id, 'name') . ",但没有什么效果。";
                    br();
                }
            }
            //属性点药品
            switch ($item_id) {
                //玉菩提
                case 75:
                    value::add_pet_value($pet_id, 4, 3);
                    break;
            }
            //忠诚度药品
            switch ($item_id) {
                //九丹金液
                case 80:
                    value::add_pet_value($pet_id, 5, 20);
                    break;
            }
        }
    }

    //玩家使用物品
    static function user_use_item($item_id, $step = 0, $tmp_str = "")
    {
        $user_id = uid();
        $user_name = value::get_game_user_value('name');
        //特殊使用事件
        $event = value::getvalue('item', 'event', 'id', $item_id);
        if ($event) {
            item::lose_item($item_id, 1);
            call_user_func($event, $user_id, $item_id);
            return;
        }
        $is_shuji = value::get_item_value($item_id, 'is_shuji');
        if ($is_shuji == 1) {
            xxjn($item_id);
            return;
        }
        $bl_id = value::get_user_value('bl.id', $user_id);
        if ($bl_id) {
            $bl_name = value::get_game_user_value('name', $bl_id);
        }
        $user_can_use = false;
        $need_error_miaoshu = true;
        $error_miaoshu = "";
        $item_name = value::get_item_value($item_id, 'name');
        $item_liangci = value::get_item_value($item_id, 'liangci');
        $lose_show = false;
        $now_time = time();
        //药品使用条件
        if (($item_id > 1 && $item_id < 13) || $item_id == 52 || $item_id == 80 || $item_id == 212 || ($item_id > 229 && $item_id < 241)) {
            if (item::lose_item($item_id, 1, false, $user_id)) {
                $name = value::get_game_user_value('name', $user_id);
                $new_hp = 0;
                $new_mp = 0;
                $mp = value::get_game_user_value('mp', $user_id);
                $max_mp = value::get_game_user_value('max_mp', $user_id);
                $hp = value::get_game_user_value('hp', $user_id);
                $max_hp = value::get_game_user_value('max_hp', $user_id);
                switch ($item_id) {
                    case 2:
                        $new_hp = 30;
                        value::add_game_user_value('hp', 30);
                        break;
                    case 3:
                        $new_hp = 70;
                        value::add_game_user_value('hp', 70);
                        break;
                    case 4:
                        $new_hp = 110;
                        value::add_game_user_value('hp', 110);
                        break;
                    case 5:
                        $new_hp = 170;
                        value::add_game_user_value('hp', 170);
                        break;
                    case 6:
                        $new_hp = 200;
                        value::add_game_user_value('hp', 200);
                        break;
                    case 7:
                        $new_mp = 40;
                        value::add_game_user_value('mp', 40);
                        break;
                    case 8:
                        $new_mp = 100;
                        value::add_game_user_value('mp', 100);
                        break;
                    case 9:
                        $new_mp = 180;
                        value::add_game_user_value('mp', 180);
                        break;
                    case 10:
                        $new_mp = 250;
                        value::add_game_user_value('mp', 250);
                        break;
                    case 11:
                        $new_mp = 300;
                        value::add_game_user_value('mp', 300);
                        break;
                    case 12:
                        $new_mp = 5;
                        $new_hp = 5;
                        value::add_game_user_value('mp', 5);
                        value::add_game_user_value('hp', 5);
                        break;
                    case 52:
                        $new_mp = 110;
                        $new_hp = 70;
                        value::add_game_user_value('mp', 110);
                        value::add_game_user_value('hp', 70);
                        break;
                    case 80:
                        $new_mp = 110;
                        $new_hp = 180;
                        value::add_game_user_value('mp', 110);
                        value::add_game_user_value('hp', 180);
                        break;
                    case 212:
                        $new_mp = 170;
                        $new_hp = 250;
                        value::add_game_user_value('mp', 5000);
                        value::add_game_user_value('hp', 10000);
                        break;
                    case 230:
                        item::add_item(2, 9);
                        break;
                    case 231:
                        item::add_item(3, 9);
                        break;
                    case 232:
                        item::add_item(4, 9);
                        break;
                    case 233:
                        item::add_item(5, 9);
                        break;
                    case 234:
                        item::add_item(6, 9);
                        break;
                    case 235:
                        item::add_item(7, 9);
                        break;
                    case 236:
                        item::add_item(8, 9);
                        break;
                    case 237:
                        item::add_item(9, 9);
                        break;
                    case 238:
                        item::add_item(10, 9);
                        break;
                    case 239:
                        item::add_item(11, 9);
                        break;
                    case 240:
                        item::add_item(212, 9);
                        break;
                    default:
                        break;
                }
                if ($hp >= $max_hp) {
                    value::set_game_user_value('hp', $max_hp, $user_id);
                }
                if ($mp >= $max_mp) {
                    value::set_game_user_value('mp', $max_mp, $user_id);
                }
                $max_hp1 = $max_hp - $hp;
                if ($max_hp1 > $new_hp) {
                    $new_hp1 = $new_hp;
                } else {
                    $new_hp1 = $max_hp1;
                }
                $max_mp1 = $max_mp - $mp;
                if ($max_mp1 > $new_mp) {
                    $new_mp1 = $new_mp;
                } else {
                    $new_mp1 = $max_mp1;
                }
                if ($new_hp > 0) {
                    echo $name . "使用" . value::get_item_value($item_id, 'name') . "回复了{$new_hp1}点生命值！";
                }
                if ($new_mp > 0) {
                    echo $name . "使用" . value::get_item_value($item_id, 'name') . "恢复了{$new_mp1}点魔法值！";
                }
                br();
            }
            return;
        }
        if ($item_id == 71) {
            item::lose_item($item_id, 1);
            echo "你熔化了1{$item_liangci}{$item_name},灵气溢出,获得了100缕补金灵气！<br>使用说明：背包装备点击修补装备即可!";
            br();
            value::add_user_value('bujinlingqi', 100);
        }
        if ($item_id == 73) {
            item::lose_item($item_id, 1);
            echo "你熔化了1{$item_liangci}{$item_name},灵气溢出,获得了1000缕补金灵气！<br>使用说明：背包装备点击修补装备即可!";
            br();
            value::add_user_value('bujinlingqi', 1000);
        }
        if ($item_id == 72) {
            item::lose_item($item_id, 1);
            echo "你使用了1{$item_liangci}{$item_name},有几率成为幸运道具，使用幸运道具会使人发挥自己的最大潜能。";
            br();
        }
        if ($item_id > 73 && $item_id < 80) {
            item::lose_item($item_id, 1);
            echo "你使用了1{$item_liangci}{$item_name}!";
            br();
            switch ($item_id) {
                case 74:
                    value::add_user_value('ss_mp', 1);
                    break;
                case 75:
                    value::add_user_value('ss_hp', 1);
                    break;
                case 76:
                    value::add_game_user_value('pugong', 1);
                    break;
                case 77:
                    value::add_game_user_value('pufang', 1);
                    break;
                case 78:
                    value::add_game_user_value('tegong', 1);
                    break;
                case 79:
                    value::add_game_user_value('tefang', 1);
                    break;
                default:
                    break;
            }
        }
    }

    //玩家使用物品
    static function user_use_item66($item_id, $step = 0, $tmp_str = "")
    {
        $user_id = uid();
        $user_name = value::get_game_user_value('name');
        //特殊使用事件
        $event = value::getvalue('item', 'event', 'id', $item_id);
        if ($event) {
            item::lose_item($item_id, 1);
            call_user_func($event, $user_id, $item_id);
            return;
        }
        $bl_id = value::get_user_value('bl.id', $user_id);
        if ($bl_id) {
            $bl_name = value::get_game_user_value('name', $bl_id);
        }
        $user_can_use = false;
        $need_error_miaoshu = true;
        $error_miaoshu = "";
        $item_name = value::get_item_value($item_id, 'name');
        $is_shuji = value::get_item_value($item_id, 'is_shuji');
        if ($is_shuji == 1) {
            xxjn($item_id);
            return;
        }
        $item_liangci = value::get_item_value($item_id, 'liangci');
        $lose_show = false;
        $now_time = time();
        //药品使用条件
        if (($item_id > 1 && $item_id < 13) || $item_id == 52 || $item_id == 80 || $item_id == 212 || ($item_id > 229 && $item_id < 241)) {
            if (item::lose_item($item_id, 1, false, $user_id)) {
                $name = value::get_game_user_value('name', $user_id);
                $new_hp = 0;
                $new_mp = 0;
                $mp = value::get_game_user_value('mp', $user_id);
                $max_mp = value::get_game_user_value('max_mp', $user_id);
                $hp = value::get_game_user_value('hp', $user_id);
                $max_hp = value::get_game_user_value('max_hp', $user_id);
                switch ($item_id) {
                    case 2:
                        $new_hp = 30;
                        value::add_game_user_value('hp', 30);
                        break;
                    case 3:
                        $new_hp = 70;
                        value::add_game_user_value('hp', 70);
                        break;
                    case 4:
                        $new_hp = 110;
                        value::add_game_user_value('hp', 110);
                        break;
                    case 5:
                        $new_hp = 170;
                        value::add_game_user_value('hp', 170);
                        break;
                    case 6:
                        $new_hp = 200;
                        value::add_game_user_value('hp', 200);
                        break;
                    case 7:
                        $new_mp = 40;
                        value::add_game_user_value('mp', 40);
                        break;
                    case 8:
                        $new_mp = 100;
                        value::add_game_user_value('mp', 100);
                        break;
                    case 9:
                        $new_mp = 180;
                        value::add_game_user_value('mp', 180);
                        break;
                    case 10:
                        $new_mp = 250;
                        value::add_game_user_value('mp', 250);
                        break;
                    case 11:
                        $new_mp = 300;
                        value::add_game_user_value('mp', 300);
                        break;
                    case 12:
                        $new_mp = 5;
                        $new_hp = 5;
                        value::add_game_user_value('mp', 5);
                        value::add_game_user_value('hp', 5);
                        break;
                    case 52:
                        $new_mp = 110;
                        $new_hp = 70;
                        value::add_game_user_value('mp', 110);
                        value::add_game_user_value('hp', 70);
                        break;
                    case 80:
                        $new_mp = 110;
                        $new_hp = 180;
                        value::add_game_user_value('mp', 110);
                        value::add_game_user_value('hp', 180);
                        break;
                    case 212:
                        $new_mp = 170;
                        $new_hp = 250;
                        value::add_game_user_value('mp', 5000);
                        value::add_game_user_value('hp', 10000);
                        break;
                    case 230:
                        item::add_item(2, 9);
                        break;
                    case 231:
                        item::add_item(3, 9);
                        break;
                    case 232:
                        item::add_item(4, 9);
                        break;
                    case 233:
                        item::add_item(5, 9);
                        break;
                    case 234:
                        item::add_item(6, 9);
                        break;
                    case 235:
                        item::add_item(7, 9);
                        break;
                    case 236:
                        item::add_item(8, 9);
                        break;
                    case 237:
                        item::add_item(9, 9);
                        break;
                    case 238:
                        item::add_item(10, 9);
                        break;
                    case 239:
                        item::add_item(11, 9);
                        break;
                    case 240:
                        item::add_item(212, 9);
                        break;
                    default:
                        break;
                }
                if ($hp >= $max_hp) {
                    value::set_game_user_value('hp', $max_hp, $user_id);
                }
                if ($mp >= $max_mp) {
                    value::set_game_user_value('mp', $max_mp, $user_id);
                }
                $max_hp1 = $max_hp - $hp;
                if ($max_hp1 > $new_hp) {
                    $new_hp1 = $new_hp;
                } else {
                    $new_hp1 = $max_hp1;
                }
                $max_mp1 = $max_mp - $mp;
                if ($max_mp1 > $new_mp) {
                    $new_mp1 = $new_mp;
                } else {
                    $new_mp1 = $max_mp1;
                }
                if ($new_hp > 0) {
                    echo $name . "使用" . value::get_item_value($item_id, 'name') . "回复了{$new_hp1}点生命值！";
                }
                if ($new_mp > 0) {
                    echo $name . "使用" . value::get_item_value($item_id, 'name') . "恢复了{$new_mp1}点魔法值！";
                }
                br();
            }
            return;
        }
        //战斗相关
        switch ($item_id) {
            //免战牌 免战之牌,使用后可1日内无法被玩家挑战,主动PK之后失效。
            case 114:
                $now_time = date("Y-m-d H:i:s");
                $mianzhan_time = value::get_user_value('mianzhan.time', $user_id);
                if ($mianzhan_time < $now_time) {
                    $user_can_use = true;
                } else {
                    $shengyu_time = strtotime($mianzhan_time) - strtotime($now_time);
                    $shengyu_hour = (int)($shengyu_time / 3600);
                    $shengyu_minute = (int)($shengyu_time % 3600 / 60);
                    $error_miaoshu = "你的免战时间效果还剩{$shengyu_hour}小时{$shengyu_minute}分钟,无法使用{$item_name}!";
                    br();
                }
                break;
            case 236:
                $now_time = date("Y-m-d H:i:s");
                value::set_user_value('hy_lvl', 1, $user_id);
                value::set_user_value('hy_day', 7, $user_id);
                value::set_user_value('hy_time', 604800, $user_id);
                value::set_user_value('wj_hy_time', $now_time, $user_id);
                $nick_name = "ST会员";
                value::insert_user_value('nick_name', $nick_name);
                if (!value::get_user_value('using_nick_name')) {
                    value::set_user_value('using_nick_name', $nick_name);
                }
                c_add_guangbo('恭喜' . value::get_game_user_value('name') . '获得了[' . $nick_name . ']的称号!');
                echo '恭喜你获得了' . $nick_name . '称号。';
                br();
                echo "你的ST会员时间效果获得7天时间!";
                $user_can_use = true;
                br();
                break;
            case 233:
                $now_time = time();
                value::set_user_value('wj_db.time', $now_time, $user_id);
                value::set_user_value('wj_db', 2, $user_id);
                echo "掉宝效果:2倍掉宝效果还剩余60分钟!";
                $user_can_use = true;
                br();
                break;
            case 235:
                $now_time = time();
                value::set_user_value('wj_bj.time', $now_time, $user_id);
                value::set_user_value('wj_bj', 2, $user_id);
                echo "掉宝效果:2倍暴击效果还剩余10分钟!";
                $user_can_use = true;
                br();
                break;
            case 234:
                $now_time = time();
                value::set_user_value('wj_jy.time', $now_time, $user_id);
                value::set_user_value('wj_jy', 2, $user_id);
                echo "掉宝效果:2倍掉宝效果还剩余60分钟!";
                $user_can_use = true;
                br();
                break;
            //引战幡 引战之幡,使用后可获得50次引战次数,引战次数可用于挑战NPC。
            case 79:
                $user_can_use = true;
                break;
        }
        //宠物相关
        switch ($item_id) {
            //孵化石 能给宠物卡增加10%孵化度的孵化石
            case 84:
                $sql = "SELECT `id`,`name` FROM `game_prop` WHERE `user_id`=$user_id AND `user_num`=4 LIMIT 1";
                $result = sql($sql);
                list($have_egg_id, $have_egg_name) = $result->fetch_row();
                if ($have_egg_id) {
                    if (!$step) {
                        echo "你确定要给你的{$have_egg_name}增加10%的孵化度吗?";
                        br();
                        cmd::addcmd("e55,{$item_id},0,0,1", '确认加速');
                        br();
                        $need_error_miaoshu = false;
                    } else {
                        if (pet::get_fuhuadu(value::get_game_prop_value($have_egg_id, 'pet_id')) < 100) {
                            $user_can_use = true;
                        } else {
                            $error_miaoshu = "你的宠物卡已经孵化成功!";
                            br();
                        }
                    }
                } else {
                    $error_miaoshu = "你的孵化巢里没有宠物卡!";
                    br();
                }
                break;
            //九丹金液精华 九丹金液精华,使用后可获得20颗九丹金液,九丹金液能给宠物+20忠诚度。
            case 105:
                $user_can_use = true;
                break;
            //复活丸精华 复活丸精华,使用后可以获得10颗复活丸,复活丸能让宠物复活。
            case 106:
                $user_can_use = true;
                break;
        }
        //玩家相关
        switch ($item_id) {
            //补金石 补金神石,使用后可获得1000补金灵气,补金灵气可修补装备耐久。
            case 81:
                $user_can_use = true;
                break;
            //签名卡 签名之卡,使用后可更改玩家个性签名。
            case 82:
                $desc = value::real_escape_string($_POST['desc']);
                if (!$desc) {
                    $error_miaoshu = "设置成功将消耗1{$item_liangci}{$item_name}。<br>";
                    $cmd = cmd::addcmd('e55,' . $item_id, '提交', false);
                    echo "<form action='game.php?cmd=" . $cmd . "' method='post'><span>请输入签名(30字内):</span><br/><input type='text' name='desc' maxlength='30'><input type='submit' 'name'='设置' value='设置'></form>";
                } else {
                    if (c_check_str($desc, 0, 30)) {
                        $user_can_use = true;
                    } else {
                        $error_miaoshu = '签名无法使用!';
                        br();
                    }
                }
                break;
            //乾坤袋 乾坤之袋,使用后可选择增加1个永久背包/装备仓库/宠物仓库/宠物袋/育将袋容量。
            case 85:
                if (!$step) {
                    echo "你要用{$item_name}增加哪里的容量?";
                    br();
                    cmd::addcmd("e55,{$item_id},0,0,1,cyd", '宠物袋');
                    br();
                    cmd::addcmd("e55,{$item_id},0,0,1,yyd", '育将袋');
                    br();
                    cmd::addcmd("e55,{$item_id},0,0,1,bb", '人物背包');
                    br();
                    cmd::addcmd("e55,{$item_id},0,0,1,zbck", '装备仓库');
                    br();
                    cmd::addcmd("e55,{$item_id},0,0,1,ygck", '宠物仓库');
                    br();
                    $need_error_miaoshu = false;
                } else {
                    $user_can_use = true;
                }
                break;
            //装备宝箱 传说中能开出上古神兵利器的装备宝箱。
            case 87:
                $user_can_use = true;
                break;
            //宠物宝箱 传说中能开出上古天地灵兽的宠物宝箱。
            case 88:
                $user_can_use = true;
                break;
            //金钱宝箱(小) 传说中能开出无数金钱的金钱宝箱(小)。
            case 89:
                $user_can_use = true;
                break;
            //金钱宝箱(中) 传说中能开出无数金钱的金钱宝箱(中)。
            case 90:
                $user_can_use = true;
                break;
            //金钱宝箱(大) 传说中能开出无数金钱的金钱宝箱(大)。
            case 91:
                $user_can_use = true;
                break;
            //双倍经验精华 双倍经验精华,使用后可获得100次双倍经验次数,双倍经验次数可用于NPC与野怪。
            case 92:
                $user_can_use = true;
                break;
            //金币情侣徽章 寓意着天长地久,金币婚及以上的情侣可以使用,双方可以获得金币情侣卷轴(使用后可选择获得金币情侣称号)与不会掉落的金币情侣装备。
            case 93:
                if (user::get_bl_hun($user_id, 1) >= 200) {
                    if (value::get_game_user_value('is_online', $bl_id)) {
                        if (value::get_game_user_value('in_map_id', $user_id) == value::get_game_user_value('in_map_id', $bl_id)) {
                            $user_can_use = true;
                        } else {
                            $error_miaoshu = "{$bl_name}不在你的身边,无法使用!";
                            br();
                        }
                    } else {
                        $error_miaoshu = "你与{$bl_name}同时在线,方可使用!";
                        br();
                    }
                } else {
                    $error_miaoshu = "金币婚及以上的情侣方可使用!";
                    br();
                }
                break;
            //黄金情侣徽章 寓意着天长地久,黄金婚及以上的情侣可以使用,双方可以获得黄金情侣卷轴(使用后可选择获得黄金情侣称号)与不会掉落的黄金情侣装备。
            case 94:
                if (user::get_bl_hun($user_id, 1) >= 500) {
                    if (value::get_game_user_value('is_online', $bl_id)) {
                        if (value::get_game_user_value('in_map_id', $user_id) == value::get_game_user_value('in_map_id', $bl_id)) {
                            $user_can_use = true;
                        } else {
                            $error_miaoshu = "{$bl_name}不在你的身边,无法使用!";
                            br();
                        }
                    } else {
                        $error_miaoshu = "你与{$bl_name}同时在线,方可使用!";
                        br();
                    }
                } else {
                    $error_miaoshu = "黄金婚及以上的情侣方可使用!";
                    br();
                }
                break;
            //水晶情侣徽章 寓意着天长地久,水晶婚及以上的情侣可以使用,双方可以获得水晶情侣卷轴(使用后可选择获得水晶情侣称号)与不会掉落的水晶情侣装备。
            case 95:
                if (user::get_bl_hun($user_id, 1) >= 800) {
                    if (value::get_game_user_value('is_online', $bl_id)) {
                        if (value::get_game_user_value('in_map_id', $user_id) == value::get_game_user_value('in_map_id', $bl_id)) {
                            $user_can_use = true;
                        } else {
                            $error_miaoshu = "{$bl_name}不在你的身边,无法使用!";
                            br();
                        }
                    } else {
                        $error_miaoshu = "你与{$bl_name}同时在线,方可使用!";
                        br();
                    }
                } else {
                    $error_miaoshu = "水晶婚及以上的情侣方可使用!";
                    br();
                }
                break;
            //钻石情侣徽章 寓意着天长地久,钻石婚及以上的情侣可以使用,双方可以获得钻石情侣卷轴(使用后可选择获得钻石情侣称号)与不会掉落的钻石情侣装备。
            case 96:
                if (user::get_bl_hun($user_id, 1) >= 1000) {
                    if (value::get_game_user_value('is_online', $bl_id)) {
                        if (value::get_game_user_value('in_map_id', $user_id) == value::get_game_user_value('in_map_id', $bl_id)) {
                            $user_can_use = true;
                        } else {
                            $error_miaoshu = "{$bl_name}不在你的身边,无法使用!";
                            br();
                        }
                    } else {
                        $error_miaoshu = "你与{$bl_name}同时在线,方可使用!";
                        br();
                    }
                } else {
                    $error_miaoshu = "钻石婚及以上的情侣方可使用!";
                    br();
                }
                break;
            //金币情侣卷轴 金币婚及以上的情侣可以使用,使用后可选择获得金币情侣称号。
            case 101:
                if (user::get_bl_hun($user_id, 1) >= 200) {
                    if (!$step) {
                        $chenghao_count = 0;
                        $by_ch_arr = config::getConfigByName("sweetheart_scroll");
                        $by_ch_arr = $by_ch_arr["baiyin"];
                        foreach ($by_ch_arr as $chenghao) {
                            if (!e17($chenghao, 1)) {
                                if (!$chenghao_count) {
                                    echo "选择你要获取的金币情侣称号:";
                                    br();
                                }
                                $chenghao_count++;
                                cmd::addcmd("e55,{$item_id},0,0,1," . $chenghao, $chenghao);
                                br();
                            }
                        }
                        if ($chenghao_count) {
                            $need_error_miaoshu = false;
                        } else {
                            $error_miaoshu = "传奇中没有你可以获得的金币情侣称号了,请等待三生石更新。";
                            br();
                        }
                    } else {
                        $user_can_use = true;
                    }
                } else {
                    $error_miaoshu = "金币婚及以上的情侣方可使用!";
                    br();
                }
                break;
            //黄金情侣卷轴 黄金婚及以上的情侣可以使用,使用后可选择获得黄金情侣称号。
            case 102:
                if (user::get_bl_hun($user_id, 1) >= 500) {
                    if (!$step) {
                        $chenghao_count = 0;
                        $by_ch_arr = config::getConfigByName("sweetheart_scroll");
                        $by_ch_arr = $by_ch_arr["huangjin"];
                        foreach ($by_ch_arr as $chenghao) {
                            if (!e17($chenghao, 1)) {
                                if (!$chenghao_count) {
                                    echo "选择你要获取的黄金情侣称号:";
                                    br();
                                }
                                $chenghao_count++;
                                cmd::addcmd("e55,{$item_id},0,0,1," . $chenghao, $chenghao);
                                br();
                            }
                        }
                        if ($chenghao_count) {
                            $need_error_miaoshu = false;
                        } else {
                            $error_miaoshu = "传奇中没有你可以获得的黄金情侣称号了,请等待三生石更新。";
                            br();
                        }
                    } else {
                        $user_can_use = true;
                    }
                } else {
                    $error_miaoshu = "黄金婚及以上的情侣方可使用!";
                    br();
                }
                break;
            //水晶情侣卷轴 水晶婚及以上的情侣可以使用,使用后可选择获得水晶情侣称号。
            case 103:
                if (user::get_bl_hun($user_id, 1) >= 800) {
                    if (!$step) {
                        $chenghao_count = 0;
                        $by_ch_arr = config::getConfigByName("sweetheart_scroll");
                        $by_ch_arr = $by_ch_arr["shuijing"];
                        foreach ($by_ch_arr as $chenghao) {
                            if (!e17($chenghao, 1)) {
                                if (!$chenghao_count) {
                                    echo "选择你要获取的水晶情侣称号:";
                                    br();
                                }
                                $chenghao_count++;
                                cmd::addcmd("e55,{$item_id},0,0,1," . $chenghao, $chenghao);
                                br();
                            }
                        }
                        if ($chenghao_count) {
                            $need_error_miaoshu = false;
                        } else {
                            $error_miaoshu = "传奇中没有你可以获得的水晶情侣称号了,请等待三生石更新。";
                            br();
                        }
                    } else {
                        $user_can_use = true;
                    }
                } else {
                    $error_miaoshu = "水晶婚及以上的情侣方可使用!";
                    br();
                }
                break;
            //钻石情侣卷轴 钻石婚及以上的情侣可以使用,使用后可选择获得钻石情侣称号。
            case 104:
                if (user::get_bl_hun($user_id, 1) >= 1000) {
                    if (!$step) {
                        $chenghao_count = 0;
                        $by_ch_arr = config::getConfigByName("sweetheart_scroll");
                        $by_ch_arr = $by_ch_arr["zuanshi"];
                        foreach ($by_ch_arr as $chenghao) {
                            if (!e17($chenghao, 1)) {
                                if (!$chenghao_count) {
                                    echo "选择你要获取的钻石情侣称号:";
                                    br();
                                }
                                $chenghao_count++;
                                cmd::addcmd("e55,{$item_id},0,0,1," . $chenghao, $chenghao);
                                br();
                            }
                        }
                        if ($chenghao_count) {
                            $need_error_miaoshu = false;
                        } else {
                            $error_miaoshu = "传奇中没有你可以获得的钻石情侣称号了,请等待三生石更新。";
                            br();
                        }
                    } else {
                        $user_can_use = true;
                    }
                } else {
                    $error_miaoshu = "钻石婚及以上的情侣方可使用!";
                    br();
                }
                break;
            //御心 镜花水月,终究离碎无欢。我持御心,看破万千变幻。
            case 107:
                if ($step) {
                    $user_can_use = true;
                } else {
                    $yuxin_time = value::get_user_value("last_yuxin_time");
                    if (value::get_map_value(value::get_game_user_value("in_map_id"), "area_id") == 20) {
                        if ($yuxin_time + 7200 < $now_time) {
                            echo "御心之中闪出一道灵光,你想要去镜花水月中的哪个地方?";
                            br();
                            $map_arr = array();
                            $sql = "SELECT `id`,`name` FROM `map` WHERE `area_id`=20 ORDER BY `id`";
                            $result = sql($sql);
                            while (list($oid, $oname) = $result->fetch_row()) {
                                $map_arr[$oid] = $oname;
                            }
                            $map_arr = array_unique($map_arr);
                            foreach ($map_arr as $oid => $oname) {
                                cmd::addcmd('e55,' . $item_id . ',0,0,1,' . $oid, $oname);
                                br();
                            }
                            $error_miaoshu = "传送成功将消耗1{$item_liangci}{$item_name}。<br>";
                        } else {
                            $error_miaoshu = "{$item_name}中的灵光若隐若现,请稍后再尝试使用。<br>";
                        }
                    } else {
                        $error_miaoshu = "{$item_name}中的灵光暗淡,请在镜花水月中使用。<br>";
                    }
                }
                break;
            //纵横传奇礼包
            case 114:
                $user_can_use = true;
                break;
            //饺子
            case 115:
                $user_can_use = true;
                break;
            //汤圆
            case 116:
                $user_can_use = true;
                break;
            //苹果
            case 117:
                $user_can_use = true;
                break;
            //幸运爆竹
            case 118:
                $user_can_use = true;
                break;
        }
        //用户是否可以使用
        if ($user_can_use) {
            //使用成功 扣除物品 触发事件
            if (item::lose_item($item_id, 1, $lose_show)) {
                $can_next_use = true;
                //战斗相关
                switch ($item_id) {
                    //免战牌 免战之牌,使用后可1日内无法被玩家挑战,主动PK之后失效。
                    case 78:
                        $now_time = date("Y-m-d H:i:s");
                        $new_mianzhan_time = date("Y-m-d H:i:s", strtotime("+1day"));
                        $mzsysj = strtotime($new_mianzhan_time) - strtotime($now_time);
                        $mz_hour = (int)($mzsysj / 3600);
                        $mz_min = (int)($mzsysj % 3600 / 60);
                        value::set_user_value('mianzhan.time', $new_mianzhan_time);
                        echo "你拿出了1{$item_liangci}{$item_name},念动真言,进入免战状态,持续{$mz_hour}小时{$mz_min}分钟!";
                        br();
                        break;
                    //引战幡 引战之幡,使用后可获得50次引战次数,引战次数可用于挑战NPC。
                    case 79:
                        echo "你祭出了1{$item_liangci}{$item_name},金光一闪,获得了50次引战次数!";
                        br();
                        value::add_user_value('yinzhancishu', 50);
                        break;
                }
                //宠物相关
                switch ($item_id) {
                    //孵化石 能给宠物卡增加10%孵化度的孵化石
                    case 84:
                        $sql = "SELECT `id`,`name` FROM `game_prop` WHERE `user_id`=$user_id AND `user_num`=4 LIMIT 1";
                        $result = sql($sql);
                        list($have_egg_id, $have_egg_name) = $result->fetch_row();
                        if ($have_egg_id) {
                            $pet_id = value::get_game_prop_value($have_egg_id, 'pet_id');
                            $add_fuhuadu = value::get_pet_zz_value($pet_id, 'exp') * 3600 / 10;
                            value::add_pet_value($pet_id, 'fuhuadu', $add_fuhuadu, false, 1);
                            //增加随机六维
                            $lw_arr = array("hp", "pufang", "pugong", "tegong", "tefang", "minjie");
                            foreach ($lw_arr as $lw_str) {
                                $add_jc = value::get_pet_value2('yuyao.jiacheng.' . $lw_str, $pet_id);
                                if ($add_jc < 30) {
                                    value::add_pet_value2('yuyao.jiacheng.' . $lw_str, mt_rand(1, 2) == 1 ? 0 : 1, $pet_id);
                                }
                            }
                            $fuhuadu = pet::get_fuhuadu($pet_id);
                            echo "你的{$have_egg_name}的孵化度增加了10%,目前进度是{$fuhuadu}%。";
                            br();
                            cmd::addcmd("e114", "查看孵化巢");
                            br();
                        }
                        break;
                    //九丹金液精华 九丹金液精华,使用后可获得20颗九丹金液,九丹金液能给宠物+20忠诚度。
                    case 105:
                        self::add_item(80, 20, true, $user_id, true);
                        break;
                    //复活丸精华 复活丸精华,使用后可以获得10颗复活丸,复活丸能让宠物复活。
                    case 106:
                        self::add_item(83, 10, true, $user_id, true);
                        break;
                }
                //玩家相关
                switch ($item_id) {
                    //补金石 补金神石,使用后可获得1000补金灵气,补金灵气可修补装备耐久。
                    case 81:
                        echo "你熔化了1{$item_liangci}{$item_name},灵气溢出,获得了1000缕补金灵气!";
                        br();
                        value::add_user_value('bujinlingqi', 1000);
                        break;
                    //签名卡 签名之卡,使用后可更改玩家个性签名。
                    case 82:
                        $desc = value::real_escape_string($_POST['desc']);
                        value::set_game_user_value('desc', $desc);
                        echo '设置成功!';
                        br();
                        $can_next_use = false;
                        break;
                    //乾坤袋 乾坤之袋,使用后可选择增加1个永久背包/装备仓库/宠物仓库/宠物袋/育将袋容量。
                    case 85:
                        $rlms = "";
                        switch ($tmp_str) {
                            case 'bb':
                                $rlms = "背包";
                                break;
                            case 'zbck':
                                $rlms = "装备仓库";
                                break;
                            case 'ygck':
                                $rlms = "宠物仓库";
                                break;
                            case 'cyd':
                                $rlms = "宠物袋";
                                break;
                            case 'yyd':
                                $rlms = "育将袋";
                                break;
                        }
                        echo "你扔出了1{$item_liangci}{$item_name},法力涌动,增加了1个{$rlms}永久容量!";
                        br();
                        value::add_user_value("qiankundai.{$tmp_str}.rongliang", 1);
                        break;
                    //装备宝箱 传说中能开出上古神兵利器的装备宝箱。
                    case 87:
                        //随机装备
                        $sj = mt_rand(1, 1000);
                        if ($sj <= 5) {
                            //圣凶 1-5
                            $sji = 7;
                        } else if ($sj <= 20) {
                            //五神 6-10
                            $sji = 6;
                        } else if ($sj <= 50) {
                            //山海经 11-50
                            $sji = 5;
                        } else if ($sj <= 100) {
                            //合成宠物 51-300
                            $sji = 4;
                        } else if ($sj <= 200) {
                            //新将 301-500
                            $sji = 3;
                        } else if ($sj <= 400) {
                            //新将 301-500
                            $sji = 2;
                        } else if ($sj <= 600) {
                            //新将 301-500
                            $sji = 1;
                        } else {
                            //普通 501-1000
                            $sji = 0;
                        }
                        if (mt_rand(1, 5) != 1) {
                            $add_sql = "AND star>4";
                        } else {
                            $add_sql = "AND leixing=3";
                        }
                        //装备id
                        $sql = "SELECT `id`,`liangci`,`name` FROM `prop` WHERE `leixing`>1 AND `leixing`<4 {$add_sql} ORDER BY RAND() LIMIT 1";
                        $result = sql($sql);
                        list($prop_id, $prop_liangci, $prop_name) = $result->fetch_row();
                        //装备星级
                        $prop_star = $sji;
                        $prop_pinzhi = prop::get_star_str($prop_star);
                        //生成装备
                        echo "你打开了1{$item_liangci}{$item_name},金光炫目,获得了1{$prop_liangci}{$prop_pinzhi}{$prop_name}!";
                        br();
                        if (user::get_rongliang() > user::get_fuzhong()) {
                            prop::user_get_prop(prop::new_prop($prop_id, 0, $sji), 0, 1, false, true);
                        } else {
                            prop::user_get_prop(prop::new_prop($prop_id, 0, $sji), 0, 2, false, true);
                            echo "{$prop_name}存入了仓库。";
                            br();
                        }
                        c_add_guangbo("神兵出世,{$user_name}打开{$item_name},获得了1{$prop_liangci}{$prop_pinzhi}{$prop_name}!");
                        break;
                    //宠物宝箱 传说中能开出上古天地灵兽的宠物宝箱。
                    case 88:
                        //随机宠物
                        $xyz = 0;
                        $xyz_ms = "";
                        $ygbx_xyz = value::get_user_value('ygbx.xyz');
                        if ($ygbx_xyz > 0) {
                            $xyz = $ygbx_xyz % 10;
                        }
                        if ($xyz == 0 && $ygbx_xyz > 0) {
                            $xyz_ms = "与日月同辉,汇集了天地灵气";
                        } elseif ($xyz < 3) {
                            $xyz_ms = "发出若隐若现的光芒";
                        } elseif ($xyz < 6) {
                            $xyz_ms = "发出微弱的光芒";
                        } elseif ($xyz < 9) {
                            $xyz_ms = "阵阵光芒闪烁";
                        } elseif ($xyz < 10) {
                            $xyz_ms = "流露出璀璨星光";
                        }
                        echo "{$item_name}{$xyz_ms}。";
                        br();
                        $xingyun = mt_rand(1, 1000);
                        if ($xingyun <= 1 && $xingyun <= 5) {
                            //圣凶 1-5
                            $add_sql = "AND (`is_shengshou`=1 OR `is_xiongshou`=1)";
                        } else if ($xingyun > 5 && $xingyun <= 10) {
                            //五神 6-10
                            $add_sql = "AND `is_wushen`=1";
                        } else if ($xingyun > 10 && $xingyun <= 50) {
                            //山海经 11-50
                            $add_sql = "AND `is_shanhaijing`=1";
                        } else if ($xingyun > 50 && $xingyun <= 300) {
                            //合成宠物 51-300
                            $add_sql = "AND `is_hechengchongwu`=1 AND `is_wushen`=0 AND `is_shanhaijing`=0";
                        } else if ($xingyun > 300 && $xingyun <= 500) {
                            //新将 301-500
                            $add_sql = "AND `is_xinchong`=1 AND `is_hechengchongwu`=0 AND `is_wushen`=0 AND `is_shanhaijing`=0";
                        } else {
                            //普通 501-1000
                            $add_sql = "AND `is_shengshou`=0 AND `is_xiongshou`=0 AND `is_xinchong`=0 AND `is_hechengchongwu`=0 AND `is_wushen`=0 AND `is_shanhaijing`=0";
                        }
                        //宠物id
                        $sql = "SELECT `id` FROM `pet` WHERE `star`>3 $add_sql AND `is_baoxiang_open`=1 ORDER BY RAND() DESC LIMIT 1";
                        $result = sql($sql);
                        list($pet_id) = $result->fetch_row();
                        //赵云 无双赵云
                        if ($pet_id == 1 || $pet_id == 201) {
                            $pet_id = mt_rand(1, 10) == 1 ? $pet_id : 0;
                        }
                        //吕布 无双周瑜
                        if ($pet_id == 5 || $pet_id == 205) {
                            $pet_id = mt_rand(1, 5) == 1 ? $pet_id : 0;
                        }
                        if (!$pet_id) {
                            $add_sql = "AND `is_shengshou`=0 AND `is_xiongshou`=0 AND `is_xinchong`=0 AND `is_hechengchongwu`=0 AND `is_wushen`=0 AND `is_shanhaijing`=0";
                            $sql = "SELECT `id` FROM `pet` WHERE `star`>2 $add_sql AND `is_baoxiang_open`=1 ORDER BY RAND() DESC LIMIT 1";
                            $result = sql($sql);
                            list($pet_id) = $result->fetch_row();
                        }
                        //生成宠物
                        //天赋
                        $texing = -1;
                        if ($xyz == 0 && $ygbx_xyz > 0) {
                            $tx_arr = array(4, 7, 15);
                            if (mb_strstr(value::getvalue("pet", "shuxing", "id", $pet_id), "妖")) {
                                array_push($tx_arr, 50);
                            }
                            $texing = $tx_arr[mt_rand(0, count($tx_arr) - 1)];
                        }
                        //性别
                        $sex = "";
                        if (mt_rand(1, 8) == 1) {
                            $sex = "雌";
                        }
                        //开始生成
                        $new_pet_id = pet::new_pet($pet_id, 1, 0, $user_id, 0, 0, 0, -1, $texing, $sex);
                        $new_pet_name = value::get_pet_value($new_pet_id, 'name');
                        $xgstr = pet::get_xingge(pet::get($new_pet_id, "xingge"));
                        $txstr = pet::get_texing(pet::get($new_pet_id, "texing"), false);
                        //输出提示
                        echo "你打开了1{$item_liangci}{$item_name},惊天动地,从宝箱里蹦出了1个{$xgstr}{$txstr}的{$new_pet_name}!";
                        br();
                        if (pet::user_get_pet($user_id, $new_pet_id, 1, false)) {
                        } else {
                            pet::user_get_pet($user_id, $new_pet_id, 2);
                            echo $new_pet_name . '存入了仓库。';
                            br();
                        }
                        //累计幸运值
                        value::add_user_value('ygbx.xyz', 1);
                        //输出广播
                        c_add_guangbo("猛将现世,{$user_name}打开{$item_name},获得了1个{$new_pet_name}!");
                        break;
                    //金钱宝箱(小) 传说中能开出无数金钱的金钱宝箱(小)。
                    case 89:
                        $money = mt_rand(58, 98);
                        self::add_money($money, $user_id, false);
                        //输出提示
                        echo "你打开了1{$item_liangci}{$item_name},金光闪闪,获得了{$money}个金币!";
                        br();
                        //输出广播
                        c_add_guangbo("天降财宝,{$user_name}打开{$item_name},获得了{$money}个金币!");
                        break;
                    //金钱宝箱(中) 传说中能开出无数金钱的金钱宝箱(中)。
                    case 90:
                        $money = mt_rand(158, 258);
                        self::add_money($money, $user_id, false);
                        //输出提示
                        echo "你打开了1{$item_liangci}{$item_name},金光闪闪,获得了{$money}个金币!";
                        br();
                        //输出广播
                        c_add_guangbo("天降财宝,{$user_name}打开{$item_name},获得了{$money}个金币!");
                        break;
                    //金钱宝箱(大) 传说中能开出无数金钱的金钱宝箱(大)。
                    case 91:
                        $money = mt_rand(328, 528);
                        self::add_money($money, $user_id, false);
                        //输出提示
                        echo "你打开了1{$item_liangci}{$item_name},金光闪闪,获得了{$money}个金币!";
                        br();
                        //输出广播
                        c_add_guangbo("天降财宝,{$user_name}打开{$item_name},获得了{$money}个金币!");
                        break;
                    //双倍经验精华 双倍经验精华,使用后可获得100次双倍经验次数,双倍经验次数可用于NPC与野怪。
                    case 92:
                        echo "你吸收了1{$item_liangci}{$item_name},仙气环绕,获得了100次双倍经验次数!";
                        br();
                        value::add_user_value('shuangbeijingyancishu', 100);
                        break;
                    //金币情侣徽章 寓意着天长地久,金币婚及以上的情侣可以使用,双方可以获得金币情侣卷轴(使用后可选择获得金币情侣称号)与不会掉落的金币情侣装备。
                    case 93:
                        $bl_item_id = 101;
                        $bl_item_liangci = value::get_item_value($bl_item_id, 'liangci');
                        $bl_item_name = value::get_item_value($bl_item_id, 'name');
                        $bl_prop_id = 58;
                        $bl_prop_liangci = value::get_prop_value($bl_prop_id, 'liangci');
                        $bl_prop_name = value::get_prop_value($bl_prop_id, 'name');
                        //输出提示
                        echo "你对{$bl_name}使用了1{$item_liangci}{$item_name},祝你们朝丝暮雪,百年好合!";
                        br();
                        //自己获得
                        prop::user_get_prop(prop::new_prop($bl_prop_id, 0, 3), $user_id, 1, true, true);
                        self::add_item($bl_item_id, 1, true, $user_id, true);
                        //伴侣获得
                        self::add_item($bl_item_id, 1, false, $bl_id, true);
                        prop::user_get_prop(prop::new_prop($bl_prop_id, 0, 3), $bl_id, 1, false, true);
                        //输出消息
                        c_add_xiaoxi("{$user_name}对你使用了{$bl_item_name},祝你们朝丝暮雪,百年好合!<br>你获得了1{$bl_prop_liangci}{$bl_prop_name}。<br>你获得了1{$bl_item_liangci}{$bl_item_name}。", 0, $user_id, $bl_id);
                        //输出广播
                        c_add_guangbo("情意绵绵,{$user_name}与{$bl_name}携手打开了{$item_name}!");
                        //输出广播
                        break;
                    //黄金情侣徽章 寓意着天长地久,黄金婚及以上的情侣可以使用,双方可以获得黄金情侣卷轴(使用后可选择获得黄金情侣称号)与不会掉落的黄金情侣装备。
                    case 94:
                        $bl_item_id = 102;
                        $bl_item_liangci = value::get_item_value($bl_item_id, 'liangci');
                        $bl_item_name = value::get_item_value($bl_item_id, 'name');
                        $bl_prop_id = 59;
                        $bl_prop_liangci = value::get_prop_value($bl_prop_id, 'liangci');
                        $bl_prop_name = value::get_prop_value($bl_prop_id, 'name');
                        //输出提示
                        echo "你对{$bl_name}使用了1{$item_liangci}{$item_name},祝你们朝丝暮雪,百年好合!";
                        br();
                        //自己获得
                        prop::user_get_prop(prop::new_prop($bl_prop_id, 0, 3), $user_id, 1, true, true);
                        self::add_item($bl_item_id, 1, true, $user_id, true);
                        //伴侣获得
                        self::add_item($bl_item_id, 1, false, $bl_id, true);
                        prop::user_get_prop(prop::new_prop($bl_prop_id, 0, 3), $bl_id, 1, false, true);
                        //输出消息
                        c_add_xiaoxi("{$user_name}对你使用了{$bl_item_name},祝你们朝丝暮雪,百年好合!<br>你获得了1{$bl_prop_liangci}{$bl_prop_name}。<br>你获得了1{$bl_item_liangci}{$bl_item_name}。", 0, $user_id, $bl_id);
                        //输出广播
                        c_add_guangbo("情意绵绵,{$user_name}与{$bl_name}携手打开了{$item_name}!");
                        break;
                    //水晶情侣徽章 寓意着天长地久,水晶婚及以上的情侣可以使用,双方可以获得水晶情侣卷轴(使用后可选择获得水晶情侣称号)与不会掉落的水晶情侣装备。
                    case 95:
                        $bl_item_id = 103;
                        $bl_item_liangci = value::get_item_value($bl_item_id, 'liangci');
                        $bl_item_name = value::get_item_value($bl_item_id, 'name');
                        $bl_prop_id = 60;
                        $bl_prop_liangci = value::get_prop_value($bl_prop_id, 'liangci');
                        $bl_prop_name = value::get_prop_value($bl_prop_id, 'name');
                        //输出提示
                        echo "你对{$bl_name}使用了1{$item_liangci}{$item_name},祝你们朝丝暮雪,百年好合!";
                        br();
                        //自己获得
                        self::add_item($bl_item_id, 1, true, $user_id, true);
                        prop::user_get_prop(prop::new_prop($bl_prop_id, 0, 3), $user_id, 1, true, true);
                        //伴侣获得
                        prop::user_get_prop(prop::new_prop($bl_prop_id, 0, 3), $bl_id, 1, true, true);
                        self::add_item($bl_item_id, 1, true, $bl_id, true);
                        //输出消息
                        c_add_xiaoxi("{$user_name}对你使用了{$bl_item_name},祝你们朝丝暮雪,百年好合!<br>你获得了1{$bl_prop_liangci}{$bl_prop_name}。<br>你获得了1{$bl_item_liangci}{$bl_item_name}。", 0, $user_id, $bl_id);
                        //输出广播
                        c_add_guangbo("情意绵绵,{$user_name}与{$bl_name}携手打开了{$item_name}!");
                        break;
                    //钻石情侣徽章 寓意着天长地久,钻石婚及以上的情侣可以使用,双方可以获得钻石情侣卷轴(使用后可选择获得钻石情侣称号)与不会掉落的钻石情侣装备。
                    case 96:
                        $bl_item_id = 104;
                        $bl_item_liangci = value::get_item_value($bl_item_id, 'liangci');
                        $bl_item_name = value::get_item_value($bl_item_id, 'name');
                        $bl_prop_id = 61;
                        $bl_prop_liangci = value::get_prop_value($bl_prop_id, 'liangci');
                        $bl_prop_name = value::get_prop_value($bl_prop_id, 'name');
                        //输出提示
                        echo "你对{$bl_name}使用了1{$item_liangci}{$item_name},祝你们朝丝暮雪,百年好合!";
                        br();
                        //自己获得
                        prop::user_get_prop(prop::new_prop($bl_prop_id, 0, 3), $user_id, 1, true, true);
                        self::add_item($bl_item_id, 1, true, $user_id, true);
                        //伴侣获得
                        self::add_item($bl_item_id, 1, false, $bl_id, true);
                        prop::user_get_prop(prop::new_prop($bl_prop_id, 0, 3), $bl_id, 1, false, true);
                        //输出消息
                        c_add_xiaoxi("{$user_name}对你使用了{$bl_item_name},祝你们朝丝暮雪,百年好合!<br>你获得了1{$bl_prop_liangci}{$bl_prop_name}。<br>你获得了1{$bl_item_liangci}{$bl_item_name}。", 0, $user_id, $bl_id);
                        //输出广播
                        c_add_guangbo("情意绵绵,{$user_name}与{$bl_name}携手打开了{$item_name}!");
                        break;
                    //金币情侣卷轴 金币婚及以上的情侣可以使用,使用后可选择获得金币情侣称号。
                    case 101:
                        e17($tmp_str, 2, true);
                        break;
                    //黄金情侣卷轴 黄金婚及以上的情侣可以使用,使用后可选择获得黄金情侣称号。
                    case 102:
                        e17($tmp_str, 2, true);
                        break;
                    //水晶情侣卷轴 水晶婚及以上的情侣可以使用,使用后可选择获得水晶情侣称号。
                    case 103:
                        e17($tmp_str, 2, true);
                        break;
                    //钻石情侣卷轴 钻石婚及以上的情侣可以使用,使用后可选择获得钻石情侣称号。
                    case 104:
                        e17($tmp_str, 2, true);
                        break;
                    //御心 镜花水月,终究离碎无欢。我持御心,看破万千变幻。
                    case 107:
                        if ($tmp_str) {
                            value::set_user_value("last_yuxin_time", $now_time);
                            value::set_game_user_value('in_map_id', $tmp_str);
                            $map_name = value::get_map_value($tmp_str, 'name');
                            echo "御心之中灵光一闪,你传送到了{$map_name}。";
                            br();
                        }
                        break;
                    //纵横传奇礼包
                    case 114:
                        echo "你打开了1{$item_liangci}{$item_name},海阔天空,开始了传奇的传奇之旅!";
                        br();
                        //输出广播
                        c_add_guangbo("海阔天空,{$user_name}打开{$item_name},开始了传奇的传奇之旅!");
                        //获得8本通关秘籍 13种进化瑰宝 8颗888888经验内丹
                        for ($i = 54; $i < 75; $i++) {
                            self::add_item($i, 1, true, $user_id, true);
                        }
                        for ($i = 0; $i < 8; $i++) {
                            prop::user_get_prop(prop::new_prop(1, 888), $user_id, 1, false, true);
                        }
                        echo "你获得了8颗存有888经验的仙丹。";
                        br();
                        break;
                    //饺子
                    case 115:
                        echo "你一口吃完了饺子,冬至快乐!";
                        br();
                        self::add_money(12);
                        if (mt_rand(1, 20) == 1) {
                            self::add_item(mt_rand(87, 88), 1);
                        }
                        break;
                    //汤圆
                    case 116:
                        echo "你慢慢吃下了汤圆,合家团圆!";
                        br();
                        self::add_lingshi(mt_rand(1, 5));
                        if (mt_rand(1, 20) == 1) {
                            self::add_item(mt_rand(88, 89), 1);
                        }
                        break;
                    //苹果
                    case 117:
                        echo "你津津有味的吃下了苹果,平平安安!";
                        br();
                        self::add_lingshi(mt_rand(1, 5));
                        if (mt_rand(1, 20) == 1) {
                            self::add_item(mt_rand(87, 89), 1);
                        }
                        break;
                    //幸运爆竹
                    case 118:
                        echo "爆竹声中辞旧岁,祝您幸运快乐!{$item_name}中下10只宠物为:";
                        $baozhu_pet_arr = json_decode(value::get_user_value("baozhu_pet_arr"), true);
                        if (!$baozhu_pet_arr[0]) {
                            $sql = "SELECT `id` FROM `pet` WHERE `exp`>=41 AND `is_baoxiang_open`=1 ORDER BY RAND() DESC LIMIT 1";
                            $result = sql($sql);
                            list($baozhu_pet_id) = $result->fetch_row();
                            //赵云 无双赵云 吕布 无双周瑜
                            if ($baozhu_pet_id == 1 || $baozhu_pet_id == 201 || $baozhu_pet_id == 5 || $baozhu_pet_id == 205) {
                                $baozhu_pet_id = mt_rand(2, 4);
                            }
                            $baozhu_pet_arr = array();
                            $sql = "SELECT `id` FROM `pet` WHERE `exp`>=38 AND `is_baoxiang_open`=1 ORDER BY RAND() DESC LIMIT 5";
                            $result = sql($sql);
                            while (list($pet_id) = $result->fetch_row()) {
                                //赵云 无双赵云  吕布 无双周瑜
                                if ($pet_id == 1 || $pet_id == 201 || $pet_id == 5 || $pet_id == 205) {
                                    $pet_id = mt_rand(9, 13);
                                }
                                array_push($baozhu_pet_arr, $pet_id);
                            }
                            $sql = "SELECT `id` FROM `pet` WHERE `exp`>=50 AND `is_baoxiang_open`=1 ORDER BY RAND() DESC LIMIT 5";
                            $result = sql($sql);
                            while (list($pet_id) = $result->fetch_row()) {
                                //赵云 无双赵云  吕布 无双周瑜
                                if ($pet_id == 1 || $pet_id == 201 || $pet_id == 5 || $pet_id == 205) {
                                    $pet_id = mt_rand(6, 8);
                                }
                                //加入数组
                                array_push($baozhu_pet_arr, $pet_id);
                            }
                        } else {
                            $baozhu_pet_id = array_shift($baozhu_pet_arr);
                            $sql = "SELECT `id` FROM `pet` WHERE `exp`>=38 AND `is_baoxiang_open`=1 ORDER BY RAND() DESC LIMIT 1";
                            $result = sql($sql);
                            list($pet_id) = $result->fetch_row();
                            //赵云 无双赵云
                            if ($pet_id == 1 || $pet_id == 201) {
                                $pet_id = mt_rand(1, 20) == 1 ? $pet_id : mt_rand(2, 4);
                            }
                            //吕布 无双周瑜
                            if ($pet_id == 5 || $pet_id == 205) {
                                $pet_id = mt_rand(1, 10) == 1 ? $pet_id : mt_rand(6, 8);
                            }
                            array_push($baozhu_pet_arr, $pet_id);
                        }
                        value::set_user_value("baozhu_pet_arr", json_encode($baozhu_pet_arr));
                        $j = 0;
                        foreach ($baozhu_pet_arr as $pet_id) {
                            echo $j ? "," : "", value::getvalue("pet", 'name', 'id', $pet_id);
                            $j++;
                        }
                        echo "。";
                        br();
                        //元宝
                        self::add_lingshi(mt_rand(1, 2) == 1 ? 8 : 88);
                        //宠物
                        //开始生成
                        $sex = "";
                        if (mt_rand(1, 6) == 1) {
                            $sex = "雌";
                        }
                        $tx_arr = array(4, 7, 15);
                        if (mb_strstr(value::getvalue("pet", "shuxing", "id", $baozhu_pet_id), "妖")) {
                            array_push($tx_arr, 50);
                        }
                        $texing = $tx_arr[mt_rand(0, count($tx_arr) - 1)];
                        $new_pet_id = pet::new_pet($baozhu_pet_id, 1, 0, $user_id, 0, 0, 0, -1, $texing, $sex);
                        $new_pet_name = value::get_pet_value($new_pet_id, 'name');
                        $xgstr = pet::get_xingge(pet::get($new_pet_id, "xingge"));
                        $txstr = pet::get_texing(pet::get($new_pet_id, "texing"), false);
                        //输出提示
                        echo "你打开了1{$item_liangci}{$item_name},惊天动地,从爆竹里蹦出了1只{$xgstr}{$txstr}的{$new_pet_name}!";
                        br();
                        if (pet::user_get_pet($user_id, $new_pet_id, 1, false)) {
                        } else {
                            pet::user_get_pet($user_id, $new_pet_id, 2);
                            echo $new_pet_name . '存入了仓库。';
                            br();
                        }
                        //输出广播
                        c_add_guangbo("爆竹声中辞旧岁,{$user_name}打开{$item_name},获得了1只{$new_pet_name}!");
                        //累计幸运值
                        value::add_user_value('xcbz.xyz', 1);
                        $xyz = value::get_user_value('xcbz.xyz');
                        switch ($xyz) {
                            case 18:
                                $new_pet_arr = array(4, 6);
                                break;
                            case 28:
                                $new_pet_arr = array(2, 7);
                                break;
                            case 38:
                                $new_pet_arr = array(3, 8);
                                break;
                            case 188:
                                $new_pet_arr = array(5);
                                break;
                            case 888:
                                $new_pet_arr = array(1);
                                break;
                        }
                        foreach ($new_pet_arr as $baozhu_pet_id) {
                            $sex = "";
                            $new_pet_id = pet::new_pet($baozhu_pet_id, 1, 0, $user_id, 0, 0, 0, -1, -1, $sex);
                            $new_pet_name = value::get_pet_value($new_pet_id, 'name');
                            $xgstr = pet::get_xingge(pet::get($new_pet_id, "xingge"));
                            $txstr = pet::get_texing(pet::get($new_pet_id, "texing"), false);
                            //输出提示
                            echo "幸运突破天际,爆竹里蹦出了1只{$xgstr}{$txstr}的{$new_pet_name}!";
                            br();
                            if (pet::user_get_pet($user_id, $new_pet_id, 1, false)) {
                            } else {
                                pet::user_get_pet($user_id, $new_pet_id, 2);
                                echo $new_pet_name . '存入了仓库。';
                                br();
                            }
                            //输出广播
                            c_add_guangbo("爆竹声中辞旧岁,{$user_name}打开{$item_name},获得了1只{$new_pet_name}!");
                        }
                        //神兵
                        if ($xyz % 2 == 0) {
                            //随机装备
                            if (mt_rand(1, 10) != 1) {
                                $add_sql = "AND star>5";
                            } else {
                                $add_sql = "AND leixing=3";
                            }
                            //装备id
                            $sql = "SELECT `id`,`liangci`,`name` FROM `prop` WHERE `leixing`>1 AND `leixing`<4 {$add_sql} ORDER BY RAND() LIMIT 1";
                            $result = sql($sql);
                            list($prop_id, $prop_liangci, $prop_name) = $result->fetch_row();
                            //装备星级
                            $prop_star = 5;
                            $prop_pinzhi = prop::get_star_str($prop_star);
                            //生成装备
                            echo "好运连连,爆竹中金光一闪,你获得了1{$prop_liangci}{$prop_pinzhi}{$prop_name}!";
                            br();
                            if (user::get_rongliang() > user::get_fuzhong()) {
                                prop::user_get_prop(prop::new_prop($prop_id, 0, $prop_star), 0, 1, false, true);
                            } else {
                                prop::user_get_prop(prop::new_prop($prop_id, 0, $prop_star), 0, 2, false, true);
                                echo "{$prop_name}存入了仓库。";
                                br();
                            }
                            c_add_guangbo("爆竹声中辞旧岁,{$user_name}打开{$item_name},获得了1{$prop_liangci}{$prop_pinzhi}{$prop_name}!");
                        }
                        break;
                }
                if ($can_next_use && self::get_item($item_id)) {
                    cmd::addcmd("e55,$item_id", '继续使用');
                    br();
                }
            }
        } else {
            if ($need_error_miaoshu) {
                if ($error_miaoshu) {
                    echo $error_miaoshu;
                } else {
                    echo "你无法使用这{$item_liangci}{$item_name}。";
                    br();
                }
            }
        }
    }
}

//宠物类
class pet
{
    //创建宠物(宠物种族id,等级,所在地图[为0不出现在地图],所属玩家[为0没有所属玩家])
    static function new_pet($pet_id, $lvl, $map_id = 0, $user_id = 0, $npc_id = 0, $enemy_user = 0, $team_id = 0, $xingge = -1, $texing = -1, $sex = "", $chujing = 0, $sex_qiangzhi = false)
    {
        //获取名字 描述 宠物属性
        $name = value::getvalue('pet', 'name', 'id', $pet_id);
        $desc = value::getvalue('pet', 'desc', 'id', $pet_id);
        $is_yj = value::getvalue('pet', 'is_yj', 'id', $pet_id);
        //获取初始经验值
        if (!$chujing) {
            $chujing = value::getvalue('pet', 'exp', 'id', $pet_id);
        }
        //获取种族值
        $hp = value::getvalue('pet', 'hp', 'id', $pet_id);
        $sex = value::getvalue('pet', 'sex', 'id', $pet_id);
        $zhiye = value::getvalue('pet', 'zhiye', 'id', $pet_id);
        $pugong = value::getvalue('pet', 'pugong', 'id', $pet_id);
        $pufang = value::getvalue('pet', 'pufang', 'id', $pet_id);
        $tegong = value::getvalue('pet', 'tegong', 'id', $pet_id);
        $tefang = value::getvalue('pet', 'tefang', 'id', $pet_id);
        $minjie = value::getvalue('pet', 'minjie', 'id', $pet_id);
        $ll = 0;
        $nl = 0;
        $tz = 0;
        $ts_mb = value::getvalue('pet', 'ts_mb', 'id', $pet_id);
        $ts_xx = value::getvalue('pet', 'ts_xx', 'id', $pet_id);
        $ts_zd = value::getvalue('pet', 'ts_zd', 'id', $pet_id);
        //设置最高等级 合成次数
        $star = value::getvalue('pet', 'star', 'id', $pet_id);
        $money = value::getvalue('pet', 'money', 'id', $pet_id) + $lvl * $star * 10 + $lvl;
        $tf = mt_rand(1, 6);
        $sx = mt_rand(1, 5);
        $czl = mt_rand(1, 102);
        if ($czl > 99) {
            $czl = 100;
        }
        $hechongcishu = 0;
        //设置六维
        $lvl++;
        $max_hp = $hp + 10 * $lvl;
        //野怪HP修正
        if ($user_id < 1) {
            $ll = value::getvalue('pet', 'll', 'id', $pet_id);
            $nl = value::getvalue('pet', 'nl', 'id', $pet_id);
            $tz = value::getvalue('pet', 'tz', 'id', $pet_id);
            $pugong = value::getvalue('pet', 'pugong', 'id', $pet_id) + $ll;
            $pufang = value::getvalue('pet', 'pufang', 'id', $pet_id) + $nl;
            $tegong = value::getvalue('pet', 'tegong', 'id', $pet_id) + $ll;
            $tefang = value::getvalue('pet', 'tefang', 'id', $pet_id) + $nl;
            $minjie = value::getvalue('pet', 'minjie', 'id', $pet_id);
            $max_hp = $max_hp + $tz;
        } else {
            $chujing = 100;
        }
        //组队HP修正
        if ($team_id) {
            //队伍成员数量
            $team_user_count = team::get_team_user_count($team_id);
            //最高三倍血量
            $max_hp *= $team_user_count > 3 ? 3 : $team_user_count;
        }
        $hp = $max_hp;
        //设置经验
        $max_exp = $chujing;
        $exp_lvl = (int)($lvl / 10);
        $exp_lvl1 = ($lvl - ($exp_lvl * 10)) * 0.17;
        if ($exp_lvl < 1) {
            $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $lvl * 10;
        } else {
            $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + 100;
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 1) {
            if ($exp_lvl > 1) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 2) {
            if ($exp_lvl > 2) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 3) {
            if ($exp_lvl > 3) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 4) {
            if ($exp_lvl > 4) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 5) {
            if ($exp_lvl > 5) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 6) {
            if ($exp_lvl > 6) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 7) {
            if ($exp_lvl > 7) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 8) {
            if ($exp_lvl > 8) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 9) {
            if ($exp_lvl > 9) {
                $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 1.7));
            } else {
                $exp = value::getvalue('pet', 'exp', 'id', $pet_id) + $exp * $exp_lvl1;
            }
        }
        $exp = (int)($exp); 
        if ($exp_lvl > 10) {
            $exp = (value::getvalue('pet', 'exp', 'id', $pet_id) + ($exp * 2));
        }
        $exp = (int)($exp); 
        $exp1 = value::getvalue('pet', 'exp1', 'id', $pet_id);
        if ($exp1 > 1) {
            $exp = $exp * $exp1; 
        }
        $exp = (int)($exp); 
        //羽翼试练特殊
        if ($map_id > 5364 && $map_id < 5565) {
            $i_sl = 0;
            for ($i = 5365; $i < 5564; $i++) {
                if ($map_id >= $i) {
                    $i_sl++;
                }
            }
            $ll *= $i_sl;
            $nl *= $i_sl;
            $tz *= $i_sl;
            if ($i_sl == 20 || $i_sl == 40 || $i_sl == 60 || $i_sl == 80 || $i_sl == 100 || $i_sl == 120 || $i_sl == 140 || $i_sl == 160 || $i_sl == 180 || $i_sl == 200) {
                $ll += $ll;
                $nl += $nl;
                $tz += $tz;
            }
            $pugong = value::getvalue('pet', 'pugong', 'id', $pet_id) + $ll;
            $pufang = value::getvalue('pet', 'pufang', 'id', $pet_id) + $nl;
            $tegong = value::getvalue('pet', 'tegong', 'id', $pet_id) + $ll;
            $tefang = value::getvalue('pet', 'tefang', 'id', $pet_id) + $nl;
            $max_hp = $max_hp + $tz;
            $hp = $max_hp;
        }
        //天梯特殊怪物特别设置
        if ($pet_id == 218) {
            $user_id1 = uid();
            $tz_tdxt_id = value::get_user_value('tz_tdxt_id', $user_id1);
            $tz_tdxt_id1 = value::get_system_value('tz_tdxt_id' . $tz_tdxt_id);
            $hp += value::get_game_user_value('max_hp', $tz_tdxt_id1);
            $max_hp = $hp;
            $pugong += value::get_user_value('xt_ttbx_gj', $tz_tdxt_id1);
            $pufang += value::get_user_value('xt_ttbx_fy', $tz_tdxt_id1);
            $name = value::get_game_user_value('name', $tz_tdxt_id1);
        }
        //开始创建宠物
        $sql = "INSERT INTO `game_pet` (`pet_id`,`is_yj`,`map_id`,`master_id`,`name`,`sex`,`zhiye`,`lvl`,`exp`,`exp1`,`money`,`ll`,`nl`,`tz`,`star`,`tf`,`sx`,`czl`,`max_exp`,`chujing`,`hp`,`max_hp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`ts_mb`,`ts_xx`,`ts_zd`,`npc_id`,`enemy_user`,`team_id`) VALUES ('{$pet_id}','{$is_yj}','{$map_id}','{$user_id}','{$name}','{$sex}','{$zhiye}','{$lvl}','{$exp}','{$exp1}','{$money}','{$ll}','{$nl}','{$tz}','{$star}','{$tf}','{$sx}','{$czl}','{$max_exp}','{$chujing}','{$hp}','{$max_hp}','{$pugong}','{$pufang}','{$tegong}','{$tefang}','{$minjie}','{$ts_mb}','{$ts_xx}','{$ts_zd}','{$npc_id}','{$enemy_user}','{$team_id}')";
        sql($sql);
        $result = sql("SELECT LAST_INSERT_ID()");
        list($new_pet_id) = $result->fetch_row();
        //学习初始技能
        if (!$map_id && !$npc_id && $lvl <= 5) {
            //读取种族技能
            $p_study_skill_str = value::getvalue('pet', 'study_skill', 'id', $pet_id);
            $p_study_skill_arr = explode(',', $p_study_skill_str);
            //学习初始技能
            skill::study_skill($new_pet_id, $p_study_skill_arr[0], 0, false);
        }
        //返回宠物ID
        return $new_pet_id;
    }

    //查看宠物
    static function show_pet($oid, $mode = 0)
    {
        //获取宠物属性
        $sql = "SELECT `pet_id`,`name`,`zhiye`,`tf`,`sx`,`czl`,`sex`,`is_yj`,`nick_name`,`lvl`,`max_lvl`,`hp`,`max_hp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`exp`,`max_exp`,`shuxingdian`,`zhongcheng`,`zhuangtai`,`xingge`,`texing`,`zhuansheng`,`is_dead`,`hechongcishu` FROM `game_pet` WHERE `id`={$oid} LIMIT 1";
        $result = sql($sql);
        list($ozzid, $oname, $zhiye, $tf, $sx, $czl, $osex, $is_yj, $nick_name, $lvl, $max_lvl, $hp, $max_hp, $pugong, $pufang, $tegong, $tefang, $minjie, $exp, $max_exp, $shuxingdian, $zhongcheng, $zhuangtai, $xingge, $texing, $zhuansheng, $is_dead, $hechongcishu) = $result->fetch_row();
        $hp = (int)$hp;
        $max_hp = (int)$max_hp;
        $fuhao = ($osex == '' ? "" : "");
        //六维加成
        $pugong_jc = pet::get_liuwei_jiacheng($oid, 'pugong');
        $pufang_jc = pet::get_liuwei_jiacheng($oid, 'pufang');
        $tegong_jc = pet::get_liuwei_jiacheng($oid, 'tegong');
        $tefang_jc = pet::get_liuwei_jiacheng($oid, 'tefang');
        $minjie_jc = pet::get_liuwei_jiacheng($oid, 'minjie');
        $hp_jc = pet::get_liuwei_jiacheng($oid, 'hp');
        //获取种族属性
        $sql = "SELECT `image`,`desc`,`star`,`wl`,`ty`,`zl`,`zz`,`ml`,`ys`,`shuxing`,`study_lvl`,`jh_id`,`jh_lvl` FROM `pet` WHERE `id`={$ozzid}" . " LIMIT 1";
        $result = sql($sql);
        list($image, $desc, $star, $wl, $ty, $zl, $zz, $ml, $ys, $shuxing, $study_lvl, $jh_id, $jh_lvl) = $result->fetch_row();
        if (!$oname) {
            //返回地图
            echo '你已经没有这只宠物了。';
            br();
            e35();
            return;
        }
        //输出宠物星级
        if ($star <= 1) {
            $xj = "一";
        } else if ($star == 2) {
            $xj = "二";
        } else if ($star == 3) {
            $xj = "三";
        } else if ($star == 4) {
            $xj = "四";
        } else if ($star == 5) {
            $xj = "五";
        } else if ($star == 6) {
            $xj = "六";
        } else if ($star == 7) {
            $xj = "七";
        } else if ($star == 8) {
            $xj = "八";
        } else if ($star == 9) {
            $xj = "九";
        } else if ($star == 10) {
            $xj = "十";
        } else {
            $xj = "神级";
        }
        echo $xj . "星：" . $oname . ($nick_name ? "({$nick_name})" : "") . ($is_dead ? "(已死亡)" : "");
        br();
        //输出图片 描述
        if (!value::get_user_value('kg.xscwtp')) {
            if ($image != "") {
                pet::img($image, true, false);
                br();
            }
        }
        $xs_max_hp = $hp_jc + $max_hp;
        $xs_pugong = $pugong_jc + $pugong;
        $xs_pufang = $pufang_jc + $pufang;
        $xs_tegong = $tegong_jc + $tegong;
        $xs_tefang = $tefang_jc + $tefang;
        $xs_minjie = $minjie_jc + $minjie;
        echo "等级：" . $lvl;
        br();
        if ($sx <= 1) {
            $shuxing = "金";
        } else if ($sx == 2) {
            $shuxing = "木";
        } else if ($sx == 3) {
            $shuxing = "水";
        } else if ($sx == 4) {
            $shuxing = "火";
        } else if ($sx == 5) {
            $shuxing = "土";
        } else {
            $shuxing = "无";
        }
        if ($shuxing) {
            echo "五行：" . $shuxing;
        } else {
            echo "五行：无";
        }
        br();
        if ($czl == 100) {
            $czl_mz = "(极)";
        } else if ($czl >= 90) {
            $czl_mz = "(珍)";
        } else if ($czl >= 75) {
            $czl_mz = "(优)";
        } else {
            $czl_mz = "(普)";
        }
        if ($czl) {
            echo "成长率：" . $czl;
            echo "  ";
            echo $czl_mz;
        } else {
            echo "成长率：无";
        }
        br();
        if ($tf <= 1) {
            $tf_shuxing = "生命型";
        } else if ($tf == 2) {
            $tf_shuxing = "攻击型";
        } else if ($tf == 3) {
            $tf_shuxing = "防御型";
        } else if ($tf == 4) {
            $tf_shuxing = "魔攻型";
        } else if ($tf == 5) {
            $tf_shuxing = "魔防型";
        } else if ($tf == 6) {
            $tf_shuxing = "灵活型";
        } else {
            $tf_shuxing = "无";
        }
        if ($tf_shuxing) {
            echo "天赋：" . $tf_shuxing;
        } else {
            echo "天赋：无";
        }
        br();
        echo "经验：" . $exp . "/" . $max_exp;
        br();
        echo "生命：" . $hp . "/" . $xs_max_hp . " " . "忠诚度:" . $zhongcheng;
        br();
        if ($desc != "") {
            echo $desc;
        } else {
            echo "这是一只" . $oname . "。";
        }
        br();
        //输出等级 六维 属性 属性点 忠诚度 状态 性格 天赋
        $yj_xs = ($is_yj < 1) ? "临时":"永久";
        echo "<span style='color: green'><style>td,th{text-align:center;font-size:18px;}</style><table  border='0'><tbody>
        <tr><td>攻击：</td><th>{$xs_pugong}</th><td>防御：</td><th>{$xs_pufang}</th></tr>
        <tr><td>魔攻：</td><th>{$xs_tegong}</th><td>魔防：</td><th>{$xs_tefang}</th></tr>
        <tr><td>灵活：</td><th>{$xs_minjie}</th><td>状态：</td><th>{$yj_xs}</th></tr>
        </tbody></table></span>";
        //获取装配道具
        $equip_liuwei_id_arr = pet::get_prop_id($oid, 2);
        if (is_array($equip_liuwei_id_arr)) {
            $equip_subtype_arr = config::getConfigByName("equip_subtype");
            foreach ($equip_liuwei_id_arr as $equip_id) {
                $equip_name = prop::get($equip_id, 'name');
                $star1 = prop::get($equip_id, 'star1');
                $equip_subtype = value::get_game_prop_zz_value($equip_id, 'sub_leixing');
                echo $equip_subtype_arr[$equip_subtype], ':';
                cmd::addcmd("e98,{$equip_id},2", $equip_name);
                br();
            }
        }
        $equip_baowu_id = pet::get_prop_id($oid, 3);
        if ($equip_baowu_id) {
            $equip_baowu_name = prop::get($equip_baowu_id, 'name');
            echo '宝物:';
            cmd::addcmd("e98,{$equip_baowu_id},2", $equip_baowu_name);
            br();
        }
    }

    //设置宠物属性函数
    static function set($pet_id, $valuename, $value)
    {
        return value::set_pet_value($pet_id, $valuename, $value);
    }

    //获取宠物属性函数
    static function get($pet_id, $valuename)
    {
        return value::get_pet_value($pet_id, $valuename);
    }

    //获取宠物种族属性函数
    static function get_zz($pet_id, $valuename)
    {
        return value::get_pet_zz_value($pet_id, $valuename);
    }

    //获取多个宠物属性函数
    static function mget($pet_id, $valuename_array)
    {
        $value_array = array();
        foreach ($valuename_array as $valuename) {
            array_push($value_array, self::get($pet_id, $valuename));
        }
        return $value_array;
    }

    //获取性格名称
    static function get_xingge($xingge)
    {
        $xingge_arr = config::getConfigByName("xingge");
        return $xingge_arr[$xingge];
    }

    //获取性格名称
    static function get_xingge1($xingge, $need_miaoshu = true)
    {
        //全部共有天赋
        $xingge_str_arr = config::getConfigByName("xingge1");
        $xingge_str = $xingge_str_arr[$xingge];
        //返回天赋文本
        if ($need_miaoshu) {
            return $xingge_str;
        } else {
            $xingge_arr = explode(':', $xingge_str);
            return $xingge_arr[0];
        }
    }

    //获取天赋名称
    static function get_texing($texing, $need_miaoshu = true)
    {
        //全部共有天赋
        $tx_str_arr = config::getConfigByName("texing");
        $tx_str = $tx_str_arr[$texing];
        //返回天赋文本
        if ($need_miaoshu) {
            return $tx_str;
        } else {
            $tx_arr = explode(':', $tx_str);
            return $tx_arr[0];
        }
    }

    //获取状态文本
    static function get_zhuangtai($zhuangtai)
    {
        $t_arr = config::getConfigByName("zhuangtai");
        $zhuangtai_name = $t_arr[$zhuangtai];
        return $zhuangtai_name;
    }

    //获取孵化度
    static function get_fuhuadu($pet_id)
    {
        $zongfuhuadu = value::get_pet_zz_value($pet_id, 'exp') * 3600;
        $fuhuadu = value::get_pet_value($pet_id, 'fuhuadu');
        $r_fuhuadu = (int)($fuhuadu / $zongfuhuadu * 100);
        if ($r_fuhuadu > 100) {
            $r_fuhuadu = 100;
        }
        return $r_fuhuadu;
    }

    //刷新成长度
    static function refresh_chengzhangdu($pet_id)
    {
        $now_time = time();
        $last_chengzhang_time = value::get_pet_value2('last.chengzhang.time', $pet_id);
        if ($last_chengzhang_time) {
            value::add_pet_value($pet_id, 'chengzhangdu', $now_time - $last_chengzhang_time, false, 1);
        }
        value::set_pet_value2('last.chengzhang.time', $now_time, $pet_id);
    }

    //获取成长度
    static function get_chengzhangdu($pet_id)
    {
        $zongchengzhangdu = value::get_pet_zz_value($pet_id, 'exp') * 3600 * 5;
        $chengzhangdu = value::get_pet_value($pet_id, 'chengzhangdu');
        $r_chengzhangdu = (int)($chengzhangdu / $zongchengzhangdu * 100);
        if ($r_chengzhangdu > 100) {
            $r_chengzhangdu = 100;
        }
        return $r_chengzhangdu;
    }

    //刷新饱食度
    static function refresh_baoshidu($pet_id)
    {
        $now_time = time();
        $last_baoshi_time = value::get_pet_value2('last.baoshi.time', $pet_id);
        if ($last_baoshi_time) {
            $baoshidu = value::add_pet_value($pet_id, 'baoshidu', -1 * ($now_time - $last_baoshi_time), false, 1);
            if ($baoshidu < 1) {
                value::set_pet_value($pet_id, 'baoshidu', 0);
            }
        }
        value::set_pet_value2('last.baoshi.time', $now_time, $pet_id);
    }

    //获取饱食度
    static function get_baoshidu($pet_id)
    {
        $zongchengzhangdu = 6000;
        $chengzhangdu = value::get_pet_value($pet_id, 'baoshidu');
        $r_chengzhangdu = (int)($chengzhangdu / $zongchengzhangdu * 100);
        if ($r_chengzhangdu > 100) {
            $r_chengzhangdu = 100;
        }
        return $r_chengzhangdu;
    }

    //玩家获取宠物
    static function user_get_pet($user_id, $pet_id, $master_mode = 1, $xianshi = true)
    {
        $cwfd = true;
        if ($master_mode == 1) {
            $u_pet_count = user::get_pet_count($user_id, 1);
            if ($u_pet_count < 5) {
                $cwfd = false;
                value::set_pet_value($pet_id, 'master_id', $user_id);
                value::set_pet_value($pet_id, 'map_id', '0');
                value::set_pet_value($pet_id, 'master_mode', $master_mode);
                value::set_pet_value($pet_id, 'master_num', $u_pet_count + 1);
            }
        } else {
            $cwfd = false;
            value::set_pet_value($pet_id, 'master_id', $user_id);
            value::set_pet_value($pet_id, 'map_id', '0');
            value::set_pet_value($pet_id, 'master_mode', $master_mode);
        }
        //宠物数量封顶
        if ($cwfd) {
            if ($xianshi) {
                echo '你无法携带5只以上宠物。';
                br();
            }
            return false;
        } else {
            return true;
        }
    }

    static function user_get_pet1($user_id, $pet_id, $master_mode = 1, $pet_name)
    {
        $u_pet_count = user::get_pet_count1($user_id, 1, $pet_id);
        $u_hy_lvl = value::get_user_value('hy_lvl') + 1;
        if ($u_pet_count < $u_hy_lvl) {
            value::set_pet_value($pet_id, 'master_id', $user_id);
            value::set_pet_value($pet_id, 'map_id', '0');
            value::set_pet_value($pet_id, 'master_mode', $master_mode);
            value::set_pet_value($pet_id, 'master_num', $u_pet_count + 1);
            echo $pet_name;
            br();
        } else {
            //宠物数量封顶
            $id = value::get_pet_value($pet_id, 'id');
            $name = value::get_pet_value($pet_id, 'name');
            pet::del_pet($id);
            echo '你无法召唤 ' . $name . ' 宠物,会员可以额外多召唤宝宝！';
            br();
        }
    }

    //玩家失去宠物
    static function user_lose_pet($user_id, $pet_id, $mode = 0, $xianshi = true)
    {
        if (!user::have_pet($user_id, $pet_id)) {
            if ($xianshi) {
                echo "该宠物已经不属于你了。";
                br();
            }
            return false;
        }
        //身上宠物序号
        $num = 0;
        //身上宠物数量
        $sql = "SELECT COUNT(*) FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=1";
        $result = sql($sql);
        list($pet_count) = $result->fetch_row();
        if ($pet_count == 1) {
            if ($xianshi) {
                echo '身上至少要保留1只宠物。';
                br();
            }
            return false;
        }
        $i = 0;
        $pet_arr = user::get_pet_arr($user_id, 1);
        foreach ($pet_arr as $list_pet_id) {
            $i++;
            if ($list_pet_id == $pet_id) {
                $num = $i;
                break;
            }
        }
        if ($num <= $pet_count) {
            for ($j = $num; $j < $pet_count; $j++) {
                $new_pet_id = user::get_pet_arr($user_id, 1, $j + 1);
                value::set_pet_value($new_pet_id, 'master_num', $j);
            }
            //mode 0删除 1身上 2妖观 3藏将 4将蛋 5孵化 6育将 7合成 8黑市
            if (!$mode) {
                //设置宠物主人ID
                value::set_pet_value($pet_id, 'master_id', 0);
            } else {
                value::set_pet_value($pet_id, 'master_mode', $mode);
            }
            return true;
        } else {
            if ($xianshi) {
                echo '你身上没有这只宠物了。';
                br();
            }
            return false;
        }
    }

    static function del_pet($pet_id)
    {
        if ($pet_id) {
            //道具掉落
            $map_id = value::get_pet_value($pet_id, 'map_id');
            $enemy_user = value::get_pet_value($pet_id, 'enemy_user');
            $team_id = value::get_pet_value($pet_id, 'team_id');
            if ($map_id) {
                $equip_id = self::get_prop_id($pet_id, 2);
                if ($equip_id) {
                    prop::pet_lose_prop($equip_id, $pet_id, false);
                    prop::map_get_prop($map_id, $equip_id, $enemy_user, $team_id);
                }
                $equip_id = self::get_prop_id($pet_id, 3);
                if ($equip_id) {
                    prop::pet_lose_prop($equip_id, $pet_id, false);
                    prop::map_get_prop($map_id, $equip_id, $enemy_user, $team_id);
                }
            } else {
                //删除道具
                sql("DELETE FROM `game_prop` WHERE `pet_id`={$pet_id}");
            }
            //删除宠物属性
            $obj = new game_pet_object($pet_id);
            $obj->adel();
            //删除宠物
            sql("DELETE FROM `game_pet` WHERE `id`={$pet_id} LIMIT 1");
        }
    }

    static function get_exp($pet_id, $exp, $is_pk = false)
    {
        $user_id = value::get_pet_value($pet_id, 'master_id');
        //获取宠物原数据
        $sql = "SELECT `star`,`pet_id`,`name`,`sex`,`lvl`,`tf`,`czl`,`max_lvl`,`max_exp`,`chujing`,`max_hp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`xingge`,`zhongcheng`,`shuxingdian`,`zhuansheng` FROM `game_pet` WHERE `id`={$pet_id} LIMIT 1";
        $result = sql($sql);
        list($zz_star,$zhongzu_id, $name, $sex, $old_lvl, $old_tf, $czl, $max_lvl, $max_exp, $chujing, $old_hp, $old_pugong, $old_pufang, $old_tegong, $old_tefang, $old_minjie, $xingge, $zhongcheng, $shuxingdian, $zhuansheng) = $result->fetch_row();
        if ($old_lvl < $max_lvl) {
            if (!value::get_pet_value2('is_lock_lvl', $pet_id)) {
                //获取宠物种族数据
                $sql = "SELECT `star`,`hp`,`tf`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`jh_id`,`jh_lvl`,`study_lvl` FROM `pet` WHERE `id`={$zhongzu_id} LIMIT 1";
                $result = sql($sql);
                list($star, $zz_hp, $zz_tf, $zz_pugong, $zz_pufang, $zz_tegong, $zz_tefang, $zz_minjie, $jh_id, $jh_lvl, $study_lvl) = $result->fetch_row();
                //增加宠物经验
                $now_exp = value::add_pet_value($pet_id, 2, $exp, false);
                if ($is_pk) {
                    skill::add_skill_chat("{$name}获得了{$exp}点经验", $user_id, 0);
                } else {
                    echo "{$name}获得了{$exp}点经验";
                    br();
                }
                //获取宠物等级 六维
                $now_lvl = $old_lvl;
                $now_hp = $old_hp;
                $now_max_hp = 0;
                $now_pugong = $old_pugong;
                $now_pufang = $old_pufang;
                $now_tegong = $old_tegong;
                $now_tefang = $old_tefang;
                $now_minjie = $old_minjie;
                $zz_hp = $zz_hp / 10;
                $add_shuxingdian = 0;
                //宠物升级
                if ($now_exp >= $max_exp) {
                    if ($now_lvl < $max_lvl) {
                        //增加玩家练宠名声
                        value::add_user_value('lianyaoms', $zz_star, $user_id);
                        //提升等级
                        $now_lvl++;
                        //提升max_exp
                        if ($now_lvl < $max_lvl) {
                            $max_exp = $chujing * ($now_lvl + 1) * (1 + $now_lvl) * (11 + $now_lvl) + 200;
                        }
                        //增加属性点
                        if ($czl == 100) {
                            $czl_mz = 1.5;
                        } else if ($czl >= 90) {
                            $czl_mz = 1.3;
                        } else if ($czl >= 75) {
                            $czl_mz = 1.1;
                        } else {
                            $czl_mz = 1;
                        }
                        $add_shuxingdian++;
                        $zz_hp = mt_rand(0, 10) * $zz_star * $czl_mz;
                        if ($old_tf == 1) {
                            $zz_hp = 2 * $zz_hp;
                        }
                        $now_hp += $zz_hp;
                        //增加六维
                        $zz_pugong = mt_rand(0, $zz_pugong) / 5 * $zz_star * $czl_mz;
                        if ($old_tf == 2) {
                            $zz_pugong = 2 * $zz_pugong;
                        }
                        $zz_pufang = mt_rand(0, $zz_pufang) / 5 * $zz_star * $czl_mz;
                        if ($old_tf == 3) {
                            $zz_pufang = 2 * $zz_pufang;
                        }
                        $zz_tegong = mt_rand(0, $zz_tegong) / 5 * $zz_star * $czl_mz;
                        if ($old_tf == 4) {
                            $zz_tegong = 2 * $zz_tegong;
                        }
                        $zz_tefang = mt_rand(0, $zz_tefang) / 5 * $zz_star * $czl_mz;
                        if ($old_tf == 5) {
                            $zz_tefang = 2 * $zz_tefang;
                        }
                        $zz_minjie = mt_rand(0, $zz_minjie) * $zz_star * $czl_mz;
                        if ($old_tf == 6) {
                            $zz_minjie = 2 * $zz_minjie;
                        }
                        $now_pugong += $zz_pugong;
                        $now_pufang += $zz_pufang;
                        $now_tegong += $zz_tegong;
                        $now_tefang += $zz_tefang;
                        $now_minjie += $zz_minjie;
                        //HP 忠诚度加成
                        $zcdjc = (int)($now_lvl * $zhongcheng / 200);
                        $now_max_hp = $now_hp + $zcdjc;
                        value::set_pet_value($pet_id, 'exp', 1);
                    } else {
                        $now_exp = $max_exp;
                        value::set_pet_value($pet_id, 'exp', $max_exp);
                    }
                }
                //输出经验提示
                if ($is_pk) {
                    skill::add_skill_chat('你的宠物下次升级还要' . (int)($max_exp - $now_exp) . "经验", $user_id, 0);
                } else {
                    echo '你的宠物下次升级还要' . (int)($max_exp - $now_exp) . "经验";
                    br();
                }
                //宠物是否升级了
                if ($now_lvl > $old_lvl) {
                    //更新宠物数据
                    $sql = "UPDATE `game_pet` SET `lvl`={$now_lvl},`max_exp`={$max_exp},`hp`={$now_max_hp},`max_hp`={$now_hp},`pugong`={$now_pugong},`pufang`={$now_pufang},`tegong`={$now_tegong},`tefang`={$now_tefang},`minjie`={$now_minjie},`shuxingdian`=`shuxingdian`+{$add_shuxingdian} WHERE `id`={$pet_id} LIMIT 1";
                    sql($sql);
                    $new_maxhp = self::get_max_hp($pet_id);
                    value::set_pet_value($pet_id, 'hp', $new_maxhp);
                    //显示处理
                    $old_hp = (int)$old_hp;
                    $old_pugong = (int)($old_pugong < 1 ? 0 : $old_pugong / 1);
                    $old_pufang = (int)($old_pufang < 1 ? 0 : $old_pufang / 1);
                    $old_tegong = (int)($old_tegong < 1 ? 0 : $old_tegong / 1);
                    $old_tefang = (int)($old_tefang < 1 ? 0 : $old_tefang / 1);
                    $old_minjie = (int)($old_minjie < 1 ? 0 : $old_minjie / 1);
                    $now_hp = (int)$now_hp;
                    $now_pugong = (int)$now_pugong;
                    $now_pufang = (int)$now_pufang;
                    $now_tegong = (int)$now_tegong;
                    $now_tefang = (int)$now_tefang;
                    $now_minjie = (int)$now_minjie;
                    //显示属性数据
                    if ($is_pk) {
                        skill::add_skill_chat("你的{$name}升级了!<br>等级:{$old_lvl} → {$now_lvl}<br>生命:{$old_hp} → {$now_hp}<br>攻击:{$old_pugong} → {$now_pugong}<br>防御:{$old_pufang} → {$now_pufang}<br>魔攻:{$old_tegong} → {$now_tegong}<br>魔防:{$old_tefang} → {$now_tefang}<br>灵活:{$old_minjie} → {$now_minjie}", $user_id, 0);
                    } else {
                        echo <<<uplvl
你的{$name}升级了!<br>
等级:{$old_lvl} → {$now_lvl}<br>
生命:{$old_hp} → {$now_hp}<br>
攻击:{$old_pugong} → {$now_pugong}<br>
防御:{$old_pufang} → {$now_pufang}<br>
魔攻:{$old_tegong} → {$now_tegong}<br>
魔防:{$old_tefang} → {$now_tefang}<br>
灵活:{$old_minjie} → {$now_minjie}<br>
uplvl;
                    }
                }
                if ($now_lvl != $old_lvl) {
                    //显示进化
                    if ($now_lvl == $jh_lvl && !$zhuansheng && $max_lvl > 100) {
                        $fuhao = ($sex == '雌' ? "" : "");
                        $chat = "<span style='color:#ff9900'>你的【{$name}】可以进化为【" . value::getvalue('pet', 'name', 'id', $jh_id) . "{$fuhao}】。</span>";
                        if ($is_pk) {
                            skill::add_skill_chat($chat, $user_id, 0);
                        } else {
                            echo $chat, "";
                            br();
                        }
                    }
                    //显示可学习技能 自动学习技能
                    $max_skill_count = $zhuansheng > 1 ? 5 : 4;
                    $can_study_fashu = (int)($now_lvl / $study_lvl) + 1;
                    $old_can_study_fashu = $can_study_fashu;
                    $can_study_fashu = $can_study_fashu > 10 ? 10 : $can_study_fashu;
                    $can_study_count = $can_study_fashu > $max_skill_count ? $max_skill_count : $can_study_fashu;
                    //读取种族技能
                    $p_study_skill_str = value::getvalue('pet', 'study_skill', 'id', $zhongzu_id);
                    $p_study_skill_arr = explode(',', $p_study_skill_str);
                    //自动学习
                    $old_pet_skill_count = skill::get_pet_skill_count($pet_id);
                    if ($old_pet_skill_count < $max_skill_count) {
                        for ($i = 0; $i < $can_study_count; $i++) {
                            //技能是否存在
                            if ($p_study_skill_arr[$i]) {
                                if ($old_pet_skill_count < $max_skill_count) {
                                    if (!skill::pet_have_skill($pet_id, $p_study_skill_arr[$i])) {
                                        $chat = "<span style='color:#0000ff'>你的【" . $name . "】学会了技能【" . value::getvalue('skill', 'name', 'id', $p_study_skill_arr[$i]) . "】。</span>";
                                        if ($is_pk) {
                                            skill::add_skill_chat($chat, $user_id, 0);
                                        } else {
                                            echo $chat, "";
                                            br();
                                        }
                                        skill::study_skill($pet_id, $p_study_skill_arr[$i], -1, false);
                                        $old_pet_skill_count++;
                                    }
                                } else {
                                    if (!skill::pet_have_skill($pet_id, $p_study_skill_arr[$i]) && $old_can_study_fashu < 11) {
                                        $chat = "<span style='color:#0000ff'>你的【" . $name . "】可以学习技能【" . value::getvalue('skill', 'name', 'id', $p_study_skill_arr[$i]) . "】。</span>";
                                        if ($is_pk) {
                                            skill::add_skill_chat($chat, $user_id, 0);
                                        } else {
                                            echo $chat, "";
                                            br();
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        //技能是否存在
                        if ($p_study_skill_arr[$can_study_fashu - 1]) {
                            if (!skill::pet_have_skill($pet_id, $p_study_skill_arr[$can_study_fashu - 1]) && $old_can_study_fashu < 11) {
                                $chat = "<span style='color:#0000ff'>你的【" . $name . "】可以学习技能【" . value::getvalue('skill', 'name', 'id', $p_study_skill_arr[$can_study_fashu - 1]) . "】。</span>";
                                if ($is_pk) {
                                    skill::add_skill_chat($chat, $user_id, 0);
                                } else {
                                    echo $chat, "";
                                    br();
                                }
                            }
                        }
                    }
                    //回复技能pp
                    $i = 0;
                    while ($pp_skill_id = value::get_pet_value2('skill.' . $i . '.id', $pet_id, false)) {
                        $max_pp = value::getvalue('skill', 'pp', 'id', $pp_skill_id);
                        $max_pp *= $zhuansheng > 0 ? 1.5 : 1;
                        $max_pp = (int)$max_pp;
                        value::set_pet_value2('skill.' . $pp_skill_id . '.pp', $max_pp, $pet_id);
                        $i++;
                    }
                }
            } else {
                if ($is_pk) {
                    skill::add_skill_chat('你的' . $name . '已被锁定等级。', $user_id, 0);
                } else {
                    echo '你的' . $name . '已被锁定等级。';
                    br();
                }
            }
        } else {
            if ($is_pk) {
                skill::add_skill_chat('你的' . $name . '已经满级了。', $user_id, 0);
            } else {
                echo '你的' . $name . '已经满级了。';
                br();
            }
        }
    }

    static function get_xingge_xz($xingge, $mode)
    {
        $pugong = 1;
        $pufang = 1;
        $tegong = 1;
        $tefang = 1;
        $minjie = 1;
        //修正宠物种族数据
        if ($xingge < 5) {
            //孤独 固执 纯洁 无畏 +攻击
            $pugong *= 1.1;
        } else if ($xingge < 9) {
            //凶猛 潇洒 敏捷 悠闲 +防御
            $pufang *= 1.1;
        } else if ($xingge < 13) {
            //保守 坚强 马虎 冷静 +魔攻
            $tegong *= 1.1;
        } else if ($xingge < 17) {
            //深沉 平庸 精明 狂妄 +魔防
            $tefang *= 1.1;
        } else if ($xingge < 21) {
            //迟缓 急躁 稳健 天真 +灵活
            $minjie *= 1.1;
        }
        if ($xingge == 5 || $xingge == 9 || $xingge == 13 || $xingge == 17) {
            //凶猛 保守 深沉 迟缓 -攻击
            $pugong *= 0.9;
        }
        if ($xingge == 1 || $xingge == 10 || $xingge == 14 || $xingge == 18) {
            //孤独 坚强 平庸 急躁 -防御
            $pufang *= 0.9;
        }
        if ($xingge == 2 || $xingge == 6 || $xingge == 15 || $xingge == 19) {
            //固执 潇洒 精明 稳健 -魔攻
            $tegong *= 0.9;
        }
        if ($xingge == 3 || $xingge == 7 || $xingge == 11 || $xingge == 20) {
            //纯洁 敏捷 马虎 天真 -魔防
            $tefang *= 0.9;
        }
        if ($xingge == 4 || $xingge == 8 || $xingge == 12 || $xingge == 16) {
            //无畏 悠闲 冷静 狂妄 -灵活
            $minjie *= 0.9;
        }
        if ($mode == 1) {
            return $pugong;
        }
        if ($mode == 2) {
            return $pufang;
        }
        if ($mode == 3) {
            return $tegong;
        }
        if ($mode == 4) {
            return $tefang;
        }
        if ($mode == 5) {
            return $minjie;
        }
    }

    //获取最大HP(基本 忠诚度加成 装备加成 法宝加成)
    static function get_max_hp($pet_id)
    {
        $max_hp = value::get_pet_value($pet_id, 'max_hp');
        $max_hp += self::get_liuwei_jiacheng($pet_id, 'hp');
        return (int)$max_hp;
    }

    //获取效果装备ID 2六维 3效果
    static function get_prop_id($pet_id, $leixing = 0)
    {
        $equip_p_id = array();
        $sql = "SELECT `id` FROM `game_prop` WHERE `pet_id`={$pet_id} LIMIT 0,7";
        $result = sql($sql);
        while (list($equip_id) = $result->fetch_row()) {
            if (value::get_game_prop_value($equip_id, 'leixing') == $leixing) {
                if ($leixing == 2) {
                    $equip_p_id[value::get_game_prop_value($equip_id, 'sub_leixing')] = $equip_id;
                } else {
                    $equip_p_id = $equip_id;
                    break;
                }
            }
        }
        ksort($equip_p_id);
        if ($equip_p_id) {
            if ($leixing == 2) {
                return array_values($equip_p_id);
            } else {
                return $equip_p_id;
            }
        } else {
            return 0;
        }
    }

    static function get_prop_id1($user_id, $leixing = 0)
    {
        $equip_p_id = array();
        $sql = "SELECT `id` FROM `game_prop` WHERE `user_id`={$user_id} LIMIT 0,13";
        $result = sql($sql);
        while (list($equip_id) = $result->fetch_row()) {
            if (value::get_game_prop_value($equip_id, 'leixing') == $leixing) {
                if ($leixing == 2) {
                    $equip_p_id[value::get_game_prop_value($equip_id, 'sub_leixing')] = $equip_id;
                } else {
                    $equip_p_id = $equip_id;
                    break;
                }
            }
        }
        ksort($equip_p_id);
        if ($equip_p_id) {
            if ($leixing == 2) {
                return array_values($equip_p_id);
            } else {
                return $equip_p_id;
            }
        } else {
            return 0;
        }
    }
    //获取装备加成
    static function get_prop_jiacheng($pet_id, $valuename = "")
    {
        $value = 0;
        $equip_liuwei_id = self::get_prop_id($pet_id, 2);
        foreach ($equip_liuwei_id as $equip_id) {
            $value += value::get_game_prop_value($equip_id, $valuename);
        }
        return $value;
    }

    //装备磨损
    static function prop_mosun($pet_id, $num = -1, $mode = 0, $is_win = true)
    {
           //获取用户信息
           if (!$user_id) {
            $user_id = uid();
        }
        $in_map_id = value::get_game_user_value('in_map_id', $user_id);
        $o_user_id = value::get_game_user_value('pk.user.id', $user_id);
        //获取名字
        $name = value::get_game_user_value('name', $user_id);
        //装备磨损耐久度
        $equip_liuwei_id = self::get_prop_id1($user_id, 2);
        foreach ($equip_liuwei_id as $equip_id) {
            $equip_name = value::get_game_prop_value($equip_id, 'name');
            $max_naijiu = (int)(value::get_game_prop_value($equip_id, 'max_naijiu') / 10);
            $equip_naijiu = value::get_game_prop_value($equip_id, 'naijiu');
            $qianghuacishu = value::get_game_prop_value($equip_id, 'fm');
            $sj = mt_rand(0, 100);
            if ($sj < 5 && $qianghuacishu < 1) {
                $user_num = value::get_game_prop_value($equip_id, 'user_num');
                if ($user_num == 5) {
                    $equip_naijiu = value::add_game_prop_value($equip_id, 'naijiu', $num);
                }
            }
            if (!$equip_naijiu) {
                $mosun_str = "{$name}的{$equip_name}磨损坏掉了。";
                skill::add_skill_chat($mosun_str, $user_id, 0, true);
                equip::user_lose_equip1($equip_id);
                prop::del_prop($equip_id);
            } else {
                if ($equip_naijiu <= $max_naijiu) {
                    $mosun_str = "{$name}的{$equip_name}严重磨损了。";
                    skill::add_skill_chat($mosun_str, $user_id, 0, true);
                }
            }
        }
    }
    //装备磨损
    static function prop_mosun1($user_id, $num = -1, $mode = 0, $is_win = true)
    {
        //获取用户信息
        if (!$user_id) {
            $user_id = uid();
        }
        $in_map_id = value::get_game_user_value('in_map_id', $user_id);
        $o_user_id = value::get_game_user_value('pk.user.id', $user_id);
        //获取名字
        $name = value::get_game_user_value('name', $user_id);
        //装备磨损耐久度
        $equip_liuwei_id = self::get_prop_id1($user_id, 2);
        foreach ($equip_liuwei_id as $equip_id) {
            $equip_name = value::get_game_prop_value($equip_id, 'name');
            $max_naijiu = (int)(value::get_game_prop_value($equip_id, 'max_naijiu') / 10);
            $equip_naijiu = value::get_game_prop_value($equip_id, 'naijiu');
            $qianghuacishu = value::get_game_prop_value($equip_id, 'fm');
            $sj = mt_rand(0, 100);
            if ($sj < 5 && $qianghuacishu < 1) {
                $user_num = value::get_game_prop_value($equip_id, 'user_num');
                if ($user_num == 5) {
                    $equip_naijiu = value::add_game_prop_value($equip_id, 'naijiu', $num);
                }
            }
            if (!$equip_naijiu) {
                $mosun_str = "{$name}的{$equip_name}磨损坏掉了。";
                skill::add_skill_chat($mosun_str, $user_id, 0, true);
                equip::user_lose_equip1($equip_id);
                prop::del_prop($equip_id);
            } else {
                if ($equip_naijiu <= $max_naijiu) {
                    $mosun_str = "{$name}的{$equip_name}严重磨损了。";
                    skill::add_skill_chat($mosun_str, $user_id, 0, true);
                }
            }
        }
    }

    //装备磨损
    static function prop_mosun2($user_id, $num = -1, $mode = 0, $is_win = true)
    {
        //获取用户信息
        if (!$user_id) {
            $user_id = uid();
        }
        //装备磨损耐久度
        $equip_liuwei_id = self::get_prop_id1($user_id, 2);
        foreach ($equip_liuwei_id as $equip_id) {
            $sub_leixing = value::get_game_prop_value($equip_id, 'sub_leixing');
            if ($sub_leixing == 10 || $sub_leixing == 14) {    
                $user_num = value::get_game_prop_value($equip_id, 'user_num');
                if ($user_num == 5) {
                    $equip_naijiu = value::add_game_prop_value($equip_id, 'naijiu', $num);
                }       
                if ($equip_naijiu < 1) {   
                    prop::del_prop($equip_id);
                }
            }
        }
    }

    //装备磨损
    static function prop_mosun3($user_id, $num = -1, $mode = 0, $is_win = true)
    {
        //获取用户信息
        if (!$user_id) {
            $user_id = uid();
        }
        //装备磨损耐久度
        $equip_liuwei_id = self::get_prop_id1($user_id, 2);
        foreach ($equip_liuwei_id as $equip_id) {
            $sub_leixing = value::get_game_prop_value($equip_id, 'sub_leixing');
            if ($sub_leixing == 2) {    
                $user_num = value::get_game_prop_value($equip_id, 'user_num');
                if ($user_num == 5) {
                    $equip_naijiu = value::add_game_prop_value($equip_id, 'naijiu', $num);
                    if ($equip_naijiu < 1) {   
                        prop::del_prop($equip_id);
                    }
                }        
            }
        }
    }

    //获取六维加成
    static function get_liuwei_jiacheng($pet_id, $valuename = "")
    {
        $value = 0;
        $lvl = value::get_pet_value($pet_id, 'lvl');
        $xingge = value::get_pet_value($pet_id, 'xingge');
        $zhongcheng = value::get_pet_value($pet_id, 'zhongcheng');
        //忠诚度加成
        $zcdjc = $lvl * $zhongcheng / 200;
        switch ($valuename) {
            case "pugong":
                $value += $zcdjc * self::get_xingge_xz($xingge, 1);
                break;
            case "pufang":
                $value += $zcdjc * self::get_xingge_xz($xingge, 2);
                break;
            case "tegong":
                $value += $zcdjc * self::get_xingge_xz($xingge, 3);
                break;
            case "tefang":
                $value += $zcdjc * self::get_xingge_xz($xingge, 4);
                break;
            case "minjie":
                $value += $zcdjc * self::get_xingge_xz($xingge, 5);
                break;
            case "hp":
                $value += $zcdjc;
                break;
        }
        //装备加成
        $value += self::get_prop_jiacheng($pet_id, $valuename);
        //小于1取整
        if ($value > 0 && $value < 1) {
            $value = 1;
        }
        //返回加成
        return (int)$value;
    }

    //宠物是否稀有
    static function is_xiyou($pet_id)
    {
        //是否稀有宠物
        $xiyou = self::get_zz($pet_id, 'xiyou');
        if ($xiyou > 4) {
            return true;
        }
        //是否转生
        $zhuansheng = value::get_pet_value($pet_id, 'zhuansheng');
        if ($zhuansheng) {
            return true;
        }
        //是否高级别宠物
        $lvl = value::get_pet_value($pet_id, 'lvl');
        $max_lvl = value::get_pet_value($pet_id, 'max_lvl');
        if ($lvl > 4 && $max_lvl > 100) {
            return true;
        }
        //默认返回非稀有
        return false;
    }

    //宠物是否有此天赋
    static function zz_have_texing($pet_zz_id, $texing)
    {
        $texing_ok = false;
        $shuxing = value::getvalue('pet', 'shuxing', 'id', $pet_zz_id);
        if ($texing < 21) {
            $texing_ok = true;
        } else if ($texing == 21 && strstr($shuxing, '金')) {
            $texing_ok = true;
        } else if ($texing > 21 && $texing < 24 && strstr($shuxing, '木')) {
            $texing_ok = true;
        } else if ($texing > 23 && $texing < 27 && strstr($shuxing, '水')) {
            $texing_ok = true;
        } else if ($texing > 26 && $texing < 31 && strstr($shuxing, '火')) {
            $texing_ok = true;
        } else if ($texing > 30 && $texing < 33 && strstr($shuxing, '土')) {
            $texing_ok = true;
        } else if ($texing > 32 && $texing < 35 && strstr($shuxing, '风')) {
            $texing_ok = true;
        } else if ($texing > 34 && $texing < 37 && strstr($shuxing, '雷')) {
            $texing_ok = true;
        } else if ($texing > 36 && $texing < 39 && strstr($shuxing, '日')) {
            $texing_ok = true;
        } else if ($texing > 38 && $texing < 44 && strstr($shuxing, '月')) {
            $texing_ok = true;
        } else if ($texing > 43 && $texing < 45 && strstr($shuxing, '人')) {
            $texing_ok = true;
        } else if ($texing > 44 && $texing < 48 && strstr($shuxing, '仙')) {
            $texing_ok = true;
        } else if ($texing > 47 && $texing < 51 && strstr($shuxing, '妖')) {
            $texing_ok = true;
        } else if ($texing > 50 && $texing < 53 && strstr($shuxing, '动')) {
            $texing_ok = true;
        } else if ($texing > 52 && $texing < 54 && strstr($shuxing, '木')) {
            $texing_ok = true;
        }
        return $texing_ok;
    }

    //获取合成宠物
    static function get_hechonggongshi()
    {
        //孟获+祝融=孟获 诸葛亮+黄月英=诸葛亮
        $hccw_arr = config::getConfigByName("hechonggongshi");
        return $hccw_arr;
    }

    //获取合成宠物ID
    static function get_hechengchongwu($pet1_zz_id, $pet2_zz_id)
    {
        $hccw_arr = self::get_hechonggongshi();
        $new_pet_id = $hccw_arr["$pet1_zz_id+$pet2_zz_id"] ? $hccw_arr["$pet1_zz_id+$pet2_zz_id"] : $hccw_arr["$pet2_zz_id+$pet1_zz_id"];
        if ($new_pet_id && !value::getvalue('pet', 'is_hecheng_open', 'id', $new_pet_id) && !value::get_user_value('game_master')) {
            $new_pet_id = 0;
        }
        return $new_pet_id;
    }

    //解析合成公式
    static function check_hechonggongshi()
    {
        echo "合成公式:";
        $hc_arr = self::get_hechonggongshi();
        foreach ($hc_arr as $hc_key => $value) {
            echo "";
            br();
            $tmp_arr = explode("+", $hc_key);
            echo value::getvalue('pet', 'name', 'id', $value), "=", value::getvalue('pet', 'name', 'id', $tmp_arr[0]), "+", value::getvalue('pet', 'name', 'id', $tmp_arr[1]);
        }
        br();
        cmd::add_last_cmd("design_game");
    }

    //获取属性编号
    static function get_shuxing_int($shuxing)
    {
        $shuxing_int = 0;
        switch ($shuxing) {
            case "金":
                $shuxing_int = 1;
                break;
            case "木":
                $shuxing_int = 2;
                break;
            case "水":
                $shuxing_int = 3;
                break;
            case "火":
                $shuxing_int = 4;
                break;
            case "土":
                $shuxing_int = 5;
                break;
            case "无":
                $shuxing_int = 6;
                break;
        }
        return $shuxing_int;
    }

    //获取属性编号
    static function get_shuxing_str($shuxing)
    {
        $shuxing_str = 0;
        switch ($shuxing) {
            case 1:
                $shuxing_str = "金";
                break;
            case 2:
                $shuxing_str = "木";
                break;
            case 3:
                $shuxing_str = "水";
                break;
            case 4:
                $shuxing_str = "火";
                break;
            case 5:
                $shuxing_str = "土";
                break;
            case 6:
                $shuxing_str = "人";
                break;
            case 7:
                $shuxing_str = "仙";
                break;
            case 8:
                $shuxing_str = "妖";
                break;
            case 9:
                $shuxing_str = "风";
                break;
            case 10:
                $shuxing_str = "雷";
                break;
            case 11:
                $shuxing_str = "日";
                break;
            case 12:
                $shuxing_str = "月";
                break;
            case 13:
                $shuxing_str = "动";
                break;
            case 14:
                $shuxing_str = "无";
                break;
        }
        return $shuxing_str;
    }

    //输出宠物图片
    static function img($img_name, $is_echo = true, $is_br = true)
    {
        $img_str = "<img src='res/img/body/{$img_name}' style='width: 135px;height:105px' onerror=\"javascript:this.src='logo.png';\">" . ($is_br ? "<br>" : "");
        if ($is_echo) {
            echo $img_str;
        }
        return $img_str;
    }

    static function img1($img_name, $is_echo = true, $is_br = true)
    {
        $img_str = "<img src='res/img/body/{$img_name}' style='width: 20px;height:20px' onerror=\"javascript:this.src='logo.png';\">" . ($is_br ? "<br>" : "");
        if ($is_echo) {
            echo $img_str;
        }
        return $img_str;
    }

    //是否野生宠物
    static function is_yesheng($pet_id)
    {
        if (self::get($pet_id, "max_lvl") < 120 || (self::get($pet_id, "max_lvl") > 120 && !self::get($pet_id, "zhuansheng"))) {
            return true;
        } else {
            return false;
        }
    }
}


//玩家类
Class user
{
    //获取game_user_value属性
    static function get($valuename, $user_id = 0)
    {
        return value::get_user_value($valuename, $user_id);
    }

    //设置game_user_value属性
    static function set($valuename, $value, $user_id = 0)
    {
        value::set_user_value($valuename, $value, $user_id);
    }

    //获取玩家game_user属性
    static function get_game_user($valuename, $user_id = 0)
    {
        return value::get_game_user_value($valuename, $user_id);
    }

    //设置game_user属性
    static function set_game_user($valuename, $value, $user_id = 0)
    {
        value::set_game_user_value($valuename, $value, $user_id);
    }

    //加减game_user_属性
    static function add_game_user($valuename, $value, $user_id = 0)
    {
        return value::add_game_user_value($valuename, $value, $user_id);
    }

    //获取玩家身上宠物最高等级
    static function get_max_pet_lvl($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $lvl_arr = array();
        $pet_arr = self::get_pet_arr($user_id, 1);
        foreach ($pet_arr as $pet_id) {
            $pet_lvl = value::get_pet_value($pet_id, 'lvl');
            array_push($lvl_arr, $pet_lvl);
        }
        rsort($lvl_arr);
        return $lvl_arr[0];
    }

    static function user_have_prop($prop_id, $user_id)
    {
        $prop_id1 = value::get_game_prop_value($prop_id, 'id');
        if ($user_id == value::get_game_prop_value($prop_id1, '$user_id')) {
            return true;
        } else {
            return false;
        }
    }
     static function user_lose_prop($prop_id, $user_id, $xianshi = true)
     {
         $name = value::get_game_user_value('name', $user_id);
         $prop_name = value::get_game_prop_value($prop_id, 'name');
         if (self::user_have_prop($prop_id, $user_id)) {
             value::set_game_prop_value($prop_id, 'user_id', '0');
             if ($xianshi) {
                 echo "{$name}卸下了{$prop_name}。";
                 br();
             }
         } else {
             echo "{$name}身上没有这个道具了。";
             br();
         }
     }

    //获取已用容量 0背包 1装备仓库 2宠物仓库 3宠物袋 4育将袋
    static function get_fuzhong($mode = 0, $userid = 0)
    {
        if (!$userid) {
            $userid = uid();
        }
        $fuzhong = 0;
        if (!$mode) {
            $item_count = 0;
            $sql = "SELECT `valuename` FROM `game_user_value` WHERE `valuename` LIKE '{$userid}.i.%' AND `value`!='0'";
            $result = sql($sql);
            while (list($item_id) = $result->fetch_row()) {
                $id = explode('.', $item_id);
                $id = $id[2];
                $item_count1 =item::get_item($id);
                $item_lingshi = value::get_item_value($id, 'lingshi');
                $item_is_bd = value::get_item_value($id, 'is_bd');
                if ($item_count1 > 1 && $id != 1 && $item_lingshi < 1) {
                    $item_count += item::get_item($id);
                } else {
                    $item_count += value::get_item_value($id, 'fuzhong');
                }
            }
            $fuzhong += $item_count;
            $prop_count = 0;
            $sql = "SELECT `prop_id` FROM `game_prop` WHERE `user_id`={$userid} AND `user_num`=1";
            $result = sql($sql);
            while (list($prop_id) = $result->fetch_row()) {
                $prop_count += value::get_prop_value($prop_id, 'fuzhong');
            }
            $fuzhong += $prop_count;
        } else if ($mode == 1) {
            $prop_count = 0;
            $sql = "SELECT `prop_id` FROM `game_prop` WHERE `user_id`={$userid} AND `user_num`=2";
            $result = sql($sql);
            while (list($prop_id) = $result->fetch_row()) {
                $prop_count += value::get_prop_value($prop_id, 'fuzhong');
            }
            $fuzhong += $prop_count;
        } else if ($mode == 2) {
            $sql = "SELECT COUNT(*) FROM `game_pet` WHERE `master_id`={$userid} AND `master_mode`=2";
            $result = sql($sql);
            list($pet_count) = $result->fetch_row();
            $fuzhong += $pet_count;
        } else if ($mode == 3) {
            $sql = "SELECT COUNT(*) FROM `game_pet` WHERE `master_id`={$userid} AND `master_mode`=3";
            $result = sql($sql);
            list($pet_count) = $result->fetch_row();
            $fuzhong += $pet_count;
        } elseif ($mode == 4) {
            $sql = "SELECT COUNT(*) FROM `game_pet` WHERE `master_id`={$userid} AND `master_mode`=5";
            $result = sql($sql);
            list($pet_count) = $result->fetch_row();
            $fuzhong += $pet_count;
        }
        return $fuzhong;
    }

    //获取总容量 0背包 1装备仓库 2宠物仓库 3宠物袋 4育将袋
    static function get_rongliang($mode = 0, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $rongliang = 0;
        if (!$mode) {
            $ts_jz6 = equip::get_user_equip_jc('ts_jz6');
            $hy_xs_lvl = value::get_user_value('hy_lvl', $user_id) * 100;
            $rongliang = 5000 + $ts_jz6 + $hy_xs_lvl;
            $rongliang += value::get_user_value('qiankundai.bb.rongliang', $user_id);
        } else if ($mode == 1) {
            $rongliang = 50;
            $rongliang += value::get_user_value('qiankundai.zbck.rongliang', $user_id);
        } else if ($mode == 2) {
            $rongliang = 30;
            $rongliang += value::get_user_value('qiankundai.ygck.rongliang', $user_id);
        } else if ($mode == 3) {
            $rongliang = 30;
            $rongliang += value::get_user_value('qiankundai.cyd.rongliang', $user_id);
        } elseif ($mode == 4) {
            $rongliang = 5;
            $rongliang += value::get_user_value('qiankundai.yyd.rongliang', $user_id);
        }
        return $rongliang;
    }

    //判断宠物是否属于该玩家
    static function have_pet($user_id = 0, $pet_id = 0, $master_mode = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        if (value::get_pet_value($pet_id, 'master_id') == $user_id) {
            if (($master_mode && value::get_pet_value($pet_id, 'master_mode') == $master_mode) || !$master_mode) {
                return true;
            }
        } else {
            sql("DELETE FROM `game_user_value` WHERE `valuename` LIKE '{$user_id}.pet%.id' AND `value`='{$pet_id}'");
            return false;
        }
    }

//玩家出师
    static function chushi($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $user_name = value::get_game_user_value('name', $user_id);
        $shifu_id = value::get_game_user_value('shifu.id', $user_id);
        $shifu_name = value::get_game_user_value('name', $shifu_id);
        item::add_money(10000, $user_id);
        prop::user_get_prop(prop::new_prop(57, 0, 3), $user_id, 1, true, true);
        echo "你在{$shifu_name}门下成功出师,开始遨游传奇,探索五行!";
        $add_chongshengdian = 20;
        $add_shengwangzhi = 30;
        c_add_xiaoxi("你获得了{$add_chongshengdian}点重生点,{$add_shengwangzhi}点声望值。<br>{$user_name}成功出师,对你三叩九拜,从此天高任鸟飞!", 0, $user_id, $shifu_id);
        c_add_guangbo("惊天地,泣鬼神,{$shifu_name}的高徒{$user_name}成功出师,开始遨游传奇,探索五行!");
        value::add_user_value('chongshengdian', $add_chongshengdian, $shifu_id);
        value::add_user_value('shengwangzhi', $add_shengwangzhi, $shifu_id);
        value::set_game_user_value('shifu.id', 0);
        value::set_game_user_value('is_chushi', 1);
    }

    //获取所在帮派
    static function get_union($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return value::get_game_user_value("gonghui.id", $user_id);
    }

    //获取所在帮派头衔等级
    static function get_union_lvl($user_id)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return value::get_game_user_value('gonghui.lvl', $user_id);
    }

    //获取所在帮派头衔名称
    static function get_union_lvl_name($user_id)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $type = union::get(self::get_union($user_id), 'type');
        $union_tx_arr = union::get_touxian($type);
        return $union_tx_arr[value::get_game_user_value('gonghui.lvl', $user_id)];
    }

    //获取徒弟个数
    static function get_tudi_count($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $sql = "SELECT COUNT(*) FROM `game_user` WHERE `shifu.id`={$user_id}";
        $reslut = sql($sql);
        list($count) = $reslut->fetch_row();
        return $count;
    }

    //获取生肖章数
    static function get_zhang_count($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $count = 0;
        for ($i = 0; $i < 12; $i++) {
            if (item::get_item($i + 30, $user_id)) {
                $count++;
            }
        }
        return $count;
    }

    //获取红名
    static function get_hongming($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $hongming = value::get_user_value('hongming', $user_id);
        return (int)$hongming;
    }

    //获取亲密度
    static function get_qinmidu($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $bl_id = value::get_user_value('bl.id', $user_id);
        $u_qinmidu = value::get_user_value('bl.qinmidu', $user_id);
        $o_qinmidu = value::get_user_value('bl.qinmidu', $bl_id);
        return (int)(($u_qinmidu + $o_qinmidu) / 60 / 2);
    }

    //获取伴侣婚名
    static function get_bl_hun($user_id = 0, $mode = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $bl_qinmidu = self::get_qinmidu($user_id);
        $bl_hun = "";
        $bl_hour = (int)($bl_qinmidu / 60);
        if ($bl_hour < 50) {
            $bl_hun = '纸';
        } else if ($bl_hour >= 50 && $bl_hour < 100) {
            $bl_hun = '木';
        } else if ($bl_hour >= 100 && $bl_hour < 200) {
            $bl_hun = '陶';
        } else if ($bl_hour >= 200 && $bl_hour < 500) {
            $bl_hun = '银';
        } else if ($bl_hour >= 500 && $bl_hour < 800) {
            $bl_hun = '金';
        } else if ($bl_hour >= 800 && $bl_hour < 1000) {
            $bl_hun = '水晶';
        } else if ($bl_hour >= 1000) {
            $bl_hun = '钻石';
        }
        if (!$mode) {
            return $bl_hun;
        } else {
            return $bl_hour;
        }
    }

    //获取师门昵称
    static function get_shimen_nick_name($tongmen_id)
    {
        $user_id = uid();
        $is_chushi = value::get_game_user_value('is_chushi', $user_id);
        if ($is_chushi) {
            $shifu_id = $user_id;
        } else {
            $shifu_id = value::get_game_user_value('shifu.id', $user_id);
        }
        $o_shimen_name = "";
        if ($shifu_id == $tongmen_id) {
            $o_shimen_name = "师父";
        }
        if ($user_id == $tongmen_id) {
            $o_shimen_name = "你自己";
        }
        if (!$o_shimen_name) {
            $ocount = 0;
            $ucount = 20;
            $sql = "SELECT COUNT(*) FROM `game_user` WHERE `shifu.id`={$shifu_id}";
            $result = sql($sql);
            list($tudi_count) = $result->fetch_row();
            $sql = "SELECT `id`,`sex` FROM `game_user` WHERE `shifu.id`={$shifu_id} ORDER BY `baishi.time` ASC LIMIT 0,20";
            $result = sql($sql);
            while (list($oid, $osex) = $result->fetch_row()) {
                $ocount++;
                if ($oid == $user_id) {
                    $ucount = $ocount;
                }
                if ($tongmen_id == $oid) {
                    if ($ocount >= 10) {
                        $o_shimen_name .= "十";
                    }
                    $o_str_count = $ocount % 10;
                    switch ($o_str_count) {
                        case 1:
                            $o_shimen_name .= "一";
                            break;
                        case 2:
                            $o_shimen_name .= "二";
                            break;
                        case 3:
                            $o_shimen_name .= "三";
                            break;
                        case 4:
                            $o_shimen_name .= "四";
                            break;
                        case 5:
                            $o_shimen_name .= "五";
                            break;
                        case 6:
                            $o_shimen_name .= "六";
                            break;
                        case 7:
                            $o_shimen_name .= "七";
                            break;
                        case 8:
                            $o_shimen_name .= "八";
                            break;
                        case 9:
                            $o_shimen_name .= "九";
                            break;
                    }
                    if ($ocount == 1) {
                        $o_shimen_name = "大";
                    } else if ($ocount == 20 || $ocount == $tudi_count) {
                        $o_shimen_name = "小";
                    }
                    if ($shifu_id == $user_id) {
                        $o_shimen_name .= "徒儿";
                    } else {
                        $o_shimen_name .= "师";
                        if ($ucount >= $ocount && $ocount != 20) {
                            if ($osex == '男') {
                                $o_shimen_name .= "兄";
                            } else {
                                $o_shimen_name .= "姐";
                            }
                        } else {
                            if ($osex == '男') {
                                $o_shimen_name .= "弟";
                            } else {
                                $o_shimen_name .= "妹";
                            }
                        }
                    }
                    break;
                }
            }
        }
        if (!$o_shimen_name) {
            $o_shimen_name = "未知";
        }
        return $o_shimen_name;
    }

    //获取禁言
    static function get_jinyan_str($user_id)
    {
        $jinyan = value::get_user_value("jinyan", $user_id);
        $sy_time = $jinyan - time();
        if ($sy_time) {
            $hour = (int)($sy_time / 3600);
            $min = (int)($sy_time % 3600 / 60);
            return "{$hour}小时{$min}分钟";
        } else {
            return "0小时0分钟";
        }
    }

    //获取用户宠物
    static function get_pet_arr($user_id, $mode = 1, $num = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $pet_id_arr = array();
        if (!$num) {
            $sql = "SELECT `id` FROM `game_pet` WHERE `master_id`=$user_id AND `master_mode`=$mode ORDER BY `master_num` LIMIT 5";
            $rs = sql($sql);
            while (list($pet_id) = $rs->fetch_row()) {
                array_push($pet_id_arr, $pet_id);
            }
            return $pet_id_arr;
        } else {
            $num--;
            $sql = "SELECT `id` FROM `game_pet` WHERE `master_id`=$user_id AND `master_mode`=$mode ORDER BY `master_num` LIMIT $num,1";
            $rs = sql($sql);
            list($pet_id) = $rs->fetch_row();
            return $pet_id;
        }
    }

    static function get_pet_arr1($user_id, $mode = 1, $num = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $pet_id_arr = array();
        if (!$num) {
            $sql = "SELECT `id` FROM `game_pet` WHERE `master_id`=$user_id AND `master_mode`=$mode AND `is_yj`<1 ORDER BY `master_num` LIMIT 1";
            $rs = sql($sql);
            while (list($pet_id) = $rs->fetch_row()) {
                array_push($pet_id_arr, $pet_id);
            }
            return $pet_id_arr;
        } else {
            $num--;
            $sql = "SELECT `id` FROM `game_pet` WHERE `master_id`=$user_id AND `master_mode`=$mode AND `is_yj`<1 ORDER BY `master_num` LIMIT $num,1";
            $rs = sql($sql);
            list($pet_id) = $rs->fetch_row();
            return $pet_id;
        }
    }
    //获取用户宠物数量
    static function get_pet_count($user_id, $mode = 1)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return count(self::get_pet_arr($user_id, $mode));
    }

    static function get_pet_count1($user_id, $mode = 1)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return count(self::get_pet_arr1($user_id, $mode));
    }
    //获取来源社区
    static function get_community($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $community = "";
        sql("USE `{$GLOBALS['mysql_dbname']}`");
        $sql = "SELECT `community` FROM `user` WHERE `id`=$user_id LIMIT 1";
        $rs = sql($sql);
        if ($rs->num_rows) {
            list($community) = $rs->fetch_row();
        }
        sql("USE `{$GLOBALS['game_area_dbname']}`");
        return $community;
    }

    //获取来源社区 用户ID
    static function get_community_id($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $community = self::get_community($user_id);
        $account = self::get_account($user_id);
        return str_replace($community . "_", "", $account);
    }

    //获取来源社区 社区网址
    static function get_community_url($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $community = self::get_community($user_id);
        $community_arr = config::getConfigByName("community");
        $community_url = $community_arr[$community]['url'];
        return $community_url;
    }

    //获取用户账号
    static function get_account($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        sql("USE `{$GLOBALS['mysql_dbname']}`");
        $sql = "SELECT `name` FROM `user` WHERE `id`=$user_id LIMIT 1";
        $rs = sql($sql);
        list($account) = $rs->fetch_row();
        sql("USE `{$GLOBALS['game_area_dbname']}`");
        return $account;
    }

    //是否黑名
    static function is_heiming($user_id, $oid)
    {
        $sql = "SELECT `id` FROM `game_user_value` WHERE `userid`={$oid} AND `valuename`='{$oid}.heimingdan' AND `value`='{$user_id}' LIMIT 1";
        $result = sql($sql);
        list($value_id) = $result->fetch_row();
        if ($value_id) {
            return true;
        } else {
            return false;
        }
    }

    //获取vip等级
    static function getVipLvl($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $vip_lvl = 0;
        $recharge_money = value::get_user_value("recharge_money", $user_id);
        $vip_lvl_arr = config::getConfigByName("vip_lvl");
        foreach ($vip_lvl_arr as $lvl => $money) {
            if ($recharge_money >= $money) {
                $vip_lvl = $lvl;
            } else {
                break;
            }
        }
        return $vip_lvl;
    }

    //显示vip图片
    static function showVipLogo($user_id = 0, $echo = false, $vip_lvl = -1)
    {
        if ($vip_lvl < 0) {
            $vip_lvl = user::getVipLvl($user_id);
        }
        static $url = "";
        if (!$url) {
            $url = cmd::addcmd2url("e162");
        }
        $px = $vip_lvl < 32 ? 31 : 31;
        $html = <<<html
<img src="res/img/system/vip_{$vip_lvl}.gif">
html;
        if ($echo) {
            echo $html;
        }
        return $html;
    }

//获取最大生命值
    static function get_max_hp($user_id)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return c_uint(self::get_game_user('max_hp') + equip::get_user_equip_jc('hp', $user_id));
    }

    //获取经验
    static function get_exp($user_id, $exp, $is_pk = false)
    {
        //获取玩家原数据
        $sql = "select lvl,max_lvl,shuxingdian,exp,max_exp,hp,max_hp,mp,max_mp,sji_hp,sji_mp,pugong,pufang,tegong,tefang,minjie from game_user where id={$user_id} limit 1";
        $rs = sql($sql);
        list($old_lvl, $max_lvl, $add_shuxingdian, $old_exp, $max_exp, $old_hp, $old_max_hp, $old_mp, $old_max_mp, $osji_hp, $osji_mp, $old_pugong, $old_pufang, $old_tegong, $old_tefang, $old_minjie) = $rs->fetch_row();
        //增加人物经验
        $now_exp = user::add_game_user('exp', $exp, $user_id);
        if ($is_pk) {
            skill::add_skill_chat("你获得了{$exp}点经验", $user_id, 0);
        } else {
            echo "你获得了{$exp}点经验";
            br();
        }
        //获取玩家等级 六维
        $now_lvl = $old_lvl;
        $now_hp = $old_hp;
        $now_sji_hp = $osji_hp;
        $now_sji_mp = $osji_mp;
        $now_max_hp = $old_max_hp;
        $now_max_mp = $old_max_mp;
        $now_pugong = $old_pugong;
        $now_pufang = $old_pufang;
        $now_tegong = $old_tegong;
        $now_tefang = $old_tefang;
        $now_minjie = $old_minjie;
        if (value::get_game_user_value('zhiye') == '战士') {
            $zz_sji_hp = 3 * 10;
            $zz_sji_mp = 5;
            $zz_pugong = mt_rand(1, 3);
            $zz_pufang = mt_rand(1, 3);
            $zz_tegong = mt_rand(0, 1);
            $zz_tefang = mt_rand(0, 1);
            $zz_minjie = mt_rand(0, 1);
        }
        if (value::get_game_user_value('zhiye') == '法师') {
            $zz_sji_hp = 1 * 10;
            $zz_sji_mp = 3 * 5;
            $zz_pugong = mt_rand(0, 1);
            $zz_pufang = mt_rand(0, 1);
            $zz_tegong = mt_rand(1, 3);
            $zz_tefang = mt_rand(1, 3);
            $zz_minjie = mt_rand(0, 1);
        }
        if (value::get_game_user_value('zhiye') == '道士') {
            $zz_sji_hp = 2 * 10;
            $zz_sji_mp = 2 * 5;
            $zz_pugong = mt_rand(0, 2);
            $zz_pufang = mt_rand(0, 2);
            $zz_tegong = mt_rand(0, 2);
            $zz_tefang = mt_rand(0, 2);
            $zz_minjie = mt_rand(0, 1);
        }
        //玩家升级
        while ($now_exp >= $max_exp) {
            if ($now_lvl < $max_lvl) {
                //提升等级
                $now_lvl++;
                //提升max_exp
                if ($now_lvl < $max_lvl) {
                    $max_exp = ($now_lvl * ($now_lvl + 1) * (1 + $now_lvl) * (11 + $now_lvl) + 200 );
                }
                //增加属性点
                $add_shuxingdian1 = mt_rand(0, 2);
                $add_shuxingdian += $add_shuxingdian1;
                $now_max_hp += $zz_sji_hp;
                $now_max_mp += $zz_sji_mp;
                $now_sji_hp += $zz_sji_hp;
                $now_sji_mp += $zz_sji_mp;
                $now_pugong += $zz_pugong;
                $now_pufang += $zz_pufang;
                $now_tegong += $zz_tegong;
                $now_tefang += $zz_tefang;
                $now_minjie += $zz_minjie;
                $now_exp = 0;
                user::set_game_user('exp', 0, $user_id);
            } else {
                $now_exp = 0;
                user::set_game_user('exp', 0, $user_id);
                break;
            }
        }
        if ($old_lvl < $max_lvl) {
            //输出经验提示
            if ($is_pk) {
                skill::add_skill_chat('下次升级还要' . (int)($max_exp - $now_exp) . "经验", $user_id, 0);
            } else {
                echo '下次升级还要' . (int)($max_exp - $now_exp) . "经验";
                br();
            }
            //玩家是否升级了
            if ($now_lvl > $old_lvl) {
                //更新玩家数据
                $sql = "UPDATE `game_user` SET `lvl`={$now_lvl},`max_exp`={$max_exp},`shuxingdian`={$add_shuxingdian},`max_hp`={$now_max_hp},`max_mp`={$now_max_mp},`hp`={$now_max_hp},`mp`={$now_max_mp},`sji_hp`={$now_sji_hp},`sji_mp`={$now_sji_mp},`pugong`={$now_pugong},`pufang`={$now_pufang},`tegong`={$now_tegong},`tefang`={$now_tefang},`minjie`={$now_minjie} WHERE `id`={$user_id} LIMIT 1";
                sql($sql);
                $new_max_hp = self::get_max_hp($user_id);
                user::set_game_user('hp', $new_max_hp);
                //显示处理
                $old_hp = (int)$old_hp;
                $old_max_hp = (int)$old_max_hp;
                $old_sji_hp = (int)($old_sji_hp < 1 ? 0 : $old_sji_hp);
                $old_sji_mp = (int)($old_sji_mp < 1 ? 0 : $old_sji_mp);
                $old_pugong = (int)($old_pugong < 1 ? 0 : $old_pugong / 1);
                $old_pufang = (int)($old_pufang < 1 ? 0 : $old_pufang / 1);
                $old_tegong = (int)($old_tegong < 1 ? 0 : $old_tegong / 1);
                $old_tefang = (int)($old_tefang < 1 ? 0 : $old_tefang / 1);
                $old_minjie = (int)($old_minjie < 1 ? 0 : $old_minjie / 1);
                $now_hp = (int)$now_hp;
                $now_max_hp = (int)$now_max_hp;
                $now_pugong = (int)$now_pugong;
                $now_pufang = (int)$now_pufang;
                $now_tegong = (int)$now_tegong;
                $now_tefang = (int)$now_tefang;
                $now_minjie = (int)$now_minjie;
                //显示属性数据
                if ($is_pk) {
                    skill::add_skill_chat("恭喜你升级了!<br>等级:{$old_lvl} → {$now_lvl}<br>生命:{$old_hp} → {$now_hp}<br>攻击:{$old_pugong} → {$now_pugong}<br>防御:{$old_pufang} → {$now_pufang}<br>魔攻:{$old_tegong} → {$now_tegong}<br>魔防:{$old_tefang} → {$now_tefang}<br>灵活:{$old_minjie} → {$now_minjie}", $user_id, 0);
                } else {
                    echo <<<uplvl
你升级了!<br>
等级:{$old_lvl} → {$now_lvl}<br>
生命值:{$osji_hp} → {$now_sji_hp}<br>
魔法值:{$osji_mp} → {$now_sji_mp}<br>
攻击:{$old_pugong} → {$now_pugong}<br>
防御:{$old_pufang} → {$now_pufang}<br>
魔攻:{$old_tegong} → {$now_tegong}<br>
魔防:{$old_tefang} → {$now_tefang}<br>
灵活:{$old_minjie} → {$now_minjie}<br>
属性点：{$add_shuxingdian1}<br>
uplvl;
                }
            }
        } else {
            if ($is_pk) {
                skill::add_skill_chat('你已经满级了。', $user_id, 0);
            } else {
                echo '你已经满级了。';
                br();
            }
        }
    }

//退出战斗
    static function exit_pk($user_id)
    {
        if ($user_id) {
            //队伍 ID
            $team_id = value::get_game_user_value('team_id', $user_id);
            //NPC ID
            $npc_id = value::get_game_user_value('pk.npc.id', $user_id);
            //设置人物返回地图
            value::set_game_user_value('in_ctm', 'ctm_map', $user_id);
            //野怪
            $o_pk_map_pet_id = value::get_game_user_value('pk.map_pet.id', $user_id);
            if ($o_pk_map_pet_id && !value::get_pet_value($o_pk_map_pet_id, 'master_id')) {
                //离开PK
                value::set_game_user_value('is_pk_id', 0, $user_id);
                value::set_pet_value($o_pk_map_pet_id, 'enemy_user', '0');
                $obj = new game_pet_object($o_pk_map_pet_id);
                $obj->adel();
            }
            //还原挑战野怪ID
            value::set_game_user_value('pk.map_pet.id', '0', $user_id);
            //NPC
            if ($npc_id) {
                //单人
                //删除宠物
                $sql = "SELECT `id` FROM `game_pet` WHERE `enemy_user` = $user_id AND `team_id` = 0 AND `npc_id` > 0";
                $rs = sql($sql);
                while (list($d_pet_id) = $rs->fetch_row()) {
                    pet::del_pet($d_pet_id);
                }
                //组队
                if ($team_id) {
                    //删除宠物
                    $can_delete_team_npc_pet = true;
                    $sql = "SELECT 1 FROM `game_pet` WHERE `team_id` = {$team_id} AND `npc_id` = {$npc_id} LIMIT 1";
                    $result = sql($sql);
                    list($team_is_pk_npc) = $result->fetch_row();
                    if ($team_is_pk_npc) {
                        $team_user_arr = team::get_team_user($team_id);
                        foreach ($team_user_arr as $team_user_id) {
                            if (value::get_game_user_value('pk.npc.id', $team_user_id) == $npc_id && $team_user_id != $user_id) {
                                $can_delete_team_npc_pet = false;
                                break;
                            }
                        }
                    }
                    if ($can_delete_team_npc_pet) {
                        $sql = "SELECT `id` FROM `game_pet` WHERE `team_id` = {$team_id} AND `npc_id` = {$npc_id}";
                        $rs = sql($sql);
                        while (list($d_pet_id) = $rs->fetch_row()) {
                            pet::del_pet($d_pet_id);
                        }
                    }
                }
            }
            //还原挑战NPCID
            value::set_game_user_value('pk.npc.id', '0', $user_id);
            //玩家
            //还原挑战玩家ID
            value::set_game_user_value('pk.user.id', '0', $user_id);
            //自己
            $sql = "SELECT `id` FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=1 LIMIT 5";
            $result = sql($sql);
            while (list($o_pet_id) = $result->fetch_row()) {
                //设置宠物未PK
                //设置宠物异常血量
                $o_max_hp = pet::get_max_hp($o_pet_id);
                $o_hp = value::get_pet_value($o_pet_id, 'hp');
                if ($o_hp > $o_max_hp) {
                    value::set_pet_value($o_pet_id, 'hp', $o_max_hp);
                }
                //清除宠物异常状态
                $o_pet_zhuangtai = value::get_pet_value($o_pet_id, 'zhuangtai');
                if ($o_pet_zhuangtai == 4 || $o_pet_zhuangtai == 7 || $o_pet_zhuangtai == 9 || $o_pet_zhuangtai == 10) {
                    value::set_pet_value($o_pet_id, 'zhuangtai', 0);
                }
                //删除宠物PK属性
                $obj = new game_pet_object($o_pet_id);
                $obj->adel("pk.*");
            }
            //删除PK属性
            sql("DELETE FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '%.pk.%'");
            //玩家离开PK
            value::set_game_user_value('is_pk', '0', $user_id);
            $u = new game_user_object($user_id);
            $u->set("next.skill.id", 0);
        }
    }
}

//用户技能类
Class user_skill
{

    //学习技能
    static function study_skill($skillId, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        if (!self::have_skill($skillId, $userId)) {
            $u = new game_user_object($userId);
            $u->jpush('skill.arr', NULL, $skillId);
            return true;
        } else {
            return false;
        }
    }

    //遗忘技能
    static function forget_skill($skillId, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        // 攻击技能(1)是玩家必备技能，不允许遗忘
        if ($skillId == 1) {
            return false;
        }
        if (self::have_skill($skillId, $userId)) {
            $u = new game_user_object($userId);
            $u->jpop_by_value('skill.arr', $skillId);
            if ($u->get('skill_moren.id') == $skillId) {
                $u->set('skill_moren.id', 0);
            }
            return true;
        } else {
            return false;
        }
    }

    //是否拥有技能
    static function have_skill($skillId, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        $uSkillArr = $u->jget('skill.arr');
        foreach ($uSkillArr as $uSkillId) {
            if ($uSkillId == $skillId) {
                return true;
            }
        }
        return false;
    }

    //获取默认技能
    static function get_moren_skill($userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        return $u->get('skill_moren.id');
    }

    //获取所有技能
    static function get_skill_arr($userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        return $u->jget('skill.arr');
    }

   //使用技能
   static function use_skill($skillId, $userId, $oUserId, $oPetId, $mode = 0)
   {
        //技能
        //玩家使用祝福神水效果
        $u_wj_mgs = value::get_user_value('wj_mgs', $userId);
        $u_wj_mfs = value::get_user_value('wj_mfs', $userId);
        $u_wj_gjs = value::get_user_value('wj_gjs', $userId);
        $u_wj_fys = value::get_user_value('wj_fys', $userId);
        $o_wj_mgs = value::get_user_value('wj_mgs', $oUserId);
        $o_wj_mfs = value::get_user_value('wj_mfs', $oUserId);
        $o_wj_gjs = value::get_user_value('wj_gjs', $oUserId);
        $o_wj_fys = value::get_user_value('wj_fys', $oUserId);
        $ch_sx_gj = value::get_user_value('ch_sx_gj');
        $ch_sx_fy = value::get_user_value('ch_sx_fy');
        $sql = "SELECT * FROM `skill` WHERE `id`={$skillId} LIMIT 1";
        $rs = sql($sql);
        $jn = $rs->fetch_array(MYSQLI_ASSOC);
        //己方属性
        $sql = "SELECT * FROM `game_user` WHERE `id`={$userId} LIMIT 1";
        $rs = sql($sql);
        $uv = $rs->fetch_array(MYSQLI_ASSOC);
        $uv['max_hp'] += equip::get_user_equip_jc('hp', $userId);
        $uv['pugong'] += (equip::get_user_equip_jc('pugong', $userId) + $u_wj_gjs + $uv['tz_gj'] + $ch_sx_gj);
        $uv['pufang'] += (equip::get_user_equip_jc('pufang', $userId) + $u_wj_fys + $uv['tz_fy'] + $ch_sx_fy);
        $uv['tegong'] += (equip::get_user_equip_jc('tegong', $userId) + $u_wj_mgs + $uv['tz_gj'] + $ch_sx_gj);
        $uv['tefang'] += (equip::get_user_equip_jc('tefang', $userId) + $u_wj_mfs + $uv['tz_fy'] + $ch_sx_fy);
        $uv['minjie'] += equip::get_user_equip_jc('minjie', $userId);
        //玩家组队加成效果
        $team_id = team::get_user_team($userId);
        $team_count = 0;
        if ($team_id) {
            $captain_user_id = team::get_team_captain_user_id($team_id);
            $team_user_name = value::get_game_user_value('name', $captain_user_id);
            $sql = "SELECT `id` FROM `game_user` WHERE `team_id`={$team_id} LIMIT 5";
            $result = sql($sql);
            while (list($team_oid) = $result->fetch_row()) {
                $team_count += 3;
            }
        }
        //会员加成属性属性
        $wj_sx_qzwd_jc = value::get_user_value('wj_sx_qzwd_jc', $userId);
        $u_hy_lvl = value::get_user_value('hy_lvl', $userId) * 10 + $wj_sx_qzwd_jc;
        if ($u_hy_lvl > 0) {
            $uv['pugong'] += ($uv['pugong'] / 100 * $u_hy_lvl);
            $uv['pufang'] += ($uv['pufang'] / 100 * $u_hy_lvl);
            $uv['tegong'] += ($uv['tegong'] / 100 * $u_hy_lvl);
            $uv['tefang'] += ($uv['tefang'] / 100 * $u_hy_lvl);
        }
        $team_count1 = $team_count;
        if ($team_count1 > 0) {
            $uv['pugong'] += (($uv['pugong'] / 100) * $team_count1);
            $uv['pufang'] += (($uv['pufang'] / 100) * $team_count1);
            $uv['tegong'] += (($uv['tegong'] / 100) * $team_count1);
            $uv['tefang'] += (($uv['tefang'] / 100) * $team_count1);
        }
        //记录天梯玩家属性
        $xt_ttbx_gj = $uv['pugong'] + $uv['tegong'];
        $xt_ttbx_fy = $uv['pufang'] + $uv['tefang'];
        value::set_user_value('xt_ttbx_gj', $xt_ttbx_gj, $userId);
        value::set_user_value('xt_ttbx_fy', $xt_ttbx_fy, $userId);
        //敌方属性
        if ($oUserId) {
            $sql = "SELECT * FROM `game_user` WHERE `id`={$oUserId} LIMIT 1";
            $rs = sql($sql);
            $ov = $rs->fetch_array(MYSQLI_ASSOC);
        }
        $ov['max_hp'] += equip::get_user_equip_jc('hp', $oUserId);
        $ov['pugong'] += (equip::get_user_equip_jc('pugong', $oUserId) + $o_wj_gjs);
        $ov['pufang'] += (equip::get_user_equip_jc('pufang', $oUserId) + $o_wj_fys);
        $ov['tegong'] += (equip::get_user_equip_jc('tegong', $oUserId) + $o_wj_mgs);
        $ov['tefang'] += (equip::get_user_equip_jc('tefang', $oUserId) + $o_wj_mfs);
        $ov['minjie'] += equip::get_user_equip_jc('minjie', $oUserId);
        //会员加成属性属性
        $o_hy_lvl = value::get_user_value('hy_lvl', $oUserId);
        if ($o_hy_lvl > 0) {
            $ov['pugong'] += ($ov['pugong'] / 10 * $o_hy_lvl);
            $ov['pufang'] += ($ov['pufang'] / 10 * $o_hy_lvl);
            $ov['tegong'] += ($ov['tegong'] / 10 * $o_hy_lvl);
            $ov['tefang'] += ($ov['tefang'] / 10 * $o_hy_lvl);
        }
        //敌方宠物属性
        if ($oPetId) {
            $sql = "SELECT * FROM `game_pet` WHERE `id`={$oPetId} LIMIT 1";
            $rs = sql($sql);
            $opv = $rs->fetch_array(MYSQLI_ASSOC);
        }
        $opv['max_hp'] += pet::get_liuwei_jiacheng($oPetId, 'hp');
        $opv['pugong'] += pet::get_liuwei_jiacheng($oPetId, 'pugong');
        $opv['pufang'] += pet::get_liuwei_jiacheng($oPetId, 'pufang');
        $opv['tegong'] += pet::get_liuwei_jiacheng($oPetId, 'tegong');
        $opv['tefang'] += pet::get_liuwei_jiacheng($oPetId, 'tefang');
        $opv['minjie'] += pet::get_liuwei_jiacheng($oPetId, 'minjie');
        //技能属性
        $jLeiXing = $jn['leixing'];
        $next_taopao = value::get_user_value('pk.next_taopao', $userId);
        //辅助技能效果
        $zd_tz = value::get_user_value('zd_tz', $userId);
        if ($jLeiXing > 2 && $zd_tz < 1) {
            $jn_lvl = value::get_user_value('skill.' . $jn['id'] . '.lvl', $userId);
            $jn_lvl_mp = $jn['mp'] * ( $jn_lvl + 1 );
            value::set_user_value('zd_tz', 1, $userId);
            if ($uv['mp'] >= $jn_lvl_mp) {
                $uv['mp'] = user::add_game_user('mp', -1 * $jn_lvl_mp, $UserId);
                value::add_user_value('skill.' . $jn['id'] . '.exp', 1, $UserId);
                if (mt_rand(1, 100) < $jn['mingzhong']) {
                    fz_jnxg($skillId, $userId, $oUserId, $oPetId);
                    return;
                } else {
                    value::set_user_value('zd_tz', 1, $userId);
                    $skillChat1 = "{$uv['name']}使用了{$jn['name']}失败";
                    skill::add_skill_chat($skillChat1, $userId, $oUserId);
                }
            } else {
                $skillChat1 = "{$uv['name']}没有足够魔法值使用了{$jn['name']}";
                skill::add_skill_chat($skillChat1, $userId, $oUserId);
            }
        }
        $zd_mb = value::get_user_value('zd_mb', $userId);
        if ($zd_mb > 0 && !$uv['is_dead']) {
            skill::add_skill_chat($uv['name'] . '被麻痹了', $userId, $oUserId);
        }
        $jn_mz = value::get_user_value('jn_mz', $userId);
        $dy_zd = value::get_user_value('dy_zd', $oUserId);
        $dy_fy = 0;
        if ($dy_zd > 0) {
            $dy_fy = value::get_user_value('dy_fy', $oUserId);
        }
        $zd_fs_hd = value::get_user_value('zd_fs_hd', $oUserId);
        $zd_fs_fy = 0;
        if ($zd_fs_hd > 0) {
            $zd_fs_fy = value::get_user_value('zd_fs_fy', $oUserId);
        }
        $zd_ds_qm = value::get_user_value('zd_ds_qm', $userId);
        $zd_ds_gj = 0;
        if ($zd_ds_qm > 0) {
            $zd_ds_gj = value::get_user_value('zd_ds_gj', $userId);
        }
        $ds_hd = value::get_user_value('ds_hd', $oUserId);
        $ds_mf_fy = 0;
        if ($ds_hd > 0) {
            $ds_mf_fy = value::get_user_value('ds_mf_fy', $oUserId);
        }
        $ds_fy = value::get_user_value('ds_fy', $oUserId);
        $ds_wl_fy = 0;
        if ($ds_fy > 0) {
            $ds_wl_fy = value::get_user_value('ds_wl_fy', $oUserId);
        }

        //攻击玩家
        $zd_tz = value::get_user_value('zd_tz', $userId);
        if ($oUserId && !$ov['is_dead'] && $next_taopao < 1 && $zd_mb < 1 && $zd_tz < 1) {
            if (!$uv['is_dead']) {
                if (!$ov['is_dead']) {
                    $mb_jc = equip::get_user_equip_jc('mb', $userId);
                    $km_jc = equip::get_user_equip_jc('km', $oUserId);
                    if (mt_rand(0, 100) < ($mb_jc - $km_jc)) {
                        value::set_user_value('zd_mb', 2, $oUserId);
                        skill::add_skill_chat($ov['name'] . '被' . $uv['name'] . '麻痹了', $userId, $oUserId);
                    }
                    $mb_xx = equip::get_user_equip_jc('xx', $userId);
                    if (mt_rand(0, 100) < $mb_xx) {
                        $fy_hp = (int)(($uv['lvl'] * 1 + 2 + $jn_wl + $uv['pugong']) / 10);
                        value::add_game_user_value('hp', $fy_hp, $userId);
                        skill::add_skill_chat($ov['name'] . '的血量被' . $uv['name'] . '吸血了' . $fy_hp . '点', $userId, $oUserId);
                    }
                    $mb_xx = equip::get_user_equip_jc('ts_jz9', $userId);
                    if ($mb_xx == 5) {
                        $fy_hp = (int)(($uv['lvl'] * 1 + 2 + $jn_wl + $uv['pugong']) / 10);
                        value::add_game_user_value('hp', $fy_hp, $userId);
                        skill::add_skill_chat($ov['name'] . '的血量被' . $uv['name'] . '吸血了' . $fy_hp . '点', $userId, $oUserId);
                    }
                    $zd_jc = equip::get_user_equip_jc('zd', $userId);
                    $kd_jc = equip::get_user_equip_jc('kd', $oUserId);
                    if (mt_rand(0, 100) < ($zd_jc - $kd_jc)) {
                        $zdhp = (int)(($uv['lvl'] * 1 + 2 + $jn_wl + $uv['pugong']) / 10);
                        value::set_user_value('zd_zd_hp', $zdhp, $oUserId);
                        value::add_game_user_value('hp', -1 * $zdhp, $oUserId);
                        value::set_user_value('zd_zd', 2, $oUserId);
                        skill::add_skill_chat($ov['name'] . '被' . $uv['name'] . '中毒了,减血' . $zdhp . '点', $UserId, $oUserId);
                    }
                }
            }
        }
        if ($oUserId && !$ov['is_dead'] && $next_taopao < 1 && $zd_mb < 1 && $zd_tz < 1) {
            $jn_lvl = value::get_user_value('skill.' . $jn['id'] . '.lvl', $userId);
            $jn_exp = value::get_user_value('skill.' . $jn['id'] . '.exp', $userId);
            $jn_max_exp = $jn_lvl * 600 + 600;
            $jn_wl = $jn_lvl * ($jn['weili'] / 5) + $jn['weili'];
            $jn_lvl_mp = $jn['mp'] * ( $jn_lvl + 1 );
            if ($uv['mp'] >= $jn_lvl_mp) {
                $uv['mp'] = user::add_game_user('mp', -1 * $jn_lvl_mp, $UserId);
                $lianji = 1;
                $lianji = mt_rand($jn['min_lianji'], $jn['max_lianji']);
                    for ($i = 0; $i < $lianji; $i++) {
                        if (mt_rand(1, 100) <= ($jn['mingzhong'] + $jn_mz)) {
                            //普攻
                            if ($jLeiXing == 1) {
                                $gongshi = (($zd_ds_gj + $uv['lvl'] * 1 + 2 + $jn['weili'] + $uv['pugong']) - ($ds_wl_fy + $zd_fs_fy + $ov['pufang'] + ($ov['lvl'] * 1 + 2) - $dy_fy));
                                $gongshi = c_uint($gongshi);
                                if ($i == 0) {
                                    if ($jn['id'] == 1) {
                                        $skillChat = "{$uv['name']}{$jn['name']}了{$ov['name']}，造成{$gongshi}点伤害！";
                                    } else {
                                        $skillChat = "{$uv['name']}施展了{$jn['name']}攻击了{$ov['name']}，造成{$gongshi}点伤害！";
                                    }
                                } else {
                                    $skillChat = "{$uv['name']}连续{$i}次{$jn['name']}了{$ov['name']}，造成{$gongshi}点伤害！";
                                }
                                skill::add_skill_chat($skillChat, $userId, 0);
                            } else {
                                //特攻
                                $gongshi = (($zd_ds_gj + $uv['lvl'] * 1 + 2 + $jn['weili'] + $uv['tegong']) - ($ds_mf_fy + $zd_fs_fy + $ov['tefang'] + ($ov['lvl'] * 1 + 2) - $dy_fy));
                                $gongshi = c_uint($gongshi);
                                if ($i == 0) {
                                    if ($jn['id'] == 1) {
                                        $skillChat = "{$uv['name']}{$jn['name']}了{$ov['name']}，造成{$gongshi}点伤害！";
                                    } else {
                                        $skillChat = "{$uv['name']}施展了{$jn['name']}攻击了{$ov['name']}，造成{$gongshi}点伤害！";
                                    }
                                } else {
                                    $skillChat = "{$uv['name']}连续{$i}次施展了{$jn['name']}攻击了{$ov['name']}，造成{$gongshi}点伤害！";
                                }
                                skill::add_skill_chat($skillChat, $userId, 0);
                            }
                            $wj_bj = value::get_user_value('wj_bj', $userId) + equip::get_user_equip_jc('bj', $userId);
                            //暴击
                            if (mt_rand(1, 100) <= $wj_bj) {
                                $gongshi *= 1.7;
                                $gongshi = (int)($gongshi);
                                skill::add_skill_chat("{$uv['name']}暴击了{$ov['name']}，造成{$gongshi}点伤害！", $userId, $oUserId);
                            }
                            $tz_gj_jm = value::get_user_value('tz_gj_jm', $oUserId);
                            if ($tz_gj_jm) {
                                $gongshi1 = $gongshi - ( $gongshi / 100 * $tz_gj_jm);
                                $gongshi = $gongshi1;
                            }
                            $tz_gj_js = value::get_user_value('tz_gj_js', $userId);
                            if ($tz_gj_js) {
                                $gongshi1 = $gongshi + ( $gongshi / 100 * $tz_gj_js);
                                $gongshi = $gongshi1;
                            }
                            $ov['hp'] = user::add_game_user('hp', -1 * $gongshi, $oUserId);
                            if ($ov['hp'] <= 0) {
                                user::set_game_user('hp', 0, $oUserId);
                                user::set_game_user('is_dead', 1, $oUserId);
                            }
                        } else {
                            $skillChat = "{$ov['name']}闪避了{$uv['name']}的{$jn['name']}！";
                            skill::add_skill_chat($skillChat, $userId, 0);
                        }
                        $gongshi = c_uint($gongshi + mt_rand(1, $uv['lvl']));
                        $ov['hp'] = user::add_game_user('hp', -1 * $gongshi, $oUserId);
                        if ($jn_exp <= $jn_max_exp) {
                            value::add_user_value('skill.' . $jn['id'] . '.exp', 1, $userId);
                        }
                        if ($jn_lvl < 3 && $jn_exp >= $jn_max_exp) {
                            value::add_user_value('skill.' . $jn['id'] . '.lvl', 1, $userId);
                            value::set_user_value('skill.' . $jn['id'] . '.exp', 0, $userId);
                            $jn_lvl = value::get_user_value('skill.' . $jn['id'] . '.lvl', $userId);
                            if (!$jn_lvl) {
                                $jn_lvl_name = "初级";
                            } else if ($jn_lvl == 1) {
                                $jn_lvl_name = "中级";
                            } else if ($jn_lvl == 2) {
                                $jn_lvl_name = "高级";
                            } else if ($jn_lvl == 3) {
                                $jn_lvl_name = "专家";
                            }
                            $skillChat = "{$uv['name']}的{$jn['name']}升级了,升到{$jn_lvl_name}了！";
                            skill::add_skill_chat($skillChat, $userId, 0);
                        }
                        if ($ov['hp'] <= 0) {
                            user::set_game_user('hp', 0, $oUserId);
                            user::set_game_user('is_dead', 1, $oUserId);
                        }
                    }
            } else {
                $skillChat1 = "{$uv['name']}没有足够魔法值施展{$jn['name']}";
                skill::add_skill_chat($skillChat1, $userId, $oUserId);
            }
        }
        //攻击宠物
        $pet_dy_zd = value::get_pet_value2('dy_zd', $oPetId);
        $pet_dy_fy = 0;
        if ($pet_zd_zd > 0) {
            $pet_dy_fy = value::get_pet_value2('dy_fy', $oPetId);
        }
        $pet_zd_zd = value::get_pet_value2('zd_zd', $oPetId);
        $pet_zd_zd_hp = value::get_pet_value2('zd_zd_hp', $oPetId);
        $pet_zd_mb = value::get_pet_value2('zd_mb', $oPetId);
        if ($pet_zd_zd > 0) {
            value::add_pet_value($oPetId, 1, -1 * $pet_zd_zd_hp, false);
            skill::add_skill_chat($opv['name'] . '中毒了,减血' . $pet_zd_zd_hp . '点', $userId, $oUserId);
        }
        if ($oPetId && !$opv['is_dead'] && $next_taopao < 1 && $zd_mb < 1 && $zd_tz < 1) {
            if (!$uv['is_dead']) {
                if (!$opv['is_dead']) {
                    $mb_jc = equip::get_user_equip_jc('mb', $userId);
                    if (mt_rand(0, 100) < $mb_jc) {
                        value::set_pet_value2('zd_mb', 2, $oPetId);
                        skill::add_skill_chat($opv['name'] . '被' . $uv['name'] . '麻痹了', $userId, $oUserId);
                    }
                    $mb_xx = equip::get_user_equip_jc('xx', $userId);
                    if (mt_rand(0, 100) < $mb_xx) {
                        $fy_hp = (int)(($uv['lvl'] * 1 + 2 + $jn_wl + $uv['pugong']) / 10);
                        value::add_game_user_value('hp', $fy_hp, $userId);
                        value::add_pet_value($oPetId, 1, -1 * $fy_hp, false);
                        skill::add_skill_chat($opv['name'] . '的血量被' . $uv['name'] . '吸血了' . $fy_hp . '点', $userId, $oUserId);
                    }
                    $mb_xx = equip::get_user_equip_jc('ts_jz9', $userId);
                    if ($mb_xx == 5) {
                        $fy_hp = (int)(($uv['lvl'] * 1 + 2 + $jn_wl + $uv['pugong']) / 10);
                        value::add_game_user_value('hp', $fy_hp, $userId);
                        value::add_pet_value($oPetId, 1, -1 * $fy_hp, false);
                        skill::add_skill_chat($opv['name'] . '的血量被' . $uv['name'] . '吸血了' . $fy_hp . '点', $userId, $oUserId);
                    }
                    $zd_jc = equip::get_user_equip_jc('zd', $userId);
                    if (mt_rand(0, 100) < $zd_jc) {
                        $zdhp = (int)(($uv['lvl'] * 1 + 2 + $jn_wl + $uv['pugong']) / 10);
                        value::set_pet_value2('zd_zd', 2, $oPetId);
                        value::set_pet_value2('zd_zd_hp', $zdhp, $oPetId);
                        value::add_pet_value($oPetId, 1, -1 * $zdhp, false);
                        skill::add_skill_chat($opv['name'] . '被' . $uv['name'] . '中毒了,减血' . $zdhp . '点', $userId, $oUserId);
                    }
                }
            }
        }
        if ($oPetId && !$opv['is_dead'] && $next_taopao < 1 && $zd_mb < 1 && $zd_tz < 1) {
            $jLeiXing = $jn['leixing'];
            $jn_lvl = value::get_user_value('skill.' . $jn['id'] . '.lvl', $userId);
            $jn_exp = value::get_user_value('skill.' . $jn['id'] . '.exp', $userId);
            $jn_max_exp = $jn_lvl * 600 + 600;
            $jn_wl = $jn_lvl * ($jn['weili'] / 5) + $jn['weili'];
            $jn_lvl_mp = $jn['mp'] * ( $jn_lvl + 1);
            if ($uv['mp'] >= $jn_lvl_mp) {
                $uv['mp'] = user::add_game_user('mp', -1 * $jn_lvl_mp, $UserId);
                $lianji = 1;
                $lianji = mt_rand($jn['min_lianji'], $jn['max_lianji']);
                    for ($i = 0; $i < $lianji; $i++) {
                        if (mt_rand(1, 100) <= ($jn['mingzhong'] + $jn_mz)) {
                            if ($jLeiXing == 1) {
                                $gongshi_1 = 0;
                                $in_map_id = value::get_game_user_value('in_map_id', $userId);
                                $yg_area_id = value::get_map_value($in_map_id, 'area_id');
                                if ($yg_area_id == 89) {
                                    $gc_gjgw_gj = value::get_user_value('gc_gjgw_gj', $userId);
                                    if ($gc_gjgw_gj > 0) {
                                        $gongshi_1 = (($zd_ds_gj + $uv['lvl'] * 1 + 2 + $jn_wl + $uv['pugong']) / 100 * $gc_gjgw_gj);
                                    }
                                }
                                $gongshi = (($zd_ds_gj + $uv['lvl'] * 1 + 2 + $jn_wl + $uv['pugong'] + $gongshi_1) - ($opv['pufang'] + ($opv['lvl'] * 1 + 2) - $pet_dy_fy));
                                $gongshi = c_uint($gongshi);
                                if ($i == 0) {
                                    if ($skillId == 1) {
                                        $skillChat = "{$uv['name']}{$jn['name']}了{$opv['name']}，造成{$gongshi}点伤害！";
                                    } else {
                                        $skillChat = "{$uv['name']}施展了{$jn['name']}攻击了{$opv['name']}，造成{$gongshi}点伤害！";
                                    }
                                } else {
                                    $skillChat = "{$uv['name']}连续{$i}次{$jn['name']}了{$opv['name']}，造成{$gongshi}点伤害！";
                                }
                                skill::add_skill_chat($skillChat, $userId, $oUserId);
                            } else {
                                //特攻
                                $gongshi_1 = 0;
                                $in_map_id = value::get_game_user_value('in_map_id', $userId);
                                $yg_area_id = value::get_map_value($in_map_id, 'area_id');
                                if ($yg_area_id == 89) {
                                    $gc_gjgw_gj = value::get_user_value('gc_gjgw_gj', $userId);
                                    if ($gc_gjgw_gj > 0) {
                                        $gongshi_1 = (($zd_ds_gj + $uv['lvl'] * 1 + 2 + $jn_wl + $uv['tegong']) / 100 * $gc_gjgw_gj);
                                    }
                                }
                                $gongshi = (($zd_ds_gj + $uv['lvl'] * 1 + 2 + $jn_wl + $uv['tegong']+ $gongshi_1) - ($opv['tefang'] + ($opv['lvl'] * 1 + 2) - $pet_dy_fy));
                                $gongshi = c_uint($gongshi);
                                if ($i == 0) {
                                    if ($skillId == 1) {
                                        $skillChat = "{$uv['name']}{$jn['name']}了{$opv['name']}，造成{$gongshi}点伤害！";
                                    } else {
                                        $skillChat = "{$uv['name']}施展了{$jn['name']}攻击了{$opv['name']}，造成{$gongshi}点伤害！";
                                    }
                                } else {
                                    $skillChat = "{$uv['name']}连续{$i}次施展了{$jn['name']}攻击了{$opv['name']}，造成{$gongshi}点伤害！";
                                }
                                skill::add_skill_chat($skillChat, $userId, $oUserId);
                            }
                            $wj_bj = value::get_user_value('wj_bj', $userId) + equip::get_user_equip_jc('bj', $userId);
                            //暴击
                            if (mt_rand(1, 100) <= $wj_bj) {
                                $gongshi *= 1.7;
                                $gongshi = (int)($gongshi);
                                skill::add_skill_chat("{$uv['name']}暴击了{$opv['name']}，造成{$gongshi}点伤害！", $userId, $oUserId);
                            }
                            $tz_gj_js = value::get_user_value('tz_gj_js', $userId);
                            if ($tz_gj_js) {
                                $gongshi1 = $gongshi + ( $gongshi / 100 * $tz_gj_js);
                                $gongshi = $gongshi1;
                            }
                            if (mt_rand(1, 100) <= $jn['zhongji']) {
                                $gongshi *= 2;
                            }
                            $gongshi = c_uint($gongshi + mt_rand(0, 1));
                            //攻城总伤害
                            $in_map_id = value::get_game_user_value('in_map_id', $userId);
                            $yg_area_id = value::get_map_value($in_map_id, 'area_id', $userId);
                            if ($yg_area_id == 89) {
                                value::add_user_value('gwgc_sh', $gongshi, $userId);
                            }
                            $opv['hp'] = value::add_pet_value($oPetId, 1, -1 * $gongshi, false);
                            if ($jn_exp <= $jn_max_exp) {
                                value::add_user_value('skill.' . $jn['id'] . '.exp', 1, $userId);
                            }
                            if ($jn_lvl < 3 && $jn_exp >= $jn_max_exp) {
                                value::add_user_value('skill.' . $jn['id'] . '.lvl', 1, $userId);
                                $jn_lvl = value::get_user_value('skill.' . $jn['id'] . '.lvl', $userId);
                                if (!$jn_lvl) {
                                    $jn_lvl_name = "初级";
                                } else if ($jn_lvl == 1) {
                                    $jn_lvl_name = "中级";
                                } else if ($jn_lvl == 2) {
                                    $jn_lvl_name = "高级";
                                } else if ($jn_lvl == 3) {
                                    $jn_lvl_name = "专家";
                                }
                                $skillChat = "{$uv['name']}的{$jn['name']}升级了,升到{$jn_lvl_name}了！";
                                value::set_user_value('skill.' . $jn['id'] . '.exp', 0, $userId);
                                skill::add_skill_chat($skillChat, $userId, 0);
                            }
                            if (!$opv['hp']) {
                                value::set_pet_value($oPetId, 'is_dead', 1);
                                $opv['is_dead'] = 1;
                            }
                        } else {
                            $skillChat = "{$opv['name']}闪避了{$uv['name']}的{$jn['name']}！";
                            skill::add_skill_chat($skillChat, $userId, $oUserId);
                        }
                    }
            } else {
                $skillChat = "{$uv['name']}没有足够的魔法值施展{$jn['name']}";
                skill::add_skill_chat($skillChat, $userId, $oUserId);
            }
        }
        //被宠物攻击
        $pet_zd_tz = value::get_pet_value2('zd_tz', $oPetId);
        if ($oPetId && !$opv['is_dead'] && !$uv['is_dead'] && !$pet_zd_tz) {
            if (!$opv['is_dead']) {
                if (!$uv['is_dead']) {
                    if (mt_rand(0, 100) < $opv['ts_mb']) {
                        value::set_user_value('zd_mb', 2, $userId);
                        skill::add_skill_chat($uv['name'] . '被' . $opv['name'] . '麻痹了', $userId, $oUserId);
                    }
                    if (mt_rand(0, 100) < $opv['ts_xx']) {
                        $fy_hp = (int)(($opv['lvl'] * 1 + 2 + $jn['weili'] + $opv['pugong']) / 10);
                        value::add_pet_value($oPetId, 1, $fy_hp, false);
                        skill::add_skill_chat($uv['name'] . '的血量被' . $opv['name'] . '吸血了' . $fy_hp . '点', $userId, $oUserId);
                    }
                    if (mt_rand(0, 100) < $opv['ts_zd']) {
                        $zdhp = (int)(($opv['lvl'] * 1 + 2 + $jn['weili'] + $opv['pugong']) / 10);
                        value::set_user_value('zd_zd_hp', $zdhp, $userId);
                        value::set_user_value('zd_zd', 3, $userId);
                        value::add_game_user_value('hp', -1 * $zdhp, $userId);
                        skill::add_skill_chat($uv['name'] . '被' . $opv['name'] . '中毒了,减血' . $zdhp . '点', $userId, $oUserId);
                    }
                }
            }
        }
        if ($oPetId && !$opv['is_dead'] && !$uv['is_dead'] && $pet_zd_mb < 1 && !$pet_zd_tz) {
            if ($mode < 4) {
                //野生宠物技能
                $o_pet_zzid = value::get_pet_value($oPetId, 'pet_id');
                $study_lvl = value::getvalue('pet', 'study_lvl', 'id', $o_pet_zzid);
                $can_study_fashu = (int)($ov['lvl'] / $study_lvl + 1);
                $can_study_fashu = $can_study_fashu > 10 ? 10 : $can_study_fashu;
                $can_study_count = $can_study_fashu > 4 ? 4 : $can_study_fashu;
                //读取种族技能
                $o_p_study_skill_str = value::getvalue('pet', 'study_skill', 'id', $o_pet_zzid);
                $o_p_study_skill_arr = explode(',', $o_p_study_skill_str);
                //设置野将技能ID
                //是否有前置技能准备中
                $obj = new game_pet_object($oPetId);
                $result = $obj->aget("pk.skill.*.qianzhi");
                foreach ($result as $k => $v) {
                    $o_qianzhi_skill_str = $k;
                    break;
                }
                if ($o_qianzhi_skill_str) {
                    $o_qianzhi_skill_arr = explode('.', $o_qianzhi_skill_str);
                    $o_skill_id = $o_qianzhi_skill_arr[4];
                }
                //随机技能
                if (!$o_skill_id) {
                    $o_skill_id = $o_p_study_skill_arr[$can_study_fashu - mt_rand(1, $can_study_count)];
                }
            } else {
                //敌方玩家宠物技能
                //下回合技能
                $o_skill_id = value::get_pet_value2('pk.next_skill.id', $oPetId, false);
                //没有下回合技能 使用默认技能
                if (!$o_skill_id) {
                    $o_skill_id = value::get_pet_value2('skill_moren.id', $oPetId, false);
                }
                //没有默认技能 使用随机技能
                if (!$o_skill_id) {
                    $obj = new game_pet_object($oPetId);
                    $result = $obj->aget("skill.*.id");
                    $o_skill_id = $result[array_rand($result, 1)];
                }
            }
            //技能
            if ($o_skill_id) {
                $sql = "SELECT * FROM `skill` WHERE `id`={$o_skill_id} LIMIT 1";
                $rs = sql($sql);
                $jn = $rs->fetch_array(MYSQLI_ASSOC);
                if (mt_rand(1, 100) <= $jn['mingzhong']) {
                    $lianji = 1;
                    $lianji = mt_rand($jn['min_lianji'], $jn['max_lianji']);
                    $jLeiXing = $jn['leixing'];
                    for ($i = 0; $i < $lianji; $i++) {
                        if ($jLeiXing == 1) {
                            $in_map_id = value::get_game_user_value('in_map_id', $userId);
                            $yg_area_id = value::get_map_value($in_map_id, 'area_id');
                            $gongshi_1 = 0;
                            if ($yg_area_id == 89) {
                                $gc_gjgw_fy = value::get_user_value('gc_gjgw_fy', $userId);
                                if ($gc_gjgw_fy > 0) {
                                    $gongshi_1 = (($uv['pufang'] + $uv['lvl'] * 1 + 2 + $zd_fs_fy + $ds_wl_fy) / 100 * $gc_gjgw_fy);
                                }
                            }
                            $gongshi = (($opv['lvl'] * 1 + 2 + $jn['weili'] + $opv['pugong']) - ($uv['pufang'] + $uv['lvl'] * 1 + 2 + $zd_fs_fy + $ds_wl_fy + $gongshi_1));
                            $gongshi = c_uint($gongshi);
                            if ($i == 0) {
                                if ($jn['id'] == 1) {
                                    $skillChat = "{$opv['name']}{$jn['name']}了{$uv['name']}，造成{$gongshi}点伤害！";
                                } else {
                                    $skillChat = "{$opv['name']}施展了{$jn['name']}攻击了{$uv['name']}，造成{$gongshi}点伤害！";
                                }
                            } else {
                                $skillChat = "{$opv['name']}连续{$i}次{$jn['name']}了{$uv['name']}，造成{$gongshi}点伤害！";
                            }
                            skill::add_skill_chat($skillChat, $userId, $oUserId);
                        } else {
                            //特攻
                            $gongshi_1 = 0;
                            $in_map_id = value::get_game_user_value('in_map_id', $userId);
                            $yg_area_id = value::get_map_value($in_map_id, 'area_id');
                            if ($yg_area_id == 89) {
                                $gc_gjgw_fy = value::get_user_value('gc_gjgw_fy', $userId);
                                if ($gc_gjgw_fy > 0) {
                                    $gongshi_1 = (($uv['tefang'] + $uv['lvl'] * 1 + 2 + $zd_fs_fy + $ds_mf_fy) / 100 * $gc_gjgw_fy);
                                }
                            }
                            $gongshi = (($opv['lvl'] * 1 + 2 + $jn['weili'] + $opv['tegong']) - ($uv['tefang'] + $uv['lvl'] * 1 + 2 + $zd_fs_fy + $ds_mf_fy + $gongshi_1));
                            $gongshi = c_uint($gongshi);
                            if ($i == 0) {
                                $skillChat = "{$opv['name']}施展了{$jn['name']}攻击了{$uv['name']}，造成{$gongshi}点伤害！";
                            } else {
                                $skillChat = "{$opv['name']}连续{$i}次施展了{$jn['name']}攻击了{$uv['name']}，造成{$gongshi}点伤害！";
                            }
                            skill::add_skill_chat($skillChat, $userId, $oUserId);
                        }
                         if (mt_rand(1, 100) <= $jn['zhongji']) {
                             $gongshi *= 2;
                         }
                         $tz_gj_jm = value::get_user_value('tz_gj_jm', $userId);
                         if ($tz_gj_jm) {
                             $gongshi1 = $gongshi - ( $gongshi / 100 * $tz_gj_jm);
                             $gongshi = $gongshi1;
                         }
                         $gongshi = c_uint($gongshi + mt_rand(0, 1));
                         $uv['hp'] = user::add_game_user('hp', -1 * $gongshi, $userId);
                         if ($uv['hp'] <= 0) {
                             user::set_game_user('hp', 0, $userId);
                             user::set_game_user('is_dead', 1, $userId);
                         }
                    }
                } else {
                    $skillChat = "{$uv['name']}闪避了{$opv['name']}的{$jn['name']}！";
                    skill::add_skill_chat($skillChat, $userId, $oUserId);
                }
            }
        }
    }
}

//技能类
Class skill
{
    //死亡换将
    static function dead_change_pet($mode, $u_pet_id, $o_pet_id)
    {
        $u_change_ok = false;
        $user_id = value::get_pet_value($u_pet_id, 'master_id');
        $user_name = value::get_game_user_value('name', $user_id);
        $u_hp = value::get_game_user_value('hp', $user_id);
        $o_user_id = value::get_pet_value($o_pet_id, 'master_id');
        $o_user_name = value::get_game_user_value('name', $o_user_id);
        $o_npc_id = value::get_pet_value($o_pet_id, 'npc_id');
        //更换宠物
        $pet_arr = user::get_pet_arr($user_id, 1);
        foreach ($pet_arr as $pet_id) {
            $is_dead = value::get_pet_value($pet_id, 'is_dead');
            if (!$is_dead) {
                value::set_game_user_value('pk.now_pet.id', $pet_id, $user_id);
                //输出提示
                self::add_skill_chat($user_name . "的" . value::get_pet_value($u_pet_id, 'name') . "战死了", $user_id, $o_user_id);
                self::add_skill_chat($user_name . "的" . value::get_pet_value($pet_id, 'name') . "上场", $user_id, $o_user_id);
                $u_change_ok = true;
                break;
            }
        }
        //删除己方宠物PK属性
        $obj = new game_pet_object($u_pet_id);
        $obj->adel("pk.*");
        //删除 逃跑 交换 使用药品操作
        value::set_user_value('pk.next_pet.id', 0, $user_id);
        value::set_user_value('pk.next_item.id', 0, $user_id);
        value::set_user_value('pk.next_taopao', 0, $user_id);
        //玩家战败事件
        if (!$u_change_ok) {
            self::add_skill_chat('', $user_id, $o_user_id, true);
            $is_jingji = user::get("pkjjzt", $user_id) ? true : false;
            if (!$is_jingji) {
                if (user::get_game_user('is_dead', $user_id)) {
                    e89($mode, $o_pet_id, $user_id);
                    if ($mode == 4) {
                        self::add_skill_chat("你打败了{$user_name}。<br>你获得了胜利。", $o_user_id, 0, true);
                        e68($mode, $o_pet_id, $o_npc_id, $o_user_id);
                    }
                } else {
                    $u_change_ok = true;
                }
            } else {
                $sports_id = user::get("pkjjid", $user_id);
                sports::out_of_sports($sports_id, $o_user_id);
            }
        }
        return $u_change_ok;
    }

    //交换宠物
    static function swap_pet($u_pet_id, $next_pet_id, $o_user_id)
    {
        //获取用户资料 宠物状态
        $user_id = value::get_pet_value($u_pet_id, 'master_id');
        $user_name = value::get_game_user_value('name', $user_id);
        $u_pet_zhuangtai = value::get_pet_value($u_pet_id, 'zhuangtai');
        //设置场上宠物 输出提示
        value::set_game_user_value('pk.now_pet.id', $next_pet_id, $user_id);
        self::add_skill_chat($user_name . '交换' . value::get_pet_value($next_pet_id, 'name') . '上场。', $user_id, $o_user_id);
        //设置原宠物PK灵活
        value::set_pet_value2('pk.minjie', 0, $u_pet_id);
        //设置原宠物异常状态
        if ($u_pet_id > 0) {
            if ($u_pet_zhuangtai == 8) {
                //设置剧毒回合清零
                value::set_pet_value2('pk.yczt.jd', 0, $u_pet_id);
            }
            if ($u_pet_zhuangtai == 9 || $u_pet_zhuangtai == 10) {
                //从 束缚 吸血 中恢复
                value::set_pet_value($u_pet_id, 'zhuangtai', 0);
            }
            //前置技能失效 技能效果状态失效 技能加成失效
            $obj = new game_pet_object($u_pet_id);
            $obj->adel("pk.skill.*.qianzhi");
            $obj->adel("pk.jnxgzt.*");
            $obj->adel("pk.jiacheng.*");
        }
    }

    //使用物品
    static function use_item($u_pet_id, $next_item_id, $o_pet_id)
    {
        $next_item_name = value::get_item_value($next_item_id, 'name');
        $user_id = value::get_pet_value($u_pet_id, 'master_id');
        $user_name = value::get_game_user_value('name', $user_id);
        $u_pet_name = value::get_pet_value($u_pet_id, 'name');
        $u_pet_zhuangtai = value::get_pet_value($u_pet_id, 'zhuangtai');
        $u_pet_zhuansheng = value::get_pet_value($u_pet_id, 'zhuansheng');
        //是否NPC宠物
        $o_npcid = value::get_pet_value($o_pet_id, 'npc_id');
        $o_user_id = value::get_pet_value($o_pet_id, 'master_id');
        $o_user_num = value::get_pet_value($o_pet_id, 'master_mode');
        $o_pet_name = value::get_pet_value($o_pet_id, 'name');
        $o_pet_lvl = value::get_pet_value($o_pet_id, 'lvl');
        $o_pet_hp = value::get_pet_value($o_pet_id, 'hp');
        $o_pet_max_hp = value::get_pet_value($o_pet_id, 'max_hp');
        $o_pet_texing = value::get_pet_value($o_pet_id, 'texing');
        $o_pet_zhuangtai = value::get_pet_value($o_pet_id, 'zhuangtai');
        //HP药品
        if ($next_item_id > 1 && $next_item_id < 7) {
            if (item::lose_item($next_item_id, 1, false, $user_id)) {
                $new_hp = 0;
                switch ($next_item_id) {
                    case 2:
                        $new_hp = value::add_pet_value($u_pet_id, 1, 30, false);
                        break;
                    case 3:
                        $new_hp = value::add_pet_value($u_pet_id, 1, 70, false);
                        break;
                    case 4:
                        $new_hp = value::add_pet_value($u_pet_id, 1, 110, false);
                        break;
                    case 5:
                        $new_hp = value::add_pet_value($u_pet_id, 1, 170, false);
                        break;
                    case 6:
                        $new_hp = value::add_pet_value($u_pet_id, 1, 200, false);
                        break;
                    default:
                        break;
                }
                skill::add_skill_chat($u_pet_name . "服下了{$next_item_name},回复了{$new_hp}点生命。", $user_id, $o_user_id);
            } else {
                skill::add_skill_chat($user_name . '身上没有' . value::getvalue('item', 'name', 'id', $next_item_id) . '了。', $user_id, $o_user_id);
            }
            if (!item::get_item($next_item_id, $user_id)) {
                sql("DELETE FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.kjwp.%.id' AND `value`='{$next_item_id}'");
            }
        }
        //PP药品
        if (strstr($next_item_id, ',')) {
            $t_item_id = explode(',', $next_item_id);
            $pp_item_id = $t_item_id[0];
            $pp_skill_id = $t_item_id[1];
            if (item::lose_item($pp_item_id, 1, false, $user_id)) {
                switch ($pp_item_id) {
                    case 47:
                        $pp_item_count = 5;
                        break;
                    case 48:
                        $pp_item_count = 10;
                        break;
                    case 49:
                        $pp_item_count = 20;
                        break;
                    case 50:
                        $pp_item_count = 40;
                        break;
                }
                $max_pp = value::getvalue('skill', 'pp', 'id', $pp_skill_id);
                $zhuansheng = $u_pet_zhuansheng;
                //转生增加可使用PP
                $max_pp *= $zhuansheng > 0 ? 1.5 : 1;
                //吸力天赋 降低可使用PP
                $max_pp *= $o_pet_texing == 10 ? 0.8 : 1;
                //PP取整
                $max_pp = (int)$max_pp;
                $now_pp = value::add_pet_value2('skill.' . $pp_skill_id . '.pp', $pp_item_count, $u_pet_id);
                if ($now_pp > $max_pp) {
                    value::set_pet_value2('skill.' . $pp_skill_id . '.pp', $max_pp, $u_pet_id);
                }
                skill::add_skill_chat($u_pet_name . '的' . value::getvalue('skill', 'name', 'id', $pp_skill_id) . '的PP恢复了。', $user_id, $o_user_id);
            } else {
                skill::add_skill_chat($user_name . '身上没有' . value::getvalue('item', 'name', 'id', $pp_item_id) . '了。', $user_id, $o_user_id);
            }
            if (!item::get_item($pp_item_id, $user_id)) {
                sql("DELETE FROM `game_user_value` WHERE `userid`={$user_id} AND `valuename` LIKE '{$user_id}.kjwp.%.id' AND `value`='{$pp_item_id}'");
            }
        }
        value::set_user_value('pk.next_pet.id', 0, $user_id);
        value::set_user_value('pk.next_taopao', 0, $user_id);
        //设置下回合使用药品
        value::set_user_value('pk.next_item.id', $item_id, $user_id);
        e82();
        return;
    }

    //异常状态出招
    static function yczt_can_use_skill($u_pet_id, $o_pet_id, $skill_id, $u_pet_zhuangtai)
    {
        $user_id = value::get_pet_value($u_pet_id, 'master_id');
        $u_pet_name = value::get_pet_value($u_pet_id, 'name');
        $o_user_id = value::get_pet_value($o_pet_id, 'master_id');
        $can_use_skill = true;
        switch ($u_pet_zhuangtai) {
            case 1:
                if (value::get_pet_value($u_pet_id, 'zhuangtai') == 1) {
                    $can_use_skill = false;
                    self::add_skill_chat($u_pet_name . '被麻痹了,无法出招。', $user_id, $o_user_id);
                }
                break;
            case 4:
                if (value::getvalue('skill', 'shuxing', 'id', $skill_id) != '火') {
                    $can_use_skill = false;
                    self::add_skill_chat($u_pet_name . '被冰冻了,只能使用火属性技能。', $user_id, $o_user_id);
                }
                break;
            case 6:
                if (mt_rand(1, 2) == 1) {
                    $can_use_skill = false;
                    self::add_skill_chat($u_pet_name . '迷惑了,没有出招。', $user_id, $o_user_id);
                }
                break;
            case 7:
                //梦游
                if ($skill_id != 59) {
                    $can_use_skill = false;
                    self::add_skill_chat($u_pet_name . '睡着了,没有出招。', $user_id, $o_user_id);
                }
                break;
        }
        return $can_use_skill;
    }

    //宠物使用装备
    static function pet_use_prop($u_pet_id, $o_pet_id)
    {
        $user_id = value::get_pet_value($u_pet_id, 'master_id');
        $u_pet_name = value::get_pet_value($u_pet_id, 'name');
        $u_pet_zhuangtai = value::get_pet_value($u_pet_id, 'zhuangtai');
        $o_user_id = value::get_pet_value($o_pet_id, 'master_id');
        $o_pet_name = value::get_pet_value($o_pet_id, 'name');
        $o_pet_zhuangtai = value::get_pet_value($o_pet_id, 'zhuangtai');
        $equip_id = pet::get_prop_id($u_pet_id, 3);
        $equip_name = value::get_game_prop_value($equip_id, 'name');
        $equip_zz_id = value::get_game_prop_value($equip_id, 'prop_id');
        switch ($equip_zz_id) {
            case 2:
                if ($o_pet_zhuangtai != 9) {
                    value::set_pet_value($o_pet_id, 'zhuangtai', 9);
                    self::add_skill_chat("{$u_pet_name}祭出{$equip_name},{$o_pet_name}被束缚了。", $user_id, $o_user_id);
                }
                break;
            case 3:
                if ($o_pet_zhuangtai != 9 || $u_pet_zhuangtai != 9) {
                    value::set_pet_value($u_pet_id, 'zhuangtai', 9);
                    value::set_pet_value($o_pet_id, 'zhuangtai', 9);
                    self::add_skill_chat("{$u_pet_name}祭出{$equip_name},{$u_pet_name}被束缚了,{$o_pet_name}被束缚了。", $user_id, $o_user_id);
                }
                break;
            case 4:
                if ($u_pet_zhuangtai) {
                    $zj_ok = true;
                    if ($user_id && $o_user_id && mt_rand(1, 100) < 50) {
                        $zj_ok = false;
                    }
                    if ($zj_ok) {
                        value::set_pet_value($u_pet_id, 'zhuangtai', 0);
                        self::add_skill_chat("{$u_pet_name}的朱睛冰蟾闪闪发光,{$u_pet_name}从异常状态中恢复了。", $user_id, $o_user_id);
                    } else {
                        self::add_skill_chat("{$u_pet_name}的朱睛冰蟾光芒黯淡,{$u_pet_name}无法从异常状态中恢复。", $user_id, $o_user_id);
                    }
                }
                break;
            case 7:
                if ($o_pet_zhuangtai != 10) {
                    value::set_pet_value($o_pet_id, 'zhuangtai', 10);
                    self::add_skill_chat("{$u_pet_name}祭出{$equip_name},{$o_pet_name}被吸血了。", $user_id, $o_user_id);
                }
                break;
            default:
                break;
        }
    }

    //使用技能
    static function use_skill($u_pet_id, $o_pet_id, $skill_id, $need_pp = true)
    {
        if (!$skill_id) {
            return false;
        }
        //用户数据 NPC数据 队伍数据
        $user_id = value::get_pet_value($u_pet_id, 'master_id');
        $u_npc_id = value::get_pet_value($u_pet_id, 'npc_id');
        $u_team_id = value::get_game_user_value('team_id', $user_id);
        $o_user_id = value::get_pet_value($o_pet_id, 'master_id');
        $o_npc_id = value::get_pet_value($o_pet_id, 'npc_id');
        $o_team_id = value::get_game_user_value('team_id', $o_user_id);
        //上回合技能
        $u_old_skill_id = value::get_pet_value2('pk.old_skill.id', $u_pet_id, false);
        $o_old_skill_id = value::get_pet_value2('pk.old_skill.id', $o_pet_id, false);
        //增益状态
        $zyzt_arr = array('hp', 'pg', 'pf', 'tf', 'mj', 'mz', 'tzhh', 'zj');
        //获取攻击宠物六维 属性 忠诚度加成 状态修正
        $sql = "SELECT `pet_id`,`name`,`lvl`,`hp`,`max_hp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`xingge`,`texing`,`zhuangtai`,`zhongcheng`,`master_id`,`master_mode`,`zhuansheng` FROM `game_pet` WHERE `id`={$u_pet_id} LIMIT 1";
        $result = sql($sql);
        list($u_zzid, $u_name, $u_lvl, $u_tq, $u_hp, $u_max_hp, $u_pugong, $u_pufang, $u_tegong, $u_tefang, $u_minjie, $u_xingge, $u_texing, $u_zhuangtai, $u_zhongcheng, $u_master_id, $u_master_mode, $u_zhuansheng) = $result->fetch_row();
        $u_shuxing = value::getvalue('pet', 'shuxing', 'id', $u_zzid);
        $u_zcdjc = $u_lvl * $u_zhongcheng / 200;
        $u_maxhp = pet::get_max_hp($u_pet_id);
        //效果装备
        $u_equip_liuwei_id_arr = pet::get_prop_id($u_pet_id, 2);
        $u_equip_liuwei_id = $u_equip_liuwei_id_arr[array_rand($u_equip_liuwei_id_arr, 1)];
        $u_equip_baowu_id = pet::get_prop_id($u_pet_id, 3);
        $u_equip_baowu_name = value::get_game_prop_value($u_equip_baowu_id, 'name');
        $u_equip_baowu_zz_id = value::get_game_prop_value($u_equip_baowu_id, 'prop_id');
        //己方偷学天赋
        if ($u_texing == 15) {
            $u_texing = value::get_pet_value2('pk.texing', $u_pet_id, false) - 1;
        }
        //异常状态修正
        switch ($u_zhuangtai) {
            case 2:
                //麻痹 灵活降低
                $u_minjie *= 0.5;
                break;
            case 3:
                //烧伤 攻击降低
                $u_pugong *= 0.5;
                break;
            case 6:
                //迷惑 双防降低
                $u_pufang *= 0.5;
                $u_tefang *= 0.5;
                break;
        }
        //异常状态触发
        if ($u_zhuangtai == 5) {
            //混乱 攻击自身
            if (value::getvalue('skill', 'leixing', 'id', $skill_id) < 3) {
                if (mt_rand(1, 2) == 1) {
                    self::add_skill_chat($u_name . '混乱了,攻击自己。', $user_id, $o_user_id);
                    $o_pet_id = $u_pet_id;
                }
            }
        }
        //是否圣凶
        $u_is_shengshou = pet::get_zz($u_pet_id, 'is_shengshou');
        $u_is_xiongshou = pet::get_zz($u_pet_id, 'is_xiongshou');
        //获取敌方用户名
        $o_user_name = value::get_game_user_value('name', $o_user_id);
        //获取被攻击宠物六维 属性 忠诚度加成 状态修正
        $sql = "SELECT `pet_id`,`name`,`lvl`,`hp`,`max_hp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`xingge`,`texing`,`zhuangtai`,`zhongcheng`,`master_id`,`zhuansheng` FROM `game_pet` WHERE `id`={$o_pet_id} LIMIT 1";
        $result = sql($sql);
        list($o_zzid, $o_name, $o_lvl, $o_hp, $o_max_hp, $o_pugong, $o_pufang, $o_tegong, $o_tefang, $o_minjie, $o_xingge, $o_texing, $o_zhuangtai, $o_zhongcheng, $o_master_id, $o_zhuansheng) = $result->fetch_row();
        $o_shuxing = value::getvalue('pet', 'shuxing', 'id', $o_zzid);
        $o_zcdjc = $o_lvl * $o_zhongcheng / 200;
        $o_maxhp = pet::get_max_hp($o_pet_id);
        //效果装备
        $o_equip_liuwei_id_arr = pet::get_prop_id($o_pet_id, 2);
        $o_equip_liuwei_id = $o_equip_liuwei_id_arr[array_rand($o_equip_liuwei_id_arr, 1)];
        $o_equip_liuwei_name = value::get_game_prop_value($o_equip_liuwei_id, 'name');
        $o_equip_liuwei_zz_id = value::get_game_prop_value($o_equip_liuwei_id, 'prop_id');
        $o_equip_baowu_id = pet::get_prop_id($o_pet_id, 3);
        $o_equip_baowu_name = value::get_game_prop_value($o_equip_baowu_id, 'name');
        $o_equip_baowu_zz_id = value::get_game_prop_value($o_equip_baowu_id, 'prop_id');
        //对方偷学天赋
        if ($o_texing == 15) {
            $o_texing = value::get_pet_value2('pk.texing', $o_pet_id, false) - 1;
        }
        //异常状态修正
        switch ($o_zhuangtai) {
            case 2:
                //麻痹 灵活降低
                $o_minjie *= 0.5;
                break;
            case 3:
                //烧伤 攻击降低
                $o_pugong *= 0.5;
                break;
            case 6:
                //迷惑 双防降低
                $o_pufang *= 0.5;
                $o_tefang *= 0.5;
                break;
        }
        //获取技能 数据 属性
        $sql = "SELECT `name`,`shuxing`,`event`,`fangshi`,`leixing`,`mingzhong`,`weili`,`zhongji`,`yczt`,`ycztmz`,`min_lianji`,`max_lianji` FROM `skill` WHERE `id`={$skill_id} LIMIT 1";
        $result = sql($sql);
        list($j_name, $j_shuxing, $j_event, $j_fangshi, $j_leixing, $j_mingzhong, $j_weili, $j_zhongji, $j_yczt, $j_ycztmz, $j_minlianji, $j_max_lianji) = $result->fetch_row();
        //封技效果触发
        if (value::get_pet_value2('pk.jiacheng.jinfa.' . $skill_id, $u_pet_id, false)) {
            value::add_pet_value2('pk.jiacheng.jinfa.' . $skill_id, -1, $u_pet_id);
            self::add_skill_chat($u_name . '的[' . $j_name . ']被禁止了。', $user_id, $o_user_id);
            return true;
        }
        //需停止一回合
        if (value::get_pet_value2('pk.jnxgzt.tzhh', $u_pet_id, false)) {
            value::add_pet_value2('pk.jnxgzt.tzhh', -1, $u_pet_id);
            self::add_skill_chat($u_name . '停止了一回合。', $user_id, $o_user_id);
            return true;
        }
        //判断是否还有PP
        if ($u_master_id && $need_pp && $u_master_mode != 8) {
            $pp = value::get_pet_value2('skill.' . $skill_id . '.pp', $u_pet_id, false);
            $pp_ok = true;
            if ($pp < 1) {
                if ($j_fangshi != 2) {
                    $pp_ok = false;
                } else {
                    if (!value::get_pet_value2('pk.skill.' . $skill_id . '.qianzhi', $u_pet_id, false)) {
                        $pp_ok = false;
                    }
                }
                if (!$pp_ok) {
                    self::add_skill_chat($u_name . '没有足够的PP使用[' . $j_name . "]了。", $user_id, $o_user_id);
                    return true;
                }
            } else {
                if ($j_fangshi != 2) {
                    $pp_ok = false;
                } else {
                    if (!value::get_pet_value2('pk.skill.' . $skill_id . '.qianzhi', $u_pet_id, false)) {
                        $pp_ok = false;
                    }
                }
                if (!$pp_ok) {
                    value::add_pet_value2('skill.' . $skill_id . '.pp', -1, $u_pet_id);
                }
            }
        }
        //记录己方上回合技能
        value::set_pet_value2('pk.old_skill.id', $skill_id, $u_pet_id);
        //己方技能命中
        $pk_j_mingzhong = $j_mingzhong;
        //己方异常状态 与 异常状态命中
        $j_u_yczt = 0;
        $j_u_ycztmz = 0;
        //技能 伤害 符号
        $u_shanghai = 0;
        $u_shanghaifuhao = -1;
        $o_shanghai = 0;
        $o_fj_shanghai = 0;
        $o_shanghaifuhao = -1;
        //技能效果 命中/重击修正
        $pk_j_mingzhong += self::get_pet_jiacheng($u_pet_id, 'mingzhong');
        $j_zhongji += self::get_pet_jiacheng($u_pet_id, 'zhongji');
        //获取现在时间
        $use_hour = date('H');
        //判断前置攻击是否蓄力中
        if ($j_fangshi == 2) {
            if (value::get_pet_value2('pk.skill.' . $skill_id . '.qianzhi', $u_pet_id, false)) {
                value::set_pet_value2('pk.skill.' . $skill_id . '.qianzhi', '0', $u_pet_id);
            } else {
                value::set_pet_value2('pk.skill.' . $skill_id . '.qianzhi', '1', $u_pet_id);
                self::add_skill_chat($u_name . '使出前置攻击技能[' . $j_name . "]。", $user_id, $o_user_id);
                return true;
            }
        } else {
            $obj = new game_pet_object($u_pet_id);
            $obj->adel("*.qianzhi");
        }
        //宠物天赋 命中 修正
        switch ($u_texing) {
            //独有
            case 0:
                //命中 增加技能命中率
                $pk_j_mingzhong += ($j_mingzhong * 0.2);
                break;
            case 48:
                //暴走 濒死时技能必然命中
                if ($u_hp < ($u_max_hp / 6)) {
                    $pk_j_mingzhong = 200;
                }
                break;
            case 52:
                //狂战:开战后减少命中
                $pk_j_mingzhong -= 10;
                break;
        }
        //幻步 技能攻击必中
        if (value::get_pet_value2('pk.jiacheng.huanhua', $o_pet_id, false) && $j_leixing == 2) {
            $pk_j_mingzhong = 100;
        }
        //出窍 普通攻击必中
        if (value::get_pet_value2('pk.jiacheng.chuqiao', $o_pet_id, false) && $j_leixing == 1) {
            $pk_j_mingzhong = 100;
        }
        //装备效果触发
        //天神眼
        if ($u_equip_baowu_zz_id == 8) {
            $pk_j_mingzhong += ($j_mingzhong * 0.2);
        }
        //游蛇蜕
        if ($o_equip_baowu_zz_id == 9) {
            $pk_j_mingzhong -= ($j_mingzhong * 0.2);
        }
        //玩家PK因素
        if ($user_id && $o_user_id) {
            //命中降低
            //防御技能
            if ($j_leixing == 3) {
                $pk_j_mingzhong *= 0.6;
            }
        }
        //判断技能是否命中
        $pk_j_mingzhong = (int)$pk_j_mingzhong;
        $mingzhong = mt_rand(1, 100) <= $pk_j_mingzhong ? true : false;
        if (!$mingzhong) {
            if ($j_leixing == 1) {
                self::add_skill_chat($o_name . '闪避了' . $u_name . '的' . $j_name, $user_id, $o_user_id);
            } else {
                self::add_skill_chat($o_name . '闪避了' . $u_name . '的' . $j_name, $user_id, $o_user_id);
            }
            return true;
        }
        // 宠物天赋 六维/威力 修正
        //己方
        switch ($u_texing) {
            //独有
            case 21:
                //金攻 金系濒死威力上升
                if ($u_hp < ($u_max_hp / 6) && $j_shuxing == '金') {
                    $j_weili *= 1.5;
                }
                break;
            case 22:
                //木攻 木系濒死威力上升
                if ($u_hp < ($u_max_hp / 6) && $j_shuxing == '木') {
                    $j_weili *= 1.5;
                }
                break;
            case 24:
                //水攻 木系濒死威力上升
                if ($u_hp < ($u_max_hp / 6) && $j_shuxing == '水') {
                    $j_weili *= 1.5;
                }
                break;
            case 27:
                //火攻 火系濒死威力上升
                if ($u_hp < ($u_max_hp / 6) && $j_shuxing == '火') {
                    $j_weili *= 1.5;
                }
                break;
            case 31:
                //土攻 土系濒死威力上升
                if ($u_hp < ($u_max_hp / 6) && $j_shuxing == '土') {
                    $j_weili *= 1.5;
                }
                break;
            case 37:
                //日之子 日系濒死威力上升
                if ($u_hp < ($u_max_hp / 6) && $j_shuxing == '日') {
                    $j_weili *= 1.5;
                }
                break;
            case 38:
                //精攻 正午日系威力上升
                if ($use_hour > 10 && $use_hour < 14 && $j_shuxing == '日') {
                    $j_weili *= 1.2;
                }
                break;
            case 40:
                //阴攻 月系濒死威力上升
                if ($u_hp < ($u_max_hp / 6) && $j_shuxing == '月') {
                    $j_weili *= 1.5;
                }
                break;
            case 47:
                //仙攻 仙系濒死威力上升
                if ($u_hp < ($u_max_hp / 6) && $j_shuxing == '仙') {
                    $j_weili *= 1.5;
                }
                break;
            case 49:
                //致命 濒死时技能威力上升
                if ($u_hp < ($u_max_hp / 6)) {
                    $j_weili *= 1.5;
                }
                break;
            //共有
            case 1:
                //攻穿 降低对手攻击
                $o_pugong *= 0.8;
                break;
            case 2:
                //防穿 降低对手防御
                $o_pufang *= 0.8;
                break;
            case 3:
                //暴攻 异常状态时 攻击魔攻上升
                if ($u_zhuangtai) {
                    $u_pugong *= 1.2;
                    $u_tegong *= 1.2;
                }
                break;
            case 5:
                //暴防 异常状态时 防御魔防上升
                if ($u_zhuangtai) {
                    $u_pufang *= 1.2;
                    $u_tefang *= 1.2;
                }
                break;
            case 46:
                //法抗:开战后魔防自动上升
                $u_tefang *= 1.2;
                break;
            case 51:
                //物穿:开战后攻击自动上升
                $u_pugong *= 1.2;
                break;
            case 52:
                //狂战:开战后攻击自动上升
                $u_pugong *= 1.3;
                break;
        }
        //敌方
        switch ($o_texing) {
            //独有
            case 23:
                //木生 受到木系攻击将伤害转化为增加HP
                if ($j_shuxing == '木') {
                    $o_shanghaifuhao = 1;
                }
                break;
            case 26:
                //水生 受到水系攻击将伤害转化为增加HP
                if ($j_shuxing == '水') {
                    $o_shanghaifuhao = 1;
                }
                break;
            case 28:
                //火生 受到火系攻击将伤害转化为增加HP
                if ($j_shuxing == '火') {
                    $o_shanghaifuhao = 1;
                }
                break;
            case 35:
                //雷生 受到雷系攻击将伤害转化为增加HP
                if ($j_shuxing == '雷') {
                    $o_shanghaifuhao = 1;
                }
                break;
            //共有
            case 1:
                //攻穿 降低对手攻击
                $u_pugong *= 0.8;
                break;
            case 2:
                //防穿 降低对手防御
                $u_pufang *= 0.8;
                break;
            case 3:
                //暴攻 异常状态时 攻击魔攻上升
                if ($o_zhuangtai) {
                    $o_pugong *= 1.2;
                    $o_tegong *= 1.2;
                }
                break;
            case 5:
                //暴防 异常状态时 防御魔防上升
                if ($o_zhuangtai) {
                    $o_pufang *= 1.2;
                    $o_tefang *= 1.2;
                }
                break;
            case 46:
                //法抗:开战后魔防自动上升
                $o_tefang *= 1.2;
                break;
            case 51:
                //物穿:开战后攻击自动上升
                $o_pugong *= 1.2;
                break;
            case 52:
                //狂战:开战后攻击自动上升
                $o_pugong *= 1.3;
                break;
        }
        //己方
        //装备 六维加成修正
        $u_pugong += pet::get_liuwei_jiacheng($u_pet_id, 'pugong');
        $u_pufang += pet::get_liuwei_jiacheng($u_pet_id, 'pufang');
        $u_tegong += pet::get_liuwei_jiacheng($u_pet_id, 'tegong');
        $u_tefang += pet::get_liuwei_jiacheng($u_pet_id, 'tefang');
        $u_minjie += pet::get_liuwei_jiacheng($u_pet_id, 'minjie');
        //技能效果 六维加成修正
        $u_pugong += (self::get_pet_jiacheng($u_pet_id, 'pugong') + 1);
        $u_pufang += (self::get_pet_jiacheng($u_pet_id, 'pufang') + 1);
        $u_tegong += (self::get_pet_jiacheng($u_pet_id, 'tegong') + 1);
        $u_tefang += (self::get_pet_jiacheng($u_pet_id, 'tefang') + 1);
        $u_minjie += (self::get_pet_jiacheng($u_pet_id, 'minjie') + 1);
        //敌方
        //装备 六维加成修正
        $o_pugong += pet::get_liuwei_jiacheng($o_pet_id, 'pugong');
        $o_pufang += pet::get_liuwei_jiacheng($o_pet_id, 'pufang');
        $o_tegong += pet::get_liuwei_jiacheng($o_pet_id, 'tegong');
        $o_tefang += pet::get_liuwei_jiacheng($o_pet_id, 'tefang');
        $o_minjie += pet::get_liuwei_jiacheng($o_pet_id, 'minjie');
        //技能效果 六维加成修正
        $o_pugong += (self::get_pet_jiacheng($o_pet_id, 'pugong') + 1);
        $o_pufang += (self::get_pet_jiacheng($o_pet_id, 'pufang') + 1);
        $o_tegong += (self::get_pet_jiacheng($o_pet_id, 'tegong') + 1);
        $o_tefang += (self::get_pet_jiacheng($o_pet_id, 'tefang') + 1);
        $o_minjie += (self::get_pet_jiacheng($o_pet_id, 'minjie') + 1);
        //停止回合数
        $u_s_add_tzhh = 0;
        $o_s_add_tzhh = 0;
        //获取连击次数
        $lianji = mt_rand($j_minlianji, $j_max_lianji);
        //闪躲天赋 连击最多一次
        if ($o_texing == 34) {
            $lianji = 1;
        }
        //攻击技能
        if ($j_leixing < 3) {
            //开始攻击
            for ($i = 0; $i < $lianji; $i++) {
                //技能威力>0
                if ($j_weili > 0) {
                    //判断攻击类型 属性克制 重击修正
                    switch ($j_leixing) {
                        case 1:
                            $o_shanghai = ($u_lvl * 1 + 2) + $j_weili + $u_pugong - $o_pufang - ($o_lvl * 1 + 2);
                            break;
                        case 2:
                            $o_shanghai = ($u_lvl * 1 + 2) + $j_weili + $u_tegong - $o_tefang - ($o_lvl * 1 + 2);
                            break;
                    }
                    $wj_bj = value::get_user_value('wj_bj', $user_id);
                    $wj_sj_bj = mt_rand(1, 100) <= $wj_bj ? 2 : 1;
                    //暴击
                    if ($wj_sj_bj > 1) {
                        $o_shanghai = $wj_sj_bj * $o_shanghai;
                    }
                    //暴击修正
                    $zhongji = mt_rand(1, 100) <= $j_zhongji ? 2 : 1;
                    //抗暴天赋
                    if ($zhongji > 1 && $o_texing == 13) {
                        $zhongji = 1;
                    }
                    //克制倍数
                    $kzbs = self::get_shuxing_xz($j_shuxing, $o_pet_id);
                    //伤害加成
                    $o_shanghai = (int)($o_shanghai * $zhongji * $kzbs);
                    //低血量加成
                    $u_hp_sybl = $u_hp / $u_maxhp;
                    if ($u_hp_sybl < 0.2) {
                        self::add_skill_chat($u_name . '开始暴怒了,准备拼死一搏', $user_id, $o_user_id);
                    }
                    $o_shanghai = (int)($o_shanghai * (2 - $u_hp_sybl));
                    //低等级修正
                    if ($o_lvl < 5 || $u_lvl < 5) {
                        $o_shanghai = (int)($o_shanghai * 0.5);
                    }
                } else {
                    //零威力
                    $o_shanghai = 0;
                }
                //战斗信息
                if ($j_leixing < 3) {
                    //攻击
                    if ($j_leixing == 1) {
                        //幻步
                        if (value::get_pet_value2('pk.jiacheng.huanhua', $u_pet_id, false)) {
                            $j_u_yczt = 0;
                            $u_shanghai = 0;
                        }
                        if (value::get_pet_value2('pk.jiacheng.huanhua', $o_pet_id, false)) {
                            $j_yczt = 0;
                            $o_shanghai = 0;
                            $o_fj_shanghai = 0;
                        }
                    }
                    //魔攻
                    if ($j_leixing == 2) {
                        //出窍
                        if (value::get_pet_value2('pk.jiacheng.chuqiao', $u_pet_id, false)) {
                            $j_u_yczt = 0;
                            $u_shanghai = 0;
                        }
                        if (value::get_pet_value2('pk.jiacheng.chuqiao', $o_pet_id, false)) {
                            $j_yczt = 0;
                            $o_shanghai = 0;
                            $o_fj_shanghai = 0;
                        }
                    }
                    //敌方增加 减少血量
                    if ($o_shanghaifuhao < 0) {
                        $o_chat_hp = '减少';
                        $o_fj_chat_hp = '伤害';
                    } else {
                        $o_chat_hp = '恢复';
                        $o_fj_chat_hp = '回复';
                    }
                    if ($o_shanghai < 1) {
                        $o_shanghai = 0;
                    }
                    if ($skill_id == 1) {
                        $skill_chat = $u_name . $j_name . "了" . $o_name . "，血" . $o_chat_hp . "了" . (int)$o_shanghai . "点";
                    } else {
                        $skill_chat = $u_name . '施展了' . $j_name . "命中了" . $o_name . "，血" . $o_chat_hp . "了" . (int)$o_shanghai . "点";
                    }
                    if ($o_fj_shanghai) {
                        $skill_chat .= ',附加' . $o_fj_chat_hp . (int)$o_fj_shanghai . '点';
                        value::add_pet_value($o_pet_id, 1, $o_shanghaifuhao * $o_fj_shanghai, false);
                    }
                    //己方增加 减少血量
                    if ($u_shanghai) {
                        if ($u_shanghaifuhao < 0) {
                            $u_chat_hp = '减少';
                        } else {
                            $u_chat_hp = '恢复';
                        }
                        if ($u_shanghai < 1) {
                            $u_shanghai = 0;
                        }
                        $skill_chat .= "," . $u_name . "的血量" . $u_chat_hp . "了" . (int)$u_shanghai . "点";
                        value::add_pet_value($u_pet_id, 1, $u_shanghaifuhao * $u_shanghai, false);
                    }
                    $skill_chat .= "。";
                    self::add_skill_chat($skill_chat, $user_id, $o_user_id);
                }
                $o_hp = (int)value::add_pet_value($o_pet_id, 1, $o_shanghaifuhao * (int)$o_shanghai, false);
            }
        }
        // 防御/其它技能 施法成功
        $sfcg = true;
        //己方属性加成 六维 命中 重击 加成回合数
        $u_s_add_hp = 0;
        $u_s_add_pg = 0;
        $u_s_add_pf = 0;
        $u_s_add_tg = 0;
        $u_s_add_tf = 0;
        $u_s_add_mj = 0;
        $u_s_add_mz = 0;
        $u_s_add_zj = 0;
        $u_s_add_hh = 0;
        //对方属性加成 六维 命中 重击 加成回合数
        $o_s_add_hp = 0;
        $o_s_add_pg = 0;
        $o_s_add_pf = 0;
        $o_s_add_tg = 0;
        $o_s_add_tf = 0;
        $o_s_add_mj = 0;
        $o_s_add_mz = 0;
        $o_s_add_zj = 0;
        $o_s_add_hh = 0;
        //己方加成
        //可否加成
        $u_can_jc = true;
        if ($u_s_add_hp || $u_s_add_hh || $u_s_add_tzhh) {
            //鬼护符
            if ($u_equip_baowu_zz_id == 6 && value::get_game_prop_value($u_equip_baowu_id, 'naijiu')) {
                if (mt_rand(1, 10) == 1) {
                    $u_can_jc = false;
                    value::add_game_prop_value($u_equip_baowu_id, 'naijiu', -1);
                }
            }
        }
        if ($u_can_jc) {
            //HP
            if ($u_s_add_hp) {
                value::add_pet_value($u_pet_id, 1, $u_s_add_hp, false);
                $sfmiaoshu = $u_name . ($u_s_add_hp > 0 ? '回复' : '减少') . '了' . abs((int)$u_s_add_hp) . '点血量。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            //攻击 防御 魔攻 魔防 灵活 命中 重击
            if ($u_s_add_pg) {
                value::set_pet_value2('pk.jiacheng.pugong.' . $u_s_add_pg, $u_s_add_hh++, $u_pet_id);
                $sfmiaoshu = $u_name . '的攻击' . ($u_s_add_pg > 0 ? '上升' : '降低') . '了' . abs($u_s_add_pg) . '%,持续' . ($u_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($u_s_add_pf) {
                value::set_pet_value2('pk.jiacheng.pufang.' . $u_s_add_pf, $u_s_add_hh++, $u_pet_id);
                $sfmiaoshu = $u_name . '的防御' . ($u_s_add_pf > 0 ? '上升' : '降低') . '了' . abs($u_s_add_pf) . '%,持续' . ($u_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($u_s_add_tg) {
                value::set_pet_value2('pk.jiacheng.tegong.' . $u_s_add_tg, $u_s_add_hh++, $u_pet_id);
                $sfmiaoshu = $u_name . '的魔攻' . ($u_s_add_tg > 0 ? '上升' : '降低') . '了' . abs($u_s_add_tg) . '%,持续' . ($u_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($u_s_add_tf) {
                value::set_pet_value2('pk.jiacheng.tefang.' . $u_s_add_tf, $u_s_add_hh++, $u_pet_id);
                $sfmiaoshu = $u_name . '的魔防' . ($u_s_add_tf > 0 ? '上升' : '降低') . '了' . abs($u_s_add_tf) . '%,持续' . ($u_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($u_s_add_mj) {
                value::set_pet_value2('pk.jiacheng.minjie.' . $u_s_add_mj, $u_s_add_hh++, $u_pet_id);
                $sfmiaoshu = $u_name . '的灵活' . ($u_s_add_mj > 0 ? '上升' : '降低') . '了' . abs($u_s_add_mj) . '%,持续' . ($u_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($u_s_add_mz) {
                value::set_pet_value2('pk.jiacheng.mingzhong.' . $u_s_add_mz, $u_s_add_hh++, $u_pet_id);
                $sfmiaoshu = $u_name . '的命中' . ($u_s_add_mz > 0 ? '上升' : '降低') . '了' . abs($u_s_add_mz) . '%,持续' . ($u_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($u_s_add_zj) {
                value::set_pet_value2('pk.jiacheng.zhongji.' . $u_s_add_zj, $u_s_add_hh++, $u_pet_id);
                $sfmiaoshu = $u_name . '的重击' . ($u_s_add_zj > 0 ? '上升' : '降低') . '了' . abs($u_s_add_zj) . '%,持续' . ($u_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            //停止下回合
            if ($u_s_add_tzhh) {
                value::set_pet_value2('pk.jnxgzt.tzhh', $u_s_add_tzhh, $u_pet_id);
                $sfmiaoshu = $u_name . '需停止' . $u_s_add_tzhh . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
        } else {
            self::add_skill_chat($u_name . '身上的' . $u_equip_baowu_name . '发出强烈的光芒,' . $u_name . '的' . $j_name . '被破法了。', $user_id, $o_user_id);
        }
        //敌方加成
        //可否加成
        $o_can_jc = true;
        if ($o_s_add_hp || $o_s_add_hh || $o_s_add_tzhh) {
            //辟邪珠 鬼护符
            if (($o_equip_baowu_zz_id == 5 || $o_equip_baowu_zz_id == 6) && value::get_game_prop_value($o_equip_baowu_id, 'naijiu')) {
                if (mt_rand(1, 10) == 1) {
                    $o_can_jc = false;
                    value::add_game_prop_value($o_equip_baowu_id, 'naijiu', -1);
                }
            }
        }
        if ($o_can_jc) {
            //HP
            if ($o_s_add_hp) {
                value::add_pet_value($o_pet_id, 1, $o_s_add_hp, false);
                $sfmiaoshu = $o_name . ($o_s_add_hp > 0 ? '回复' : '减少') . '了' . abs((int)$o_s_add_hp) . '点血量。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            //攻击 防御 魔攻 魔防 灵活 命中 重击
            if ($o_s_add_pg) {
                value::set_pet_value2('pk.jiacheng.pugong.' . $o_s_add_pg, $o_s_add_hh++, $o_pet_id);
                $sfmiaoshu = $o_name . '的攻击' . ($o_s_add_pg > 0 ? '上升' : '降低') . '了' . abs($o_s_add_pg) . '%,持续' . ($o_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($o_s_add_pf) {
                value::set_pet_value2('pk.jiacheng.pufang.' . $o_s_add_pf, $o_s_add_hh++, $o_pet_id);
                $sfmiaoshu = $o_name . '的防御' . ($o_s_add_pf > 0 ? '上升' : '降低') . '了' . abs($o_s_add_pf) . '%,持续' . ($o_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($o_s_add_tg) {
                value::set_pet_value2('pk.jiacheng.tegong.' . $o_s_add_tg, $o_s_add_hh++, $o_pet_id);
                $sfmiaoshu = $o_name . '的魔攻' . ($o_s_add_tg > 0 ? '上升' : '降低') . '了' . abs($o_s_add_tg) . '%,持续' . ($o_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($o_s_add_tf) {
                value::set_pet_value2('pk.jiacheng.tefang.' . $o_s_add_tf, $o_s_add_hh++, $o_pet_id);
                $sfmiaoshu = $o_name . '的魔防' . ($o_s_add_tf > 0 ? '上升' : '降低') . '了' . abs($o_s_add_tf) . '%,持续' . ($o_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($o_s_add_mj) {
                value::set_pet_value2('pk.jiacheng.minjie.' . $o_s_add_mj, $o_s_add_hh++, $o_pet_id);
                $sfmiaoshu = $o_name . '的灵活' . ($o_s_add_mj > 0 ? '上升' : '降低') . '了' . abs($o_s_add_mj) . '%,持续' . ($o_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($o_s_add_mz) {
                value::set_pet_value2('pk.jiacheng.mingzhong.' . $o_s_add_mz, $o_s_add_hh++, $o_pet_id);
                $sfmiaoshu = $o_name . '的命中' . ($o_s_add_mz > 0 ? '上升' : '降低') . '了' . abs($o_s_add_mz) . '%,持续' . ($o_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            if ($o_s_add_zj) {
                value::set_pet_value2('pk.jiacheng.zhongji.' . $o_s_add_zj, $o_s_add_hh++, $o_pet_id);
                $sfmiaoshu = $o_name . '的重击' . ($o_s_add_zj > 0 ? '上升' : '降低') . '了' . abs($o_s_add_zj) . '%,持续' . ($o_s_add_hh - 1) . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
            //停止下回合
            if ($o_s_add_tzhh) {
                value::set_pet_value2('pk.jnxgzt.tzhh', $o_s_add_tzhh, $o_pet_id);
                $sfmiaoshu = $o_name . '需停止' . $o_s_add_tzhh . '回合。';
                self::add_skill_chat($sfmiaoshu, $user_id, $o_user_id);
            }
        } else {
            self::add_skill_chat($o_name . '身上的' . $o_equip_baowu_name . '发出强烈的光芒,' . $u_name . '的' . $j_name . '被破法了。', $user_id, $o_user_id);
        }
        //己方异常状态
        if ($j_u_yczt && $u_zhuangtai != $j_u_yczt) {
            //幸运 异常状态命中率提升
            if ($u_texing == 45) {
                $j_u_ycztmz *= 1.5;
            }
            //辟邪珠 鬼护符
            if ($u_equip_baowu_zz_id == 5 || $u_equip_baowu_zz_id == 6) {
                $j_u_ycztmz *= 0.5;
                self::add_skill_chat($u_equip_baowu_name . "的灵气环绕在" . $u_name . "周身", $user_id, $o_user_id);
            }
            if (mt_rand(1, 100) <= $j_u_ycztmz) {
                $can_set_yczt = true;
                //异常处理 天赋效果
                switch ($j_u_yczt) {
                    case 1:
                        //免疫天赋 无法中毒
                        if ($u_texing == 19) {
                            $can_set_yczt = false;
                        }
                        break;
                    case 2:
                        //免麻天赋 无法麻痹
                        if ($u_texing != 14) {
                            value::add_pet_value2('pk.yczt.mb', mt_rand(2, 5), $u_pet_id);
                        } else {
                            $can_set_yczt = false;
                        }
                        break;
                    case 3:
                        //水防天赋 无法烧伤
                        if ($u_texing == 25) {
                            $can_set_yczt = false;
                        }
                        break;
                    case 4:
                        //防冻天赋 无法冰冻
                        if ($u_texing == 29) {
                            $can_set_yczt = false;
                        }
                        break;
                    case 5:
                        //坚定 无法混乱
                        if ($u_texing != 44) {
                            value::add_pet_value2('pk.yczt.hl', mt_rand(2, 5), $u_pet_id);
                        } else {
                            $can_set_yczt = false;
                        }
                        break;
                    case 6:
                        //抗惑天赋 无法迷惑
                        if ($u_texing == 16) {
                            $can_set_yczt = false;
                        }
                        break;
                    case 7:
                        //抗眠天赋 无法睡眠
                        if ($u_texing != 18) {
                            value::add_pet_value2('pk.yczt.sm', mt_rand(2, 7), $u_pet_id);
                        } else {
                            $can_set_yczt = false;
                        }
                        break;
                }
                $u_yczt_str = pet::get_zhuangtai($j_u_yczt);
                if ($can_set_yczt) {
                    value::set_pet_value($u_pet_id, 'zhuangtai', $j_u_yczt);
                    if ($j_u_yczt == 4 || $j_u_yczt == 9 || $j_u_yczt == 10) {
                        $u_yczt_str = '被' . $u_yczt_str;
                    }
                    if ($j_u_yczt == 8) {
                        $u_yczt_str = '中' . $u_yczt_str;
                    }
                    self::add_skill_chat($u_name . $u_yczt_str . "了。", $user_id, $o_user_id);
                } else {
                    self::add_skill_chat($u_name . "无法被{$u_yczt_str}。", $user_id, $o_user_id);
                }
            }
        }
        //敌方异常状态
        if ($j_yczt && $o_zhuangtai != $j_yczt) {
            //辟邪珠 鬼护符
            if ($o_equip_baowu_zz_id == 5 || $o_equip_baowu_zz_id == 6) {
                $j_ycztmz *= 0.5;
                self::add_skill_chat($o_equip_baowu_name . "的灵气环绕在" . $o_name . "周身。", $user_id, $o_user_id);
            }
            if (mt_rand(1, 100) <= $j_ycztmz) {
                $can_set_yczt = true;
                $yczt_str = pet::get_zhuangtai($j_yczt);
                if ($can_set_yczt) {
                    value::set_pet_value($o_pet_id, 'zhuangtai', $j_yczt);
                    if ($j_yczt == 4 || $j_yczt == 9 || $j_yczt == 10) {
                        $yczt_str = '被' . $yczt_str;
                    }
                    if ($j_yczt == 8) {
                        $yczt_str = '中' . $yczt_str;
                    }
                    self::add_skill_chat($o_name . $yczt_str . "了。", $user_id, $o_user_id);
                } else {
                    self::add_skill_chat($o_name . "无法被{$yczt_str}。", $user_id, $o_user_id);
                }
            }
        }
        //处理宠物死亡
        if ($o_hp <= 0) {
            return false;
        }
        return true;
    }

//属性克制修正
    static function get_shuxing_xz($skill_shuxing, $pet_id)
    {
        $kzbs = 1;
        $pk_shuxing = pet::get_shuxing_str(value::get_pet_value2('pk.shuxing', $pet_id, false));
        if (!$pk_shuxing) {
            $pet_zzid = value::get_pet_value($pet_id, 'pet_id');
            $pk_shuxing = value::getvalue('pet', 'shuxing', 'id', $pet_zzid);
        }
        switch ($skill_shuxing) {
            case '金':
                //克
                if (strstr($pk_shuxing, '木')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '土')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '动')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '无')) {
                    $kzbs *= 2;
                }
                //生
                if (strstr($pk_shuxing, '水')) {
                    $kzbs *= 0;
                }
                if (strstr($pk_shuxing, '风')) {
                    $kzbs *= 0;
                }
                break;
            case '木':
                //克
                if (strstr($pk_shuxing, '水')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '土')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '日')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '动')) {
                    $kzbs *= 2;
                }
                //生
                if (strstr($pk_shuxing, '火')) {
                    $kzbs *= 0;
                }
                break;
            case '水':
                //克
                if (strstr($pk_shuxing, '火')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '妖')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '雷')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '动')) {
                    $kzbs *= 2;
                }
                //生
                if (strstr($pk_shuxing, '木')) {
                    $kzbs *= 0;
                }
                break;
            case '火':
                //克
                if (strstr($pk_shuxing, '金')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '木')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '人')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '妖')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '无')) {
                    $kzbs *= 2;
                }
                //生
                if (strstr($pk_shuxing, '土')) {
                    $kzbs *= 0;
                }
                if (strstr($pk_shuxing, '雷')) {
                    $kzbs *= 0;
                }
                break;
            case '土':
                //克
                if (strstr($pk_shuxing, '水')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '火')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '雷')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '无')) {
                    $kzbs *= 2;
                }
                //生
                if (strstr($pk_shuxing, '金')) {
                    $kzbs *= 0;
                }
                break;
            case '人':
                //克
                if (strstr($pk_shuxing, '水')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '火')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '妖')) {
                    $kzbs *= 2;
                }
                break;
            case '仙':
                //克
                if (strstr($pk_shuxing, '人')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '风')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '月')) {
                    $kzbs *= 2;
                }
                break;
            case '妖':
                //克
                if (strstr($pk_shuxing, '仙')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '日')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '动')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '无')) {
                    $kzbs *= 2;
                }
                //生
                if (strstr($pk_shuxing, '水')) {
                    $kzbs *= 0;
                }
                if (strstr($pk_shuxing, '火')) {
                    $kzbs *= 0;
                }
                break;
            case '风':
                //克
                if (strstr($pk_shuxing, '日')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '月')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '动')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '无')) {
                    $kzbs *= 2;
                }
                //生
                if (strstr($pk_shuxing, '风')) {
                    $kzbs *= 0;
                }
                break;
            case '雷':
                //克
                if (strstr($pk_shuxing, '金')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '木')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '人')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '仙')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '妖')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '月')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '动')) {
                    $kzbs *= 2;
                }
                //生
                if (strstr($pk_shuxing, '水')) {
                    $kzbs *= 0;
                }
                if (strstr($pk_shuxing, '火')) {
                    $kzbs *= 0;
                }
                if (strstr($pk_shuxing, '风')) {
                    $kzbs *= 0;
                }
                break;
            case '日':
                //克
                if (strstr($pk_shuxing, '妖')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '风')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '雷')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '日')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '月')) {
                    $kzbs *= 2;
                }
                break;
            case '月':
                //克
                if (strstr($pk_shuxing, '仙')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '风')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '日')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '月')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '无')) {
                    $kzbs *= 2;
                }
                break;
            case '动':
                if (strstr($pk_shuxing, '土')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '无')) {
                    $kzbs *= 2;
                }
                break;
            case '无':
                if (strstr($pk_shuxing, '金')) {
                    $kzbs *= 2;
                }
                if (strstr($pk_shuxing, '动')) {
                    $kzbs *= 2;
                }
                break;
        }
        if ($kzbs >= 2) {
            $kzbs = 2;
            //物抗天赋
            if (value::get_pet_value($pet_id, 'texing') == 12) {
                $kzbs = 1.5;
            }
        }
        return $kzbs;
    }

// 获取技能属性加成
    static function get_pet_jiacheng($pet_id, $value_name)
    {
        $value = 0;
        $obj = new game_pet_object($pet_id);
        $result = $obj->aget("pk.jiacheng.{$value_name}.*");
        foreach ($result as $k => $v) {
            $t_arr = explode('.', $k);
            $value += $t_arr[3];
        }
        return $value;
    }

    //使用技能消息
    static function add_skill_chat($chat, $uid = 0, $oid = 0, $is_insert = false, $is_top = false)
    {
        static $chats = "";
        if ($chat) {
            $chats .= $chat . "<br>";
        }
        if (!$is_insert) {
            $obj = new game_user_object($uid);
            $obj->set("pk_chats", $obj->get("pk_chats", "string") . $chats);
            if ($oid) {
                $obj = new game_user_object($oid);
                $obj->set("pk_chats", $obj->get("pk_chats", "string") . $chats);
            }
            $chats = "";
        }
    }

//获取技能类型
    static function get_skill_leixing($skill_id, $str = true)
    {
        $leixing = value::getvalue('skill', 'leixing', 'id', $skill_id);
        if ($str) {
            if (value::get_skill_value($skill_id, 'fangshi') == 2) {
                $lx_str = '前置';
            } else {
                $lx_str = '';
            }
            switch ($leixing) {
                case 1:
                    $lx_str .= '物攻';
                    break;
                case 2:
                    $lx_str .= '魔攻';
                    break;
                case 3:
                    $lx_str .= '防御';
                    break;
                case 4:
                    $lx_str .= '辅助';
                    break;
            }
            return $lx_str;
        } else {
            return $leixing;
        }
    }

//宠物是否拥有此技能
    static function pet_have_skill($pet_id, $skill_id)
    {
        $obj = new game_pet_object($pet_id);
        $result = $obj->aget("skill.*.id");
        $id = 0;
        foreach ($result as $v) {
            if ($v == $skill_id) {
                $id++;
            }
        }
        if ($id) {
            return true;
        } else {
            return false;
        }
    }

//获取宠物拥有技能数量
    static function get_pet_skill_count($pet_id)
    {
        $obj = new game_pet_object($pet_id);
        $result = $obj->aget("skill.*.id");
        $id = 0;
        foreach ($result as $v) {
            if ($v) {
                $id++;
            }
        }
        if ($id) {
            return $id;
        } else {
            return 0;
        }
    }

//获取宠物可学习技能数量
    static function get_pet_can_study_skill_count($pet_id)
    {
        $can_study_count = 0;
        $lvl = value::get_pet_value($pet_id, 'lvl');
        $zzid = value::get_pet_value($pet_id, 'pet_id');
        $study_lvl = value::getvalue('pet', 'study_lvl', 'id', $zzid);
        $can_study_fashu = (int)($lvl / $study_lvl + 1);
        $can_study_fashu = $can_study_fashu > 10 ? 10 : $can_study_fashu;
        $p_study_skill_str = value::getvalue('pet', 'study_skill', 'id', $zzid);
        $p_study_skill_arr = explode(',', $p_study_skill_str);
        for ($i = 0; $i < $can_study_fashu; $i++) {
            if (!self::pet_have_skill($pet_id, $p_study_skill_arr[$i])) {
                $can_study_count++;
            }
        }
        return $can_study_count;
    }

//学习技能
    static function study_skill($pet_id, $skill_id, $cnum = -1, $show = true, $need_money = false)
    {
        if (!self::pet_have_skill($pet_id, $skill_id)) {
            $num = -1;
            $zhuansheng = value::get_pet_value($pet_id, 'zhuansheng');
            $max_skill_count = $zhuansheng > 1 ? 5 : 4;
            $pet_name = value::get_pet_value($pet_id, 'name');
            if ($cnum < 0) {
                for ($i = 0; $i < $max_skill_count; $i++) {
                    if (!value::get_pet_value2('skill.' . $i . '.id', $pet_id)) {
                        $num = $i;
                        break;
                    }
                }
                if ($num < 0) {
                    echo '学习' . value::getvalue('skill', 'name', 'id', $skill_id) . ':';
                    br();
                    self::show_skill($skill_id);
                    $i = 0;
                    while ($oskill_id = value::get_pet_value2('skill.' . $i . '.id', $pet_id, false)) {
                        cmd::addcmd('e84,' . $pet_id . ',' . $skill_id . ',' . $i . ',' . $show . ',' . $need_money, "遗忘" . value::getvalue('skill', 'name', 'id', $oskill_id));
                        br();
                        self::show_skill($oskill_id);
                        $i++;
                    }
                    return;
                }
            } else {
                $lvl = value::get_pet_value($pet_id, 'lvl');
                if ($need_money) {
                    if (!item::add_money(-1 * $lvl * 10 * ($zhuansheng + 1))) {
                        return;
                    }
                }
                $num = $cnum;
                if ($show) {
                    $old_skill_id = value::get_pet_value2('skill.' . $num . '.id', $pet_id, false);
                    $obj = new game_pet_object($pet_id);
                    $obj->adel("*skill.{$old_skill_id}.pp");
                    echo '你的' . $pet_name . '遗忘了' . value::getvalue('skill', 'name', 'id', $old_skill_id) . "。";
                    br();
                    //重置默认技能
                    if (value::get_pet_value2('skill_moren.id', $pet_id, false) == $old_skill_id) {
                        value::set_pet_value2('skill_moren.id', 0, $pet_id);
                    }
                }
            }
            $pp = value::getvalue('skill', 'pp', 'id', $skill_id);
            value::set_pet_value2('skill.' . $num . '.id', $skill_id, $pet_id);
            value::set_pet_value2('skill.' . $skill_id . '.pp', (int)($pp * ($zhuansheng ? 1.5 : 1)), $pet_id);
            if ($show) {
                echo '你的' . $pet_name . '学会了' . value::getvalue('skill', 'name', 'id', $skill_id) . "。";
                br();
            }
        } else {
            if ($show) {
                echo '宠物已学习过该技能。';
                br();
            }
        }
    }

//获取技能描述
    static function get_skill_desc($skill_id)
    {
        $lianji_str = '';
        $min_lianji = value::get_skill_value($skill_id, 'min_lianji');
        $max_lianji = value::get_skill_value($skill_id, 'max_lianji');
        if ($max_lianji > 1) {
            $lianji_str = "<br>技能连击：{$min_lianji}-{$max_lianji}次";
        }
        $yczt = value::get_skill_value($skill_id, 'yczt');
        $yczt_str = '';
        if ($yczt) {
            $yczt_str = ',命中则' . value::get_skill_value($skill_id, 'ycztmz') . '%几率敌方' . pet::get_zhuangtai($yczt);
        }
        return value::getvalue('skill', 'miaoshu', 'id', $skill_id) . $lianji_str . $yczt_str . '。';
    }

//显示技能详情
    static function show_skill($skill_id)
    {
        echo '描述:' . self::get_skill_desc($skill_id);
        br();
        echo '属性:' . value::getvalue('skill', 'shuxing', 'id', $skill_id) . ' 类型:' . self::get_skill_leixing($skill_id);
        br();
        echo '威力:' . value::getvalue('skill', 'weili', 'id', $skill_id) . ' 命中:' . value::getvalue('skill', 'mingzhong', 'id', $skill_id);
        br();
        echo '使用次数:' . value::get_skill_value($skill_id, 'pp') . 'PP';
        br();
    }
}

//道具类
class prop
{
    //新道具
    static function new_prop($prop_id, $exp = 0, $star = 0, $cj_pet = 0)
    {
        $user_id = uid();
        $cj_name = value::get_game_user_value('name', $user_id);
        $in_map_id = value::get_game_user_value('in_map_id', $user_id);
        $cj_map = value::get_map_value($in_map_id, 'name');
        $leixing = value::get_prop_value($prop_id, 'leixing');
        $sub_leixing = value::get_prop_value($prop_id, 'sub_leixing');
        $name = value::get_prop_value($prop_id, 'name');
        $is_mm = value::get_prop_value($prop_id, 'is_mm');
        $sex = value::get_prop_value($prop_id, 'sex');
        $zhiye = value::get_prop_value($prop_id, 'zhiye');
        $money = value::get_prop_value($prop_id, 'money');
        $zb_hf = value::get_prop_value($prop_id, 'zb_hf');
        $zb_dy = value::get_prop_value($prop_id, 'zb_dy');
        $is_ct = value::get_prop_value($prop_id, 'is_ct');
        $ts_jz1 = value::get_prop_value($prop_id, 'ts_jz1');
        $ts_jz2 = value::get_prop_value($prop_id, 'ts_jz2');
        $ts_jz3 = value::get_prop_value($prop_id, 'ts_jz3');        
        $ts_jz4 = value::get_prop_value($prop_id, 'ts_jz4');
        $ts_jz5 = value::get_prop_value($prop_id, 'ts_jz5');
        $ts_jz6 = value::get_prop_value($prop_id, 'ts_jz6');
        $ts_jz7 = value::get_prop_value($prop_id, 'ts_jz7');
        $ts_jz8 = value::get_prop_value($prop_id, 'ts_jz8');
        $ts_jz9 = value::get_prop_value($prop_id, 'ts_jz9');
        $ts_jz10 = value::get_prop_value($prop_id, 'ts_jz10');
        $ts_jz11 = value::get_prop_value($prop_id, 'ts_jz11');
        $ts_jz12 = value::get_prop_value($prop_id, 'ts_jz12');
        $ts_jz13 = value::get_prop_value($prop_id, 'ts_jz13');
        $is_jiaoyi  = value::get_prop_value($prop_id, 'is_jiaoyi');
        $exp  = value::get_prop_value($prop_id, 'exp');
        if ($is_jiaoyi) {
            $is_jiaoyi = 0;
        }
        $tz_tz = value::get_prop_value($prop_id, 'tz_tz');
        if (self::get_star_str($star) == "普通") {
            $xs_name = $name;
        } elseif (self::get_star_str($star) == "优秀") {
            $xs_name = "(优)" . $name;
        } elseif (self::get_star_str($star) == "精良") {
            $xs_name = "(精)" . $name;
        } elseif (self::get_star_str($star) == "极品") {
            $xs_name = "(极)" . $name;
        }
        $name = $xs_name;
        $max_naijiu = 0;
        $pugong = 0;
        $pufang = 0;
        $tegong = 0;
        $tefang = 0;
        $minjie = 0;
        $hp = 0;
        $mp = 0;
        $qianghuacishu = 0;
        $is_qianghua = 0;
        //装备品质
        if ($star > 3) {
            $star = 3;
        }
        //装备修正
        $xiuzheng = $star * 0.25;
        //根据道具类型操作
        switch ($leixing) {
            case 1:
                break;
            case 2:
                $sql = "SELECT `max_naijiu`,`star1`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`mz`,`mp`,`mb`,`km`,`xx`,`zd`,`kd`,`bj`,`kb`,`hp` FROM `prop` WHERE `id`={$prop_id} LIMIT 1";
                $result = sql($sql);
                list($max_naijiu, $star1, $pugong, $pufang, $tegong, $tefang, $minjie, $mz, $mp, $mb, $km, $xx, $zd, $kd, $bj, $kb, $hp) = $result->fetch_row();
                $is_qianghua = 1;
                //开始修正
                $max_naijiu = $xiuzheng * $max_naijiu + $max_naijiu;
                $pugong = $xiuzheng * $pugong + $pugong;
                $pufang = $xiuzheng * $pufang + $pufang;
                $tegong = $xiuzheng * $tegong + $tegong;
                $tefang = $xiuzheng * $tefang + $tefang;
                $minjie = $xiuzheng * $minjie + $minjie;
                $hp = $xiuzheng * $hp + $hp;
                $mp = $xiuzheng * $mp + $mp;
                break;
            case 3:
                $sql = "SELECT `max_naijiu`,`star1`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`mz`,`mp`,`mb`,`km`,`xx`,`zd`,`kd`,`bj`,`kb`,`hp` FROM `prop` WHERE `id`={$prop_id} LIMIT 1";
                $result = sql($sql);
                list($max_naijiu, $star1, $pugong, $pufang, $tegong, $tefang, $minjie, $mz, $mp, $mb, $km, $xx, $zd, $kd, $bj, $kb, $hp) = $result->fetch_row();
                $is_qianghua = 1;
                //开始修正
                $max_naijiu = $xiuzheng * $max_naijiu + $max_naijiu;
                $pugong = $xiuzheng * $pugong + $pugong;
                $pufang = $xiuzheng * $pufang + $pufang;
                $tegong = $xiuzheng * $tegong + $tegong;
                $tefang = $xiuzheng * $tefang + $tefang;
                $minjie = $xiuzheng * $minjie + $minjie;
                $hp = $xiuzheng * $hp + $hp;
                $mp = $xiuzheng * $mp + $mp;
                break;
            case 4:
                $sql = "SELECT `max_naijiu`,`star1`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`mz`,`mp`,`mb`,`km`,`xx`,`zd`,`kd`,`bj`,`kb`,`hp` FROM `prop` WHERE `id`={$prop_id} LIMIT 1";
                $result = sql($sql);
                list($max_naijiu, $star1, $pugong, $pufang, $tegong, $tefang, $minjie, $mz, $mp, $mb, $km, $xx, $zd, $kd, $bj, $kb, $hp) = $result->fetch_row();
                $is_qianghua = 1;
                //开始修正
                $max_naijiu = $xiuzheng * $max_naijiu + $max_naijiu;
                $pugong = $xiuzheng * $pugong + $pugong;
                $pufang = $xiuzheng * $pufang + $pufang;
                $tegong = $xiuzheng * $tegong + $tegong;
                $tefang = $xiuzheng * $tefang + $tefang;
                $minjie = $xiuzheng * $minjie + $minjie;
                $hp = $xiuzheng * $hp + $hp;
                $mp = $xiuzheng * $mp + $mp;
                break;
        }
        if ($ts_jz11 > 0) {
            $pugong = mt_rand(1, $pugong);
            $pugong *= 2;
            $pufang = mt_rand(1, $pufang);
            $pufang *= 2;
            $tegong = mt_rand(1, $tegong);
            $tegong *= 2;
            $tefang = mt_rand(1, $tefang);
            $tefang *= 2;
        }
        c_log('new_prop()', value::get_game_user_value('name') . "(id:" . uid() . ")创建了1" . value::get_prop_value($prop_id, 'liangci') . value::get_prop_value($prop_id, 'name') . "\r\n");
        //生成装备
        value::insert('game_prop', "`id`,`tz_tz`,`name`,`sex`,`is_ct`,`zhiye`,`sub_leixing`,`leixing`,`prop_id`,`user_id`,`pet_id`,`map_id`,`star1`,`is_mm`,`money`,`star`,`naijiu`,`max_naijiu`,`exp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`mz`,`hp`,`mp`,`zb_hf`,`zb_dy`,`qianghuacishu`,`mb`,`km`,`xx`,`zd`,`kd`,`bj`,`kb`,`cj_name`,`cj_pet`,`cj_map`,`ts_jz1`,`ts_jz2`,`ts_jz3`,`ts_jz4`,`ts_jz5`,`ts_jz6`,`ts_jz7`,`ts_jz8`,`ts_jz9`,`ts_jz10`,`ts_jz11`,`ts_jz12`,`ts_jz13`,`is_jiaoyi`,`is_qianghua`", "NULL,'{$tz_tz}','{$name}','{$sex}','{$is_ct}','{$zhiye}','{$sub_leixing}','{$leixing}','{$prop_id}','0','0','0','{$star1}','{$is_mm}','{$money}','{$star}', '{$max_naijiu}','{$max_naijiu}','{$exp}','{$pugong}','{$pufang}','{$tegong}','{$tefang}','{$minjie}','{$mz}','{$hp}','{$mp}','{$zb_hf}','{$zb_dy}','0','{$mb}','{$km}','{$xx}','{$zd}','{$kd}','{$bj}','{$kb}','{$cj_name}','{$cj_pet}','{$cj_map}','{$ts_jz1}','{$ts_jz2}','{$ts_jz3}','{$ts_jz4}','{$ts_jz5}','{$ts_jz6}','{$ts_jz7}','{$ts_jz8}','{$ts_jz9}','{$ts_jz10}','{$ts_jz11}','{$ts_jz12}','{$ts_jz13}','{$is_jiaoyi}','{$is_qianghua}'");
        $result = sql("SELECT LAST_INSERT_ID()");
        list($new_prop_id) = $result->fetch_row();
        return $new_prop_id;
    }

    //新宠物卡
    static function new_pet_egg($pet_id, $xingge = -1, $texing = -1, $sex = "", $chujing = 0, $father = "", $mother = "")
    {
        $new_pet_id = pet::new_pet($pet_id, 1, 0, 0, 0, 0, 0, $xingge, $texing, $sex, $chujing);
        $new_egg_id = self::new_prop(62);
        if ($father && $mother) {
            $creator = uid();
            value::set_game_prop_value($new_egg_id, 'father', $father);
            value::set_game_prop_value($new_egg_id, 'mother', $mother);
            value::set_game_prop_value($new_egg_id, 'creator', $creator);
        }
        value::set_pet_value($new_pet_id, 'master_mode', '4');
        value::set_game_prop_value($new_egg_id, 'name', value::get_pet_zz_value($new_pet_id, 'name') . '宠物卡');
        value::set_game_prop_value($new_egg_id, 'pet_id', $new_pet_id);
        return $new_egg_id;
    }

    //显示道具
    static function show_prop($prop_id, $mode = 0, $show_name = true)
    {
        $user_id = uid();
        if ($mode == 5) {
            //黑市交易模式
            if (value::get_game_prop_value($prop_id, 'user_num') != 3) {
                echo "黑市里已经没有这件道具了。";
                br();
                return false;
            }
        }
        $sql = "SELECT `name`,`prop_id`,`star`,`fm`,`sx_lvl`,`sj_lvl`,`ts_jz3`,`ts_jz6`,`ts_jz13`,`star1`,`naijiu`,`max_naijiu`,`exp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`mz`,`hp`,`mp`,`sex`,`sub_leixing`,`mb`,`km`,`xx`,`zd`,`kd`,`bj`,`kb`,`cj_name`,`cj_map`,`cj_time`,`cj_pet`,`is_jiaoyi`,`qianghuacishu` FROM `game_prop` WHERE `id`={$prop_id} LIMIT 1";
        $result = sql($sql);
        list($name, $prop_zz_id, $star, $fm, $sx_lvl, $sj_lvl, $ts_jz3, $ts_jz6, $ts_jz13, $star1, $naijiu, $max_naijiu, $exp, $pugong, $pufang, $tegong, $tefang, $minjie, $mz, $hp, $mp, $sex, $sub_leixing, $mb, $km, $xx, $zd, $kd, $bj, $kb, $cj_name, $cj_map, $cj_time, $cj_pet, $is_jiaoyi, $qianghuacishu) = $result->fetch_row();
        if ($name) {
            $sql = "SELECT `desc`,`id`,`zhiye`,`equip_use_lvl`,`leixing`,`event`,`in_map_use`,`is_diuqi`,`is_xiaohui`,`zb_ls` FROM `prop` WHERE `id` ={$prop_zz_id} LIMIT 1";
            $result = sql($sql);
            list($desc, $id, $zhiye, $equip_use_lvl, $leixing, $event, $in_map_use, $is_diuqi, $is_xiaohui, $is_budiao, $zb_ls) = $result->fetch_row();
            if ($show_name) {
                echo $name;
                br();
                $zb_naijiu = "耐久:{$naijiu}/{$max_naijiu}";
                $zb_zl = "重量：1";
                $zb_tp = "<img src=res/img/zb/" . $id . ".png style='width: 80px;height: 80px;'>";
                if ($leixing == 4) {
                    $zb_tp = "<img src=res/img/zb/" . $id . ".gif style='width: 80px;height: 80px;'>";
                }
                echo "<style>td,th{text-align:center;font-size:16px;}</style><table  border='0'><tbody>
                <tr><td>{$zb_tp}</td><th>{$zb_zl}<br><br>{$zb_naijiu}</th></tr>
                </tbody></table>";
                if ($desc) {
                    echo $desc;
                    br();
                }
            }
            $equip_subtype_arr = config::getConfigByName("equip_subtype");
            foreach ($equip_subtype_arr as $equip_num => $equip_subtype) {
                if ($equip_num == $sub_leixing) {
                    if ($leixing == 4) {
                        echo "位置：翅膀{$equip_num}";
                        br();
                    } else {
                        echo "位置：" . $equip_subtype;
                        br();
                    }
                }
            }
            if ($equip_use_lvl) {
                echo "等级：" . $equip_use_lvl;
                br();
            }
            if ($zhiye) {
                echo "职业：" . $zhiye;
                br();
            }
            if ($sex) {
                echo "性别：" . $sex;
                br();
            }
            if ($is_jiaoyi) {
                echo "<span style=color:red>绑定：是</span>";
                br();
            } else {
                echo "<span style=color:green>绑定：否</span>";
                br();
            }
            if ($ts_jz13) {
                echo "<span style=color:red>套装：万能效果</span>";
                br();
            }
            if ($sj_lvl) {
                echo "<span style=color:red>升阶：{$sj_lvl}阶</span>";
                br();
            }
            if ($sx_lvl) {
                echo "<span style=color:red>升星：{$sx_lvl}星</span>";
                br();
            }
            if ($fm) {
                echo "<span style=color:red>附魔：绑耐</span>";
                br();
            }
            if ($pugong || $pufang || $tegong || $tefang || $hp || $mp) {
                echo "<span style=color:#006000>【基础属性】</span>";
                br();
                if ($leixing) {
                    if ($pugong) {
                        echo "<span style=color:#006000>攻击：</span>" . $pugong;
                        br();
                    }
                    if ($pufang) {
                        echo "<span style=color:#006000>防御：</span>" . $pufang;
                        br();
                    }
                    if ($tegong) {
                        echo "<span style=color:#006000>魔攻：</span>" . $tegong;
                        br();
                    }
                    if ($tefang) {
                        echo "<span style=color:#006000>魔防：</span>" . $tefang;
                        br();
                    }
                    if ($hp) {
                        echo "<span style=color:#006000>生命：</span>" . $hp;
                        br();
                    }
                    if ($mp) {
                        echo "<span style=color:#006000>魔法：</span>" . $mp;
                        br();
                    }
                }
            }
            if ($minjie || $mz || $mb || $km || $xx || $zd || $kd || $bj || $kb  || $exp || $ts_jz6) {
                echo "<span style=color:#006000>【特殊属性】</span>";
                br();
                if ($minjie) {
                    echo "<span style=color:red>躲避：+{$minjie}%</span>";
                    br();
                }
                if ($mz) {
                    echo "<span style=color:red>命中：+{$mz}%</span>";
                    br();
                }
                if ($mb) {
                    echo "<span style=color:red>麻痹：+{$mb}%</span>";
                    br();
                }
                if ($km) {
                    echo "<span style=color:red>免麻：+{$km}%</span>";
                    br();
                }
                if ($xx) {
                    echo "<span style=color:red>吸血：+{$xx}%</span>";
                    br();
                }
                if ($zd) {
                    echo "<span style=color:red>中毒：+{$zd}%</span>";
                    br();
                }
                if ($kd) {
                    echo "<span style=color:red>免毒：+{$kd}%</span>";
                    br();
                }
                if ($bj) {
                    echo "<span style=color:red>暴击：+{$bj}%</span>";
                    br();
                }
                if ($exp) {
                    echo "<span style=color:red>经验：+{$exp}%</span>";
                    br();
                }
                if ($kb) {
                    echo "<span style=color:red>抗暴：+{$kb}%</span>";
                    br();
                }
                if ($ts_jz6) {
                    echo "<span style=color:red>负重：+{$ts_jz6}</span>";
                    br();
                }
            }
            if ($leixing != 4) {
                echo "<span style=color:#006000>【装备来源】</span>";
                br();
                echo "<span style=color:#BB5E00>地图：{$cj_map}</span>";
                br();
                if ($cj_pet) {
                    echo "<span style=color:#BB5E00>怪物：{$cj_pet}</span>";
                    br();
                    echo "<span style=color:#BB5E00>击杀：{$cj_name}</span>";
                    br();
                } else {
                    echo "<span style=color:#BB5E00>出处：NPC制造</span>";
                    br();
                    echo "<span style=color:#BB5E00>玩家：{$cj_name}</span>";
                    br();
                }
                echo "<span style=color:#BB5E00>时间：{$cj_time}</span>";
                br();
                echo "<span style=color:#BB5E00>品质：" . self::get_star_str($star) . "</span>";
                br();
                $zb_ls1 = value::get_prop_value($prop_zz_id, 'zb_ls');
                if ($zb_ls1 > 0) {
                    $now_time = date('Y-m-d H:i:s', strtotime('-1440 minute'));
                    $mzsysj = strtotime($cj_time) - strtotime($now_time);
                    $mz_hour = (int)($mzsysj / 3600);
                    $mz_min = (int)($mzsysj % 3600 / 60);
                    echo "<span style=color:green>临时：剩{$mz_hour}小时{$mz_min}分钟</span>";
                    br();
                }
            }
            //使用传送
            $ts_ts_jz3 = equip::get_user_equip_jc('ts_jz3', $user_id);
            if ($ts_jz3 && $ts_ts_jz3) {
                cmd::addcmd('zb_csjz', '使用' . $name);
                br();
            }
            if (!$mode) {
                //默认背包模式
                if ($leixing > 1 && $leixing < 4 && value::get_user_value('bujinlingqi') && $naijiu < $max_naijiu) {
                    cmd::addcmd('e132,0,' . $prop_id, '修补装备');
                    br();
                }
                if ($in_map_use) {
                    if ($leixing == 2) {
                        cmd::addcmd('e10002,' . $prop_id, '使用装备');
                        br();
                    }
                    if ($leixing == 3) {
                        cmd::addcmd('e10002_sz,' . $prop_id, '使用装备');
                        br();
                    }
                }
                //宠物卡
                if ($leixing == 5) {
                    cmd::addcmd('e150,' . $prop_id, '开始孵化');
                    br();
                }
                if ($is_diuqi) {
                    cmd::addcmd('e99,' . $prop_id . ',0', '丢弃物品');
                    br();
                }
                if ($is_xiaohui && $is_jiaoyi < 1) {
                    cmd::addcmd('e99,' . $prop_id . ',1', '销毁物品');
                    br();
                }
            } else if ($mode == 1) {
                //地图掉落物模式
                cmd::addcmd('e100,' . $prop_id, '捡起物品');
                br();
            } else if ($mode == 2) {
                //宠物装配模式
                if ($leixing > 1 && $leixing < 4 && value::get_user_value('bujinlingqi') && $naijiu < $max_naijiu) {
                    cmd::addcmd('e132,1,' . $prop_id, '修补装备');
                    br();
                }
                $user_id = uid();
                $pet_id = value::get_game_prop_value($prop_id, 'pet_id');
                $matster_id = value::get_pet_value($pet_id, 'master_id');
                if ($matster_id == $user_id && $leixing != 4) {
                    cmd::addcmd('e103,' . $prop_id, '卸下装备');
                    br();
                }
                if ($matster_id != $user_id) {
                    cmd::addcmd('e621', '返回宠物');
                    br();
                }
            } else if ($mode == 3) {
                //仓库模式
                cmd::addcmd('e30,2,' . $prop_id, '取出道具');
                br();
                cmd::addcmd('e30,3,' . $prop_id . ',1', '销毁道具');
                br();
            } else if ($mode == 4) {
                //黑市出售模式
                cmd::addcmd('e621' . $prop_id, '出售道具');
                br();
            } else if ($mode == 5) {
                //黑市交易模式
            } else if ($mode == 6) {
                //装配道具显示
            } else if ($mode == 7) {
                //交易详情显示
            } else if ($mode == 8) {
                //存入道具显示
            } else if ($mode == 9) {
                //孵化巢模式
                if ($fuhuadu >= 100) {
                    cmd::addcmd('e152,' . $prop_id, '取出宠物');
                    br();
                } else {
                    if (item::get_item(84)) {
                        cmd::addcmd('e55,84', '加速孵化');
                        br();
                    }
                }
                cmd::addcmd('e151,' . $prop_id, '放回背包');
                br();
            } else if ($mode == 10) {
                //玩家装备模式
            }
            return true;
        } else {
            echo "没有找到这件道具。";
            br();
            return false;
        }
    }
    //显示道具
    static function gm_show_prop($prop_id, $mode = 0, $show_name = true)
    {
        $user_id = uid();
        $sql = "SELECT `name`,`id`,`star`,`star1`,`max_naijiu`,`exp`,`pugong`,`pufang`,`tegong`,`tefang`,`minjie`,`mb`,`km`,`xx`,`zd`,`kd`,`bj`,`hp`,`sub_leixing` FROM `prop` WHERE `id`={$prop_id} LIMIT 1";
        $result = sql($sql);
        list($name, $prop_zz_id, $star, $star1, $max_naijiu, $exp, $pugong, $pufang, $tegong, $tefang, $minjie, $mb, $km, $xx, $zd, $kd, $bj, $hp, $sub_leixing) = $result->fetch_row();
        if ($name) {
            $sql = "SELECT `desc`,`id`,`equip_use_lvl`,`leixing`,`event`,`in_map_use`,`is_diuqi`,`is_xiaohui`,`is_budiao` FROM `prop` WHERE `id` ={$prop_zz_id} LIMIT 1";
            $result = sql($sql);
            list($desc, $id, $equip_use_lvl, $leixing, $event, $in_map_use, $is_diuqi, $is_xiaohui, $is_budiao) = $result->fetch_row();
            //宠物卡
            $pet_id = 0;
            $fuhuadu = 0;
            if ($show_name) {
                echo $name;
                br();
                $zb_tp = "<img src=res/img/zb/" . $id . ".png style='width: 80px;height: 80px;'>";
                if ($leixing == 4) {
                    $zb_tp = "<img src=res/img/zb/" . $id . ".gif style='width: 80px;height: 80px;'>";
                }
                echo $zb_tp;
                br();
                if ($desc) {
                    echo $desc;
                    br();
                }
            }
            if ($leixing > 1 && $leixing < 4) {
                if ($leixing == 2) {
                    if ($pugong) {
                        echo "+{$pugong}点攻击";
                        br();
                    }
                    if ($pufang) {
                        echo "+{$pufang}点防御";
                        br();
                    }
                    if ($tegong) {
                        echo "+{$tegong}点魔攻";
                        br();
                    }
                    if ($tefang) {
                        echo "+{$tefang}点魔防";
                        br();
                    }
                    if ($minjie) {
                        echo "+{$minjie}点灵活";
                        br();
                    }
                    if ($hp) {
                        echo "+{$hp}点HP";
                        br();
                    }
                }
                echo "需要等级:" . $equip_use_lvl . "";
                br();
                echo "品质:" . self::get_star_str($star) . "";
                br();
                echo "耐久:{$max_naijiu}";
                br();
                if ($is_budiao) {
                    echo "特效:无法掉落";
                    br();
                }
            }
        }
    }

    //用户获得道具
    static function user_get_prop($prop_id, $user_id = 0, $user_num = 0, $xianshi = true, $qiangzhi = false)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $prop_zz_id = value::get_game_prop_value($prop_id, 'prop_id');
        $leixing = value::get_prop_value($prop_zz_id, 'leixing');
        $fuzhong = value::get_prop_value($prop_zz_id, 'fuzhong');
        if ($user_num == 1 && !$qiangzhi) {
            $beibaofuzhong = user::get_fuzhong();
            $beibaorongliang = user::get_rongliang();
            if ($beibaofuzhong + $fuzhong > $beibaorongliang) {
                echo "抱歉,您的背包已满。";
                br();
                return false;
            }
        }
        if ($user_num == 2 && !$qiangzhi) {
            $cangkufuzhong = user::get_fuzhong(1);
            $cangkurongliang = user::get_rongliang(1);
            if ($cangkufuzhong + $fuzhong > $cangkurongliang) {
                echo "抱歉,您的仓库已满。";
                br();
                return false;
            }
        }
        $get_miaoshu = "";
        value::set_game_prop_value($prop_id, 'map_id', '0');
        value::set_game_prop_value($prop_id, 'team_id', '0');
        value::set_game_prop_value($prop_id, 'user_id', $user_id);
        value::set_game_prop_value($prop_id, 'user_num', $user_num);
        $name = value::get_game_prop_value($prop_id, 'name');
        $liangci = value::get_prop_value(value::get_game_prop_value($prop_id, 'prop_id'), 'liangci');
        if ($xianshi || !$xianshi) {
            if ($leixing == 1) {
                $exp = value::get_game_prop_value($prop_id, 'exp');
                $get_miaoshu = "你获得了1{$liangci}含有{$exp}点经验的经验内丹。<br>";
            }
            if (!$get_miaoshu) {
                echo "你获得了1{$liangci}{$name}。<br>";
            } else {
                echo $get_miaoshu;
            }
        }
        return true;
    }

    static function user_get_prop_sz($prop_id, $user_id = 0, $user_num = 0, $xianshi = true, $qiangzhi = false)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $prop_zz_id = value::get_game_prop_value($prop_id, 'prop_id');
        $leixing = value::get_prop_value($prop_zz_id, 'leixing');
        $fuzhong = value::get_prop_value($prop_zz_id, 'fuzhong');
        if ($user_num == 1 && !$qiangzhi) {
            $beibaofuzhong = user::get_fuzhong();
            $beibaorongliang = user::get_rongliang();
            if ($beibaofuzhong + $fuzhong > $beibaorongliang) {
                echo "抱歉,您的背包已满。";
                br();
                return false;
            }
        }
        if ($user_num == 2 && !$qiangzhi) {
            $cangkufuzhong = user::get_fuzhong(1);
            $cangkurongliang = user::get_rongliang(1);
            if ($cangkufuzhong + $fuzhong > $cangkurongliang) {
                echo "抱歉,您的仓库已满。";
                br();
                return false;
            }
        }
        $get_miaoshu = "";
        value::set_game_prop_value($prop_id, 'map_id', '0');
        value::set_game_prop_value($prop_id, 'team_id', '0');
        value::set_game_prop_value($prop_id, 'user_id', $user_id);
        value::set_game_prop_value($prop_id, 'user_num', $user_num);
        $name = value::get_game_prop_value($prop_id, 'name');
        $liangci = value::get_prop_value(value::get_game_prop_value($prop_id, 'prop_id'), 'liangci');
        if ($xianshi) {
            if ($leixing == 1) {
                $exp = value::get_game_prop_value($prop_id, 'exp');
                $get_miaoshu = "你获得了1{$liangci}含有{$exp}点经验的经验内丹。<br>";
            }
            if (!$get_miaoshu) {
                echo "你获得了1{$liangci}{$name}。<br>";
            } else {
                echo $get_miaoshu;
            }
        }
        return true;
    }

    //用户获得道具
    static function user_get_prop1($prop_id)
    {
        value::set_game_prop_value($prop_id, 'map_id', '0');
        value::set_game_prop_value($prop_id, 'team_id', '0');
        value::set_game_prop_value($prop_id, 'user_id', 0);
        value::set_game_prop_value($prop_id, 'user_num', 0);
    }

//用户是否拥有该道具
    static function user_have_prop($prop_id, $user_id = 0, $user_num = 1)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        if ($user_id == value::get_game_prop_value($prop_id, 'user_id') && $user_num == value::get_game_prop_value($prop_id, 'user_num')) {
            return true;
        } else {
            return false;
        }
    }


//用户使用道具   
    static function user_use_prop($prop_id = 0, $pet_id = 0, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        //是否选择了宠物
        if (!$pet_id) {
            echo "你要给谁使用?";
            br();
            $sql = "SELECT `id`,`name`,`lvl`,`nick_name` FROM `game_pet` WHERE `master_id`={$user_id} AND `master_mode`=1 LIMIT 5";
            $result = sql($sql);
            while (list($oid, $oname, $olvl, $onick_name) = $result->fetch_row()) {
                cmd::addcmd("e101,{$prop_id},{$oid}", ($onick_name ? "({$onick_name})" : "") . "{$oname}({$olvl}级)");
                br();
            }
            return false;
        } else {
            //宠物归属
            if (!user::have_pet(0, $pet_id)) {
                echo "该宠物已经不属于你了。";
                br();
                return false;
            }
            //道具归属
            if (self::user_have_prop($prop_id)) {
                $prop_zz_id = value::get_game_prop_value($prop_id, 'prop_id');
                $leixing = value::get_prop_value($prop_zz_id, 'leixing');
                switch ($leixing) {
                    case 1:
                        //宠物是否死亡
                        if (!value::get_pet_value($pet_id, 'is_dead')) {
                            if (!user::get("pkjjzt")) {
                                $exp = value::get_game_prop_value($prop_id, 'exp');
                                pet::get_exp($pet_id, $exp);
                                self::user_lose_prop($prop_id, 1, 1, false);
                            } else {
                                echo "你在竞技中,无法喂食仙丹。";
                                br();
                            }
                        } else {
                            echo "宠物已死亡,无法喂食仙丹。";
                            br();
                        }
                        break;
                    case 2:
                        self::user_lose_prop($prop_id, 2, 1, false);
                        if (!self::pet_get_prop($prop_id, $pet_id)) {
                            self::user_get_prop($prop_id, $user_id, 1, false, true);
                        }
                        break;
                    case 3:
                        self::user_lose_prop($prop_id, 2, 1, false);
                        self::user_lose_prop($prop_id, 2, 1, false);
                        if (!self::pet_get_prop($prop_id, $pet_id)) {
                            self::user_get_prop($prop_id, $user_id, 1, false, true);
                        }
                        break;
                }
            } else {
                echo "你已经没有这个道具了。";
                br();
                return false;
            }
            return true;
        }
    }

//地图获取道具
    static function map_get_prop($in_map_id, $prop_id, $user_id = 0, $user_team_id = 0, $user_union_id = 0)
    {
        value::set_game_prop_value($prop_id, 'map_id', $in_map_id);
        value::set_game_prop_value($prop_id, 'time', date("Y-m-d H:i:s"));
        $m_is_danrenfuben = value::get_map_value($in_map_id, 'is_danrenfuben');
        $m_is_duorenfuben = value::get_map_value($in_map_id, 'is_duorenfuben');
        $m_is_gonghuilingdi = value::get_map_value($in_map_id, 'is_gonghuilingdi');
        if ($m_is_danrenfuben) {
            value::set_game_prop_value($prop_id, 'user_id', $user_id);
        }
        if ($m_is_duorenfuben) {
            value::set_game_prop_value($prop_id, 'team_id', $user_team_id);
        }
        if ($m_is_gonghuilingdi) {
            value::set_game_prop_value($prop_id, 'union_id', $user_union_id);
        }
    }

//地图上是否还有该道具
    static function map_have_prop($prop_id, $map_id)
    {
        if ($map_id == value::get_game_prop_value($prop_id, 'map_id')) {
            return true;
        } else {
            return false;
        }
    }

    //宠物装配道具
    static function pet_get_prop($prop_id, $pet_id, $xianshi = true)
    {
        $equip_liuwei_id = 0;
        $equip_baowu_id = 0;
        $new_equip_sub_leixing = value::get_game_prop_zz_value($prop_id, 'sub_leixing');
        $new_equip_use_lvl = value::get_game_prop_zz_value($prop_id, 'equip_use_lvl');
        $name = value::get_pet_value($pet_id, 'name');
        $prop_name = value::get_game_prop_value($prop_id, 'name');
        if (pet::get($pet_id, 'lvl') >= $new_equip_use_lvl) {
            $sql = "SELECT `id`,`prop_id` FROM `game_prop` WHERE `pet_id`={$pet_id} LIMIT 0,2";
            $result = sql($sql);
            while (list($equip_id, $equip_zz_id) = $result->fetch_row()) {
                if (value::get_prop_value($equip_zz_id, 'leixing') == 2) {
                    if (value::get_prop_value($equip_zz_id, 'sub_leixing') == $new_equip_sub_leixing) {
                        $equip_liuwei_id = $equip_id;
                    }
                } else {
                    $equip_baowu_id = $equip_id;
                }
            }
            if ($equip_liuwei_id) {
                self::pet_lose_prop($equip_liuwei_id, $pet_id);
                self::user_get_prop($equip_liuwei_id, 0, 1, false, true);
            }
            if ($equip_baowu_id) {
                self::pet_lose_prop($equip_baowu_id, $pet_id);
                self::user_get_prop($equip_baowu_id, 0, 1, false, true);
            }
            value::set_game_prop_value($prop_id, 'pet_id', $pet_id);
            if ($xianshi) {
                echo "{$name}装配了{$prop_name}。";
                br();
            }
            return true;
        } else {
            if ($xianshi) {
                echo "{$name}的等级还不能装配{$prop_name}。";
                br();
            }
            return false;
        }
    }

//宠物失去道具
    static function pet_lose_prop($prop_id, $pet_id, $xianshi = true)
    {
        $name = value::get_pet_value($pet_id, 'name');
        $prop_name = value::get_game_prop_value($prop_id, 'name');
        if (self::pet_have_prop($prop_id, $pet_id)) {
            value::set_game_prop_value($prop_id, 'pet_id', '0');
            $max_hp = pet::get_max_hp($pet_id);
            if (value::get_pet_value($pet_id, 'hp') > $max_hp) {
                value::set_pet_value($pet_id, 'hp', $max_hp);
            }
            if ($xianshi) {
                echo "{$name}卸下了{$prop_name}。";
                br();
            }
        } else {
            echo "{$name}身上没有这个道具了。";
            br();
        }
    }
    static function user_lose_prop($prop_id, $mode = 0, $step = 0, $xianshi = true)
    {
        $user_id = uid();
        //获取队伍id
        $user_team_id = value::get_game_user_value('team_id');
        $user_union_id = user::get_union();
        $name = value::get_game_prop_value($prop_id, 'name');
        $prop_zz_id = value::get_game_prop_value($prop_id, 'prop_id');
        $liangci = value::get_prop_value($prop_zz_id, 'liangci');
        $leixing = value::get_prop_value($prop_zz_id, 'leixing');
        if (!$step) {
            switch ($mode) {
                //丢弃
                case 0:
                    echo '你确定要丢弃这' . $liangci . $name . '吗?';
                    br();
                    break;
                //销毁
                case 1:
                    echo '你确定要销毁这' . $liangci . $name . '吗?';
                    br();
                    break;
            }
        } else {
            if (self::user_have_prop($prop_id)) {
                switch ($mode) {
                    //丢弃
                    case 0:
                        value::set_game_prop_value($prop_id, 'user_id', '0');
                        value::set_game_prop_value($prop_id, 'user_num', '0');
                        $in_map_id = value::get_game_user_value('in_map_id');
                        self::map_get_prop($in_map_id, $prop_id, $user_id, $user_team_id, $user_union_id);
                        if ($xianshi) {
                            echo '你成功的丢弃了1' . $liangci . $name . '。';
                            br();
                        }
                        break;
                    //销毁
                    case 1:
                        if ($leixing < 5) {
                            self::del_prop($prop_id);
                            if ($xianshi) {
                                echo '你成功的销毁了1' . $liangci . $name . '。';
                                br();
                            }
                        }
                        break;
                    //装配
                    case 2:
                        if ($leixing > 1 && $leixing < 4) {
                            value::set_game_prop_value($prop_id, 'user_id', '0');
                            value::set_game_prop_value($prop_id, 'user_num', '0');
                        }
                        break;
                }
                return true;
            } else {
                echo "你已经没有这个道具了。";
                br();
                return false;
            }
        }
    }

//宠物卸下所有道具
    static function pet_lose_all_prop($pet_id, $mode = 0, $xianshi = true)
    {
        $user_id = value::get_pet_value($pet_id, 'master_id');
        if ($user_id) {
            $in_map_id = value::get_game_user_value('in_map_id', $user_id);
            $team_id = value::get_game_user_value('team_id', $user_id);
        } else {
            $in_map_id = value::get_pet_value($pet_id, 'map_id');
            $team_id = value::get_pet_value($pet_id, 'team_id');
        }
        $pet_name = value::get_pet_value($pet_id, 'name');
        $m_is_danrenfuben = value::get_map_value($in_map_id, 'is_danrenfuben');
        $equip_liuwei_id_arr = pet::get_prop_id($pet_id, 2);
        foreach ($equip_liuwei_id_arr as $equip_liuwei_id) {
            $prop_name = value::get_game_prop_value($equip_liuwei_id, 'name');
            $zzid = value::get_game_prop_value($equip_liuwei_id, 'prop_id');
            $liangci = value::get_prop_value($zzid, 'liangci');
            self::pet_lose_prop($equip_liuwei_id, $pet_id, false);
            if (!$user_id) {
                if ($m_is_danrenfuben) {
                    $user_id = uid();
                }
                self::map_get_prop($in_map_id, $equip_liuwei_id, $user_id, $team_id);
                if ($xianshi) {
                    echo "{$pet_name}的{$prop_name}掉在了地上。";
                    br();
                }
            } else {
                if (!$mode) {
                    self::user_get_prop($equip_liuwei_id, $user_id, 1, true, true);
                } else if ($mode == 1) {
                    self::user_get_prop($equip_liuwei_id, $user_id, 1, false, true);
                    c_add_xiaoxi('你获得了1' . $liangci . $prop_name . "。", 0, $user_id, $user_id);
                }
            }
        }
        $equip_baowu_id = pet::get_prop_id($pet_id, 3);
        if ($equip_baowu_id) {
            $prop_name = value::get_game_prop_value($equip_baowu_id, 'name');
            self::pet_lose_prop($equip_baowu_id, $pet_id, false);
            $zzid = value::get_game_prop_value($equip_baowu_id, 'prop_id');
            $liangci = value::get_prop_value($zzid, 'liangci');
            if (!$user_id) {
                if ($m_is_danrenfuben) {
                    $user_id = uid();
                }
                self::map_get_prop($in_map_id, $equip_baowu_id, $user_id, $team_id);
                if ($xianshi) {
                    echo "{$pet_name}的{$prop_name}掉在了地上。";
                    br();
                }
            } else {
                if (!$mode) {
                    self::user_get_prop($equip_baowu_id, $user_id, 1, true, true);
                } else if ($mode == 1) {
                    self::user_get_prop($equip_baowu_id, $user_id, 1, false, true);
                    c_add_xiaoxi('你获得了1' . $liangci . $prop_name . "。", 0, $user_id, $user_id);
                }
            }
        }
    }

//宠物是否拥有该道具
    static function pet_have_prop($prop_id, $pet_id)
    {
        $prop_id1 = value::get_game_prop_value($prop_id, 'id');
        if ($pet_id == value::get_game_prop_value($prop_id1, 'pet_id')) {
            return true;
        } else {
            return false;
        }
    }

//删除道具
    static function del_prop($prop_id)
    {
        //是否宠物卡
        if (value::get_game_prop_value($prop_id, 'prop_id') == 0) {
            //获取宠物宠物id
            $pet_id = value::get_game_prop_value($prop_id, 'pet_id');
            //宠物宠物是否出生
            if (!value::get_pet_value($pet_id, 'master_id')) {
                //删除宠物宠物
                pet::del_pet($pet_id);
                //道具已经删除
                return;
            }
        }
        //删除道具
        sql("DELETE FROM `game_prop` WHERE `id`={$prop_id} LIMIT 1");
    }

//获取品质
    static function get_star_str($star)
    {
        if ($star == 0) {
            return '普通';
        }
        if ($star == 1) {
            return '优秀';
        }
        if ($star == 2) {
            return '精良';
        }
        if ($star > 2) {
            return '极品';
        }
        return "";
    }

    //获取game_prop属性
    static function get($id, $valuename)
    {
        return value::get_game_prop_value($id, $valuename);
    }

    //设置game_prop属性
    static function set($id, $valuename, $value)
    {
        return value::set_game_prop_value($id, $valuename, $value);
    }

    //增加game_prop属性
    static function add($id, $valuename, $value)
    {
        return value::add_game_prop_value($id, $valuename, $value);
    }
}

//装备类
class equip extends prop
{
    //获取属性
    static function get($eid, $valuename)
    {
        return value::get_game_prop_value($eid, $valuename);
    }

    //设置属性
    static function set($eid, $valuename, $value)
    {
        value::set_game_prop_value($eid, $valuename, $value);
    }

    //获取种族属性
    static function getzz($eid, $valuename)
    {
        return value::get_game_prop_zz_value($eid, $valuename);
    }

    //是否拥有装备
    static function user_have_equip($equipId, $userId, $userNum)
    {
        return self::user_have_prop($equipId, $userId, $userNum);
    }

    //装配装备
    static function user_get_equip($eid, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        $eLx = self::getzz($eid, 'sub_leixing');
        $oldEquipId = $u->get("equip.{$eLx}");
        $leixing = value::get_game_prop_value($oldEquipId, 'leixing');
        if ($oldEquipId && $leixing == 2) {
            self::user_lose_equip($oldEquipId, $userId);
        }
        if (self::user_have_equip($eid, $userId, 1)) {
            self::user_get_prop($eid, $userId, 5, false, true);
            value::set_game_prop_value($eid, 'is_jiaoyi', '1');
            $u->set("equip.{$eLx}", $eid);
            echo "你装备了1", self::getzz($eid, 'liangci'), self::get($eid, 'name'), "";
            br();
        } else {
            echo "你身上没有这", self::getzz($eid, 'liangci'), self::get($eid, 'name'), "了。";
            br();
        }
    }

    static function user_get_equip_sz($eid, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        $eLx = self::getzz($eid, 'sub_leixing');
        $oldEquipId = $u->get("equip.sz.{$eLx}");
        $leixing = value::get_game_prop_value($oldEquipId, 'leixing');
        if ($oldEquipId && $leixing == 3) {
            self::user_lose_equip_sz($oldEquipId, $userId);
        }
        if (self::user_have_equip($eid, $userId, 1)) {
            self::user_get_prop($eid, $userId, 5, false, true);
            value::set_game_prop_value($eid, 'is_jiaoyi', '1');
            $u->set("equip.sz.{$eLx}", $eid);
            echo "你装备了1", self::getzz($eid, 'liangci'), self::get($eid, 'name'), "";
            br();
        } else {
            echo "你身上没有这", self::getzz($eid, 'liangci'), self::get($eid, 'name'), "了。";
            br();
        }
    }

    static function user_get_equip_cb($eid, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        $eLx = self::getzz($eid, 'sub_leixing');
        $oldEquipId = $u->get("equip.cb.{$eLx}");
        $leixing = value::get_game_prop_value($oldEquipId, 'leixing');
        if ($oldEquipId && $leixing == 4) {
            prop::del_prop($oldEquipId);
        }
        if (self::user_have_equip($eid, $userId, 1)) {
            self::user_get_prop($eid, $userId, 5, false, true);
            value::set_game_prop_value($eid, 'is_jiaoyi', '1');
            $u->set("equip.cb.{$eLx}", $eid);
        }
    }

    //卸下装备
    static function user_lose_equip($eid, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        $eLx = self::getzz($eid, 'sub_leixing');
        self::user_get_prop($eid, $userId, 1, false, true);
        $u->set("equip.{$eLx}", 0);
        echo "你卸下了1", self::getzz($eid, 'liangci'), self::get($eid, 'name'), "";
        br();
    }

    static function user_lose_equip_sz($eid, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        $eLx = self::getzz($eid, 'sub_leixing');
        self::user_get_prop($eid, $userId, 1, false, true);
        $u->set("equip.sz.{$eLx}", 0);
        echo "你卸下了1", self::getzz($eid, 'liangci'), self::get($eid, 'name'), "";
        br();
    }

    static function user_lose_equip_cb($eid, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        $eLx = self::getzz($eid, 'sub_leixing');
        self::user_get_prop($eid, $userId, 1, false, true);
        $u->set("equip.cb.{$eLx}", 0);
        echo "你卸下了1", self::getzz($eid, 'liangci'), self::get($eid, 'name'), "";
        br();
    }

    static function user_lose_equip1($eid, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $u = new game_user_object($user_id);
        $eLx = self::getzz($eid, 'sub_leixing');
        self::user_get_prop1($eid);
        $u->set("equip.{$eLx}", 0);
    }
    //选择可用装备
    static function user_choice_equip($sub_leixing, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $ec = 0;
        $leixingStr = self::leixing_int2str($sub_leixing);
        echo "请选择你要装备的", $leixingStr, ":";
        br();
        $sql = "select id from game_prop where user_id={$userId} AND user_num=1 AND leixing=2 AND sub_leixing={$sub_leixing}";
        $rs = sql($sql);
        while (list($equip_id) = $rs->fetch_row()) {
            $equip_name = self::get($equip_id, 'name');
            $star = self::get($equip_id, 'star1');
            $equip_zz_id = self::get($equip_id, 'prop_id');
            $liangci = self::getzz($equip_zz_id, 'liangci');
            cmd::addcmd("e10002,{$equip_id},{$userId},{$sub_leixing}", $equip_name . "x1" . $liangci);
            br();
            $ec++;
        }
        if (!$ec) {
            echo "你没有可以装备的{$leixingStr}!";
            br();
        }
    }

    static function user_choice_equip_sz($sub_leixing, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $ec = 0;
        $leixingStr = self::leixing_int2str($sub_leixing);
        echo "请选择你要装备的", $leixingStr, ":";
        br();
        $sql = "select id from game_prop where user_id={$userId} AND user_num=1 AND leixing=3 AND sub_leixing={$sub_leixing}";
        $rs = sql($sql);
        while (list($equip_id) = $rs->fetch_row()) {
            $equip_name = self::get($equip_id, 'name');
            $star = self::get($equip_id, 'star1');
            $equip_zz_id = self::get($equip_id, 'prop_id');
            $liangci = self::getzz($equip_zz_id, 'liangci');
            cmd::addcmd("e10002_sz,{$equip_id},{$userId},{$sub_leixing}", $equip_name . "x1" . $liangci);
            br();
            $ec++;
        }
        if (!$ec) {
            echo "你没有可以装备的{$leixingStr}!";
            br();
        }
    }

    static function user_choice_equip_cb($sub_leixing, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $ec = 0;
        $leixingStr = self::leixing_int2str($sub_leixing);
        echo "请选择你要装备的翅膀:";
        br();
        $sql = "select id from game_prop where user_id={$userId} AND user_num=1 AND leixing=4 AND sub_leixing={$sub_leixing}";
        $rs = sql($sql);
        while (list($equip_id) = $rs->fetch_row()) {
            $equip_name = self::get($equip_id, 'name');
            $star = self::get($equip_id, 'star1');
            $equip_zz_id = self::get($equip_id, 'prop_id');
            $liangci = self::getzz($equip_zz_id, 'liangci');
            cmd::addcmd("e10002_cb,{$equip_id},{$userId},{$sub_leixing}", $equip_name . "x1" . $liangci);
            br();
            $ec++;
        }
        if (!$ec) {
            echo "你没有可以装备的翅膀!";
            br();
        }
    }

    //获取玩家当前部位装备
    static function get_user_in_equip($subType, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        return $u->get("equip.{$subType}");
    }

    static function get_user_in_equip_sz($subType, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        return $u->get("equip.sz.{$subType}");
    }
    
    static function get_user_in_equip_cb($subType, $userId = 0)
    {
        if (!$userId) {
            $userId = uid();
        }
        $u = new game_user_object($userId);
        return $u->get("equip.cb.{$subType}");
    }

    //获取玩家装备加成
    static function get_user_equip_jc($liuwei, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $hp = 0;
        $pg = 0;
        $pf = 0;
        $tg = 0;
        $tf = 0;
        $mj = 0;
        $mp = 0;
        $mz = 0;
        $xx = 0;
        $mb = 0;
        $kb = 0;
        $zd = 0;
        $kd = 0;
        $bj = 0;
        $hf = 0;
        $dy = 0;
        $km = 0;
        $ct = 0;
        $exp = 0;
        $tsjz1  = 0;
        $tsjz2  = 0;
        $tsjz3  = 0;
        $tsjz4  = 0;
        $tsjz5  = 0;
        $tsjz6  = 0;
        $tsjz7  = 0;
        $tsjz8  = 0;
        $tsjz9  = 0;
        $tsjz10  = 0;
        $tsjz11  = 0;
        $tsjz12  = 0;
        $tsjz13  = 0;
        $equip_subtype_arr = config::getConfigByName("equip_subtype");
        foreach ($equip_subtype_arr as $equip_num => $equip_subtype) {
            $in_equip_id = equip::get_user_in_equip($equip_num, $user_id);
            if ($in_equip_id) {
                $hp += self::get($in_equip_id, 'hp');
                $pg += self::get($in_equip_id, 'pugong');
                $pf += self::get($in_equip_id, 'pufang');
                $tg += self::get($in_equip_id, 'tegong');
                $tf += self::get($in_equip_id, 'tefang');
                $mj += self::get($in_equip_id, 'minjie');
                $mz += self::get($in_equip_id, 'mz');
                $xx += self::get($in_equip_id, 'xx');
                $zd += self::get($in_equip_id, 'zd');
                $kd += self::get($in_equip_id, 'kd');
                $bj += self::get($in_equip_id, 'bj');
                $kb += self::get($in_equip_id, 'kb');
                $mb += self::get($in_equip_id, 'mb');
                $km += self::get($in_equip_id, 'km');
                $mp += self::get($in_equip_id, 'mp');
                $hf += self::get($in_equip_id, 'zb_hf');
                $dy += self::get($in_equip_id, 'zb_dy');
                $km += self::get($in_equip_id, 'km');
                $ct += self::get($in_equip_id, 'is_ct');
                $exp += self::get($in_equip_id, 'exp');
                $tsjz1 += self::get($in_equip_id, 'ts_jz1');
                $tsjz2 += self::get($in_equip_id, 'ts_jz2');
                $tsjz3 += self::get($in_equip_id, 'ts_jz3');
                $tsjz4 += self::get($in_equip_id, 'ts_jz4');
                $tsjz5 += self::get($in_equip_id, 'ts_jz5');
                $tsjz6 += self::get($in_equip_id, 'ts_jz6');
                $tsjz7 += self::get($in_equip_id, 'ts_jz7');
                $tsjz8 += self::get($in_equip_id, 'ts_jz8');
                $tsjz9 += self::get($in_equip_id, 'ts_jz9');
                $tsjz10 += self::get($in_equip_id, 'ts_jz10');
                $tsjz11 += self::get($in_equip_id, 'ts_jz11');
                $tsjz12 += self::get($in_equip_id, 'ts_jz12');
                $tsjz13 += self::get($in_equip_id, 'ts_jz13');
            }
            $in_equip_id = equip::get_user_in_equip_sz($equip_num, $user_id);
            if ($in_equip_id) {
                $hp += self::get($in_equip_id, 'hp');
                $pg += self::get($in_equip_id, 'pugong');
                $pf += self::get($in_equip_id, 'pufang');
                $tg += self::get($in_equip_id, 'tegong');
                $tf += self::get($in_equip_id, 'tefang');
                $mj += self::get($in_equip_id, 'minjie');
                $mz += self::get($in_equip_id, 'mz');
                $xx += self::get($in_equip_id, 'xx');
                $zd += self::get($in_equip_id, 'zd');
                $kd += self::get($in_equip_id, 'kd');
                $bj += self::get($in_equip_id, 'bj');
                $kb += self::get($in_equip_id, 'kb');
                $mb += self::get($in_equip_id, 'mb');
                $km += self::get($in_equip_id, 'km');
                $mp += self::get($in_equip_id, 'mp');
                $hf += self::get($in_equip_id, 'zb_hf');
                $dy += self::get($in_equip_id, 'zb_dy');
                $km += self::get($in_equip_id, 'km');
                $ct += self::get($in_equip_id, 'is_ct');
                $exp += self::get($in_equip_id, 'exp');
                $tsjz1 += self::get($in_equip_id, 'ts_jz1');
                $tsjz2 += self::get($in_equip_id, 'ts_jz2');
                $tsjz3 += self::get($in_equip_id, 'ts_jz3');
                $tsjz4 += self::get($in_equip_id, 'ts_jz4');
                $tsjz5 += self::get($in_equip_id, 'ts_jz5');
                $tsjz6 += self::get($in_equip_id, 'ts_jz6');
                $tsjz7 += self::get($in_equip_id, 'ts_jz7');
                $tsjz8 += self::get($in_equip_id, 'ts_jz8');
                $tsjz9 += self::get($in_equip_id, 'ts_jz9');
                $tsjz10 += self::get($in_equip_id, 'ts_jz10');
                $tsjz11 += self::get($in_equip_id, 'ts_jz11');
                $tsjz12 += self::get($in_equip_id, 'ts_jz12');
                $tsjz13 += self::get($in_equip_id, 'ts_jz13');
            }
            $in_equip_id = equip::get_user_in_equip_cb($equip_num, $user_id);
            if ($in_equip_id) {
                $hp += self::get($in_equip_id, 'hp');
                $pg += self::get($in_equip_id, 'pugong');
                $pf += self::get($in_equip_id, 'pufang');
                $tg += self::get($in_equip_id, 'tegong');
                $tf += self::get($in_equip_id, 'tefang');
                $mj += self::get($in_equip_id, 'minjie');
                $mz += self::get($in_equip_id, 'mz');
                $xx += self::get($in_equip_id, 'xx');
                $zd += self::get($in_equip_id, 'zd');
                $kd += self::get($in_equip_id, 'kd');
                $bj += self::get($in_equip_id, 'bj');
                $kb += self::get($in_equip_id, 'kb');
                $mb += self::get($in_equip_id, 'mb');
                $km += self::get($in_equip_id, 'km');
                $mp += self::get($in_equip_id, 'mp');
                $hf += self::get($in_equip_id, 'zb_hf');
                $dy += self::get($in_equip_id, 'zb_dy');
                $km += self::get($in_equip_id, 'km');
                $ct += self::get($in_equip_id, 'is_ct');
                $exp += self::get($in_equip_id, 'exp');
                $tsjz1 += self::get($in_equip_id, 'ts_jz1');
                $tsjz2 += self::get($in_equip_id, 'ts_jz2');
                $tsjz3 += self::get($in_equip_id, 'ts_jz3');
                $tsjz4 += self::get($in_equip_id, 'ts_jz4');
                $tsjz5 += self::get($in_equip_id, 'ts_jz5');
                $tsjz6 += self::get($in_equip_id, 'ts_jz6');
                $tsjz7 += self::get($in_equip_id, 'ts_jz7');
                $tsjz8 += self::get($in_equip_id, 'ts_jz8');
                $tsjz9 += self::get($in_equip_id, 'ts_jz9');
                $tsjz10 += self::get($in_equip_id, 'ts_jz10');
                $tsjz11 += self::get($in_equip_id, 'ts_jz11');
                $tsjz12 += self::get($in_equip_id, 'ts_jz12');
                $tsjz13 += self::get($in_equip_id, 'ts_jz13');
            }
        }
        $jc = 0;
        switch ($liuwei) {
            case 'hp':
                $jc = $hp;
                break;
            case 'mp':
                $jc = $mp;
                break;
            case 'pugong':
                $jc = $pg;
                break;
            case 'pufang':
                $jc = $pf;
                break;
            case 'tegong':
                $jc = $tg;
                break;
            case 'tefang':
                $jc = $tf;
                break;
            case 'minjie':
                $jc = $mj;
                break;
            case 'mz':
                $jc = $mz;
                break;
            case 'mb':
                $jc = $mb;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'xx':
                $jc = $xx;
                break;
            case 'zd':
                $jc = $zd;
                break;
            case 'kd':
                $jc = $kd;
                break;
            case 'bj':
                $jc = $bj;
                break;
            case 'kb':
                $jc = $kb;
                break;
            case 'zb_hf':
                $jc = $hf;
                break;
            case 'zb_dy':
                $jc = $dy;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'exp':
                $jc = $exp;
                break;
            case 'is_ct':
                $jc = $ct;
                break;
            case 'ts_jz1':
                $jc = $tsjz1;
                break;
            case 'ts_jz2':
                $jc = $tsjz2;
                break;
            case 'ts_jz3':
                $jc = $tsjz3;
                break;
            case 'ts_jz4':
                $jc = $tsjz4;
                break;
            case 'ts_jz5':
                $jc = $tsjz5;
                break;
            case 'ts_jz6':
                $jc = $tsjz6;
                break;
            case 'ts_jz7':
                $jc = $tsjz7;
                break;
            case 'ts_jz8':
                $jc = $tsjz8;
                break;
            case 'ts_jz9':
                $jc = $tsjz9;
                break;
            case 'ts_jz10':
                $jc = $tsjz10;
                break;
            case 'ts_jz11':
                $jc = $tsjz11;
                break;
            case 'ts_jz12':
                $jc = $tsjz12;
                break;
            case 'ts_jz13':
                $jc = $tsjz13;
                break;
        }
        return $jc;
    }

    static function get_user_equip_jc1($liuwei, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $hp = 0;
        $pg = 0;
        $pf = 0;
        $tg = 0;
        $tf = 0;
        $mj = 0;
        $mp = 0;
        $mz = 0;
        $xx = 0;
        $mb = 0;
        $kb = 0;
        $zd = 0;
        $kd = 0;
        $bj = 0;
        $hf = 0;
        $dy = 0;
        $km = 0;
        $ct = 0;
        $tsjz1  = 0;
        $tsjz2  = 0;
        $tsjz3  = 0;
        $tsjz4  = 0;
        $tsjz5  = 0;
        $tsjz6  = 0;
        $tsjz7  = 0;
        $tsjz8  = 0;
        $tsjz9  = 0;
        $tsjz10  = 0;
        $tsjz11  = 0;
        $tsjz12  = 0;
        $tsjz13  = 0;
        $equip_subtype_arr = config::getConfigByName("equip_subtype");
        foreach ($equip_subtype_arr as $equip_num => $equip_subtype) {
            $in_equip_id = equip::get_user_in_equip($equip_num, $user_id);
            if ($in_equip_id) {
                $hp += self::get($in_equip_id, 'hp');
                $pg += self::get($in_equip_id, 'pugong');
                $pf += self::get($in_equip_id, 'pufang');
                $tg += self::get($in_equip_id, 'tegong');
                $tf += self::get($in_equip_id, 'tefang');
                $mj += self::get($in_equip_id, 'minjie');
                $mz += self::get($in_equip_id, 'mz');
                $xx += self::get($in_equip_id, 'xx');
                $zd += self::get($in_equip_id, 'zd');
                $kd += self::get($in_equip_id, 'kd');
                $bj += self::get($in_equip_id, 'bj');
                $mb += self::get($in_equip_id, 'mb');
                $km += self::get($in_equip_id, 'km');
                $mp += self::get($in_equip_id, 'mp');
                $hf += self::get($in_equip_id, 'zb_hf');
                $dy += self::get($in_equip_id, 'zb_dy');
                $km += self::get($in_equip_id, 'km');
                $ct += self::get($in_equip_id, 'is_ct');
                $tsjz1 += self::get($in_equip_id, 'ts_jz1');
                $tsjz2 += self::get($in_equip_id, 'ts_jz2');
                $tsjz3 += self::get($in_equip_id, 'ts_jz3');
                $tsjz4 += self::get($in_equip_id, 'ts_jz4');
                $tsjz5 += self::get($in_equip_id, 'ts_jz5');
                $tsjz6 += self::get($in_equip_id, 'ts_jz6');
                $tsjz7 += self::get($in_equip_id, 'ts_jz7');
                $tsjz8 += self::get($in_equip_id, 'ts_jz8');
                $tsjz9 += self::get($in_equip_id, 'ts_jz9');
                $tsjz10 += self::get($in_equip_id, 'ts_jz10');
                $tsjz11 += self::get($in_equip_id, 'ts_jz11');
                $tsjz12 += self::get($in_equip_id, 'ts_jz12');
                $tsjz13 += self::get($in_equip_id, 'ts_jz13');
            }
        }
        $jc = 0;
        switch ($liuwei) {
            case 'hp':
                $jc = $hp;
                break;
            case 'mp':
                $jc = $mp;
                break;
            case 'pugong':
                $jc = $pg;
                break;
            case 'pufang':
                $jc = $pf;
                break;
            case 'tegong':
                $jc = $tg;
                break;
            case 'tefang':
                $jc = $tf;
                break;
            case 'minjie':
                $jc = $mj;
                break;
            case 'mz':
                $jc = $mz;
                break;
            case 'mb':
                $jc = $mb;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'xx':
                $jc = $xx;
                break;
            case 'zd':
                $jc = $zd;
                break;
            case 'kd':
                $jc = $kd;
                break;
            case 'bj':
                $jc = $bj;
                break;
            case 'zb_hf':
                $jc = $hf;
                break;
            case 'zb_dy':
                $jc = $dy;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'is_ct':
                $jc = $ct;
                break;
            case 'ts_jz1':
                $jc = $tsjz1;
                break;
            case 'ts_jz2':
                $jc = $tsjz2;
                break;
            case 'ts_jz3':
                $jc = $tsjz3;
                break;
            case 'ts_jz4':
                $jc = $tsjz4;
                break;
            case 'ts_jz5':
                $jc = $tsjz5;
                break;
            case 'ts_jz6':
                $jc = $tsjz6;
                break;
            case 'ts_jz7':
                $jc = $tsjz7;
                break;
            case 'ts_jz8':
                $jc = $tsjz8;
                break;
            case 'ts_jz9':
                $jc = $tsjz9;
                break;
            case 'ts_jz10':
                $jc = $tsjz10;
                break;
            case 'ts_jz11':
                $jc = $tsjz11;
                break;
            case 'ts_jz12':
                $jc = $tsjz12;
                break;
            case 'ts_jz13':
                $jc = $tsjz13;
                break;
        }
        return $jc;
    }

    static function get_user_equip_jc2($liuwei, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $hp = 0;
        $pg = 0;
        $pf = 0;
        $tg = 0;
        $tf = 0;
        $mj = 0;
        $mp = 0;
        $mz = 0;
        $xx = 0;
        $mb = 0;
        $kb = 0;
        $zd = 0;
        $kd = 0;
        $bj = 0;
        $hf = 0;
        $dy = 0;
        $km = 0;
        $ct = 0;
        $tsjz1  = 0;
        $tsjz2  = 0;
        $tsjz3  = 0;
        $tsjz4  = 0;
        $tsjz5  = 0;
        $tsjz6  = 0;
        $tsjz7  = 0;
        $tsjz8  = 0;
        $tsjz9  = 0;
        $tsjz10  = 0;
        $tsjz11  = 0;
        $tsjz12  = 0;
        $tsjz13  = 0;
        $equip_subtype_arr = config::getConfigByName("equip_subtype");
        foreach ($equip_subtype_arr as $equip_num => $equip_subtype) {
            $in_equip_id = equip::get_user_in_equip_sz($equip_num, $user_id);
            if ($in_equip_id) {
                $hp += self::get($in_equip_id, 'hp');
                $pg += self::get($in_equip_id, 'pugong');
                $pf += self::get($in_equip_id, 'pufang');
                $tg += self::get($in_equip_id, 'tegong');
                $tf += self::get($in_equip_id, 'tefang');
                $mj += self::get($in_equip_id, 'minjie');
                $mz += self::get($in_equip_id, 'mz');
                $xx += self::get($in_equip_id, 'xx');
                $zd += self::get($in_equip_id, 'zd');
                $kd += self::get($in_equip_id, 'kd');
                $bj += self::get($in_equip_id, 'bj');
                $mb += self::get($in_equip_id, 'mb');
                $km += self::get($in_equip_id, 'km');
                $mp += self::get($in_equip_id, 'mp');
                $hf += self::get($in_equip_id, 'zb_hf');
                $dy += self::get($in_equip_id, 'zb_dy');
                $km += self::get($in_equip_id, 'km');
                $ct += self::get($in_equip_id, 'is_ct');
                $tsjz1 += self::get($in_equip_id, 'ts_jz1');
                $tsjz2 += self::get($in_equip_id, 'ts_jz2');
                $tsjz3 += self::get($in_equip_id, 'ts_jz3');
                $tsjz4 += self::get($in_equip_id, 'ts_jz4');
                $tsjz5 += self::get($in_equip_id, 'ts_jz5');
                $tsjz6 += self::get($in_equip_id, 'ts_jz6');
                $tsjz7 += self::get($in_equip_id, 'ts_jz7');
                $tsjz8 += self::get($in_equip_id, 'ts_jz8');
                $tsjz9 += self::get($in_equip_id, 'ts_jz9');
                $tsjz10 += self::get($in_equip_id, 'ts_jz10');
                $tsjz11 += self::get($in_equip_id, 'ts_jz11');
                $tsjz12 += self::get($in_equip_id, 'ts_jz12');
                $tsjz13 += self::get($in_equip_id, 'ts_jz13');
            }
        }
        $jc = 0;
        switch ($liuwei) {
            case 'hp':
                $jc = $hp;
                break;
            case 'mp':
                $jc = $mp;
                break;
            case 'pugong':
                $jc = $pg;
                break;
            case 'pufang':
                $jc = $pf;
                break;
            case 'tegong':
                $jc = $tg;
                break;
            case 'tefang':
                $jc = $tf;
                break;
            case 'minjie':
                $jc = $mj;
                break;
            case 'mz':
                $jc = $mz;
                break;
            case 'mb':
                $jc = $mb;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'xx':
                $jc = $xx;
                break;
            case 'zd':
                $jc = $zd;
                break;
            case 'kd':
                $jc = $kd;
                break;
            case 'bj':
                $jc = $bj;
                break;
            case 'zb_hf':
                $jc = $hf;
                break;
            case 'zb_dy':
                $jc = $dy;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'is_ct':
                $jc = $ct;
                break;
            case 'ts_jz1':
                $jc = $tsjz1;
                break;
            case 'ts_jz2':
                $jc = $tsjz2;
                break;
            case 'ts_jz3':
                $jc = $tsjz3;
                break;
            case 'ts_jz4':
                $jc = $tsjz4;
                break;
            case 'ts_jz5':
                $jc = $tsjz5;
                break;
            case 'ts_jz6':
                $jc = $tsjz6;
                break;
            case 'ts_jz7':
                $jc = $tsjz7;
                break;
            case 'ts_jz8':
                $jc = $tsjz8;
                break;
            case 'ts_jz9':
                $jc = $tsjz9;
                break;
            case 'ts_jz10':
                $jc = $tsjz10;
                break;
            case 'ts_jz11':
                $jc = $tsjz11;
                break;
            case 'ts_jz12':
                $jc = $tsjz12;
                break;
            case 'ts_jz13':
                $jc = $tsjz13;
                break;
        }
        return $jc;
    }

    static function get_user_equip_jc_cb($liuwei, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $hp = 0;
        $pg = 0;
        $pf = 0;
        $tg = 0;
        $tf = 0;
        $mj = 0;
        $mp = 0;
        $mz = 0;
        $xx = 0;
        $mb = 0;
        $kb = 0;
        $zd = 0;
        $kd = 0;
        $bj = 0;
        $hf = 0;
        $dy = 0;
        $km = 0;
        $ct = 0;
        $tsjz1  = 0;
        $tsjz2  = 0;
        $tsjz3  = 0;
        $tsjz4  = 0;
        $tsjz5  = 0;
        $tsjz6  = 0;
        $tsjz7  = 0;
        $tsjz8  = 0;
        $tsjz9  = 0;
        $tsjz10  = 0;
        $tsjz11  = 0;
        $tsjz12  = 0;
        $tsjz13  = 0;
        $equip_subtype_arr = config::getConfigByName("equip_subtype");
        foreach ($equip_subtype_arr as $equip_num => $equip_subtype) {
            $in_equip_id = equip::get_user_in_equip_cb($equip_num, $user_id);
            if ($in_equip_id && $equip_num == 1) {
                $hp += self::get($in_equip_id, 'hp');
                $pg += self::get($in_equip_id, 'pugong');
                $pf += self::get($in_equip_id, 'pufang');
                $tg += self::get($in_equip_id, 'tegong');
                $tf += self::get($in_equip_id, 'tefang');
                $mj += self::get($in_equip_id, 'minjie');
                $mz += self::get($in_equip_id, 'mz');
                $xx += self::get($in_equip_id, 'xx');
                $zd += self::get($in_equip_id, 'zd');
                $kd += self::get($in_equip_id, 'kd');
                $bj += self::get($in_equip_id, 'bj');
                $mb += self::get($in_equip_id, 'mb');
                $km += self::get($in_equip_id, 'km');
                $mp += self::get($in_equip_id, 'mp');
                $hf += self::get($in_equip_id, 'zb_hf');
                $dy += self::get($in_equip_id, 'zb_dy');
                $km += self::get($in_equip_id, 'km');
                $ct += self::get($in_equip_id, 'is_ct');
                $tsjz1 += self::get($in_equip_id, 'ts_jz1');
                $tsjz2 += self::get($in_equip_id, 'ts_jz2');
                $tsjz3 += self::get($in_equip_id, 'ts_jz3');
                $tsjz4 += self::get($in_equip_id, 'ts_jz4');
                $tsjz5 += self::get($in_equip_id, 'ts_jz5');
                $tsjz6 += self::get($in_equip_id, 'ts_jz6');
                $tsjz7 += self::get($in_equip_id, 'ts_jz7');
                $tsjz8 += self::get($in_equip_id, 'ts_jz8');
                $tsjz9 += self::get($in_equip_id, 'ts_jz9');
                $tsjz10 += self::get($in_equip_id, 'ts_jz10');
                $tsjz11 += self::get($in_equip_id, 'ts_jz11');
                $tsjz12 += self::get($in_equip_id, 'ts_jz12');
                $tsjz13 += self::get($in_equip_id, 'ts_jz13');
            }
        }
        $jc = 0;
        switch ($liuwei) {
            case 'hp':
                $jc = $hp;
                break;
            case 'mp':
                $jc = $mp;
                break;
            case 'pugong':
                $jc = $pg;
                break;
            case 'pufang':
                $jc = $pf;
                break;
            case 'tegong':
                $jc = $tg;
                break;
            case 'tefang':
                $jc = $tf;
                break;
            case 'minjie':
                $jc = $mj;
                break;
            case 'mz':
                $jc = $mz;
                break;
            case 'mb':
                $jc = $mb;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'xx':
                $jc = $xx;
                break;
            case 'zd':
                $jc = $zd;
                break;
            case 'kd':
                $jc = $kd;
                break;
            case 'bj':
                $jc = $bj;
                break;
            case 'zb_hf':
                $jc = $hf;
                break;
            case 'zb_dy':
                $jc = $dy;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'is_ct':
                $jc = $ct;
                break;
            case 'ts_jz1':
                $jc = $tsjz1;
                break;
            case 'ts_jz2':
                $jc = $tsjz2;
                break;
            case 'ts_jz3':
                $jc = $tsjz3;
                break;
            case 'ts_jz4':
                $jc = $tsjz4;
                break;
            case 'ts_jz5':
                $jc = $tsjz5;
                break;
            case 'ts_jz6':
                $jc = $tsjz6;
                break;
            case 'ts_jz7':
                $jc = $tsjz7;
                break;
            case 'ts_jz8':
                $jc = $tsjz8;
                break;
            case 'ts_jz9':
                $jc = $tsjz9;
                break;
            case 'ts_jz10':
                $jc = $tsjz10;
                break;
            case 'ts_jz11':
                $jc = $tsjz11;
                break;
            case 'ts_jz12':
                $jc = $tsjz12;
                break;
            case 'ts_jz13':
                $jc = $tsjz13;
                break;
        }
        return $jc;
    }

    static function get_user_equip_jc_cb1($liuwei, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $hp = 0;
        $pg = 0;
        $pf = 0;
        $tg = 0;
        $tf = 0;
        $mj = 0;
        $mp = 0;
        $mz = 0;
        $xx = 0;
        $mb = 0;
        $kb = 0;
        $zd = 0;
        $kd = 0;
        $bj = 0;
        $hf = 0;
        $dy = 0;
        $km = 0;
        $ct = 0;
        $tsjz1  = 0;
        $tsjz2  = 0;
        $tsjz3  = 0;
        $tsjz4  = 0;
        $tsjz5  = 0;
        $tsjz6  = 0;
        $tsjz7  = 0;
        $tsjz8  = 0;
        $tsjz9  = 0;
        $tsjz10  = 0;
        $tsjz11  = 0;
        $tsjz12  = 0;
        $tsjz13  = 0;
        $equip_subtype_arr = config::getConfigByName("equip_subtype");
        foreach ($equip_subtype_arr as $equip_num => $equip_subtype) {
            $in_equip_id = equip::get_user_in_equip_cb($equip_num, $user_id);
            if ($in_equip_id && $equip_num > 1) {
                $hp += self::get($in_equip_id, 'hp');
                $pg += self::get($in_equip_id, 'pugong');
                $pf += self::get($in_equip_id, 'pufang');
                $tg += self::get($in_equip_id, 'tegong');
                $tf += self::get($in_equip_id, 'tefang');
                $mj += self::get($in_equip_id, 'minjie');
                $mz += self::get($in_equip_id, 'mz');
                $xx += self::get($in_equip_id, 'xx');
                $zd += self::get($in_equip_id, 'zd');
                $kd += self::get($in_equip_id, 'kd');
                $bj += self::get($in_equip_id, 'bj');
                $mb += self::get($in_equip_id, 'mb');
                $km += self::get($in_equip_id, 'km');
                $mp += self::get($in_equip_id, 'mp');
                $hf += self::get($in_equip_id, 'zb_hf');
                $dy += self::get($in_equip_id, 'zb_dy');
                $km += self::get($in_equip_id, 'km');
                $ct += self::get($in_equip_id, 'is_ct');
                $tsjz1 += self::get($in_equip_id, 'ts_jz1');
                $tsjz2 += self::get($in_equip_id, 'ts_jz2');
                $tsjz3 += self::get($in_equip_id, 'ts_jz3');
                $tsjz4 += self::get($in_equip_id, 'ts_jz4');
                $tsjz5 += self::get($in_equip_id, 'ts_jz5');
                $tsjz6 += self::get($in_equip_id, 'ts_jz6');
                $tsjz7 += self::get($in_equip_id, 'ts_jz7');
                $tsjz8 += self::get($in_equip_id, 'ts_jz8');
                $tsjz9 += self::get($in_equip_id, 'ts_jz9');
                $tsjz10 += self::get($in_equip_id, 'ts_jz10');
                $tsjz11 += self::get($in_equip_id, 'ts_jz11');
                $tsjz12 += self::get($in_equip_id, 'ts_jz12');
                $tsjz13 += self::get($in_equip_id, 'ts_jz13');
            }
        }
        $jc = 0;
        switch ($liuwei) {
            case 'hp':
                $jc = $hp;
                break;
            case 'mp':
                $jc = $mp;
                break;
            case 'pugong':
                $jc = $pg;
                break;
            case 'pufang':
                $jc = $pf;
                break;
            case 'tegong':
                $jc = $tg;
                break;
            case 'tefang':
                $jc = $tf;
                break;
            case 'minjie':
                $jc = $mj;
                break;
            case 'mz':
                $jc = $mz;
                break;
            case 'mb':
                $jc = $mb;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'xx':
                $jc = $xx;
                break;
            case 'zd':
                $jc = $zd;
                break;
            case 'kd':
                $jc = $kd;
                break;
            case 'bj':
                $jc = $bj;
                break;
            case 'zb_hf':
                $jc = $hf;
                break;
            case 'zb_dy':
                $jc = $dy;
                break;
            case 'km':
                $jc = $km;
                break;
            case 'is_ct':
                $jc = $ct;
                break;
            case 'ts_jz1':
                $jc = $tsjz1;
                break;
            case 'ts_jz2':
                $jc = $tsjz2;
                break;
            case 'ts_jz3':
                $jc = $tsjz3;
                break;
            case 'ts_jz4':
                $jc = $tsjz4;
                break;
            case 'ts_jz5':
                $jc = $tsjz5;
                break;
            case 'ts_jz6':
                $jc = $tsjz6;
                break;
            case 'ts_jz7':
                $jc = $tsjz7;
                break;
            case 'ts_jz8':
                $jc = $tsjz8;
                break;
            case 'ts_jz9':
                $jc = $tsjz9;
                break;
            case 'ts_jz10':
                $jc = $tsjz10;
                break;
            case 'ts_jz11':
                $jc = $tsjz11;
                break;
            case 'ts_jz12':
                $jc = $tsjz12;
                break;
            case 'ts_jz13':
                $jc = $tsjz13;
                break;
        }
        return $jc;
    }

    //获取玩家装备加成
    static function get_user_equip_tz($tz, $user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        $tz_sz = 0;
        $equip_subtype_arr = config::getConfigByName("equip_subtype");
        foreach ($equip_subtype_arr as $equip_num => $equip_subtype) {
            $in_equip_id = equip::get_user_in_equip($equip_num, $user_id);
            if ($in_equip_id && $tz == self::get($in_equip_id, 'tz_tz') && $tz > 0) {
                $tz_sz += 1;
            }
        }
        $tz_sz += equip::get_user_equip_jc1('ts_jz13');
        return $tz_sz;
    }

    static function get_user_equip_tz1($tz, $user_id)
    {
        $tz_sz = 0;
        $equip_subtype_arr = config::getConfigByName("equip_subtype");
        foreach ($equip_subtype_arr as $equip_num => $equip_subtype) {
            $in_equip_id = equip::get_user_in_equip($equip_num, $user_id);
            if ($in_equip_id && $tz == self::get($in_equip_id, 'tz_tz') && $tz > 0) {
                $tz_sz += 1;
            }
        }
        return $tz_sz;
    }
    //展示装备
    static function show_equip($equipID, $mode = 0)
    {
        self::show_prop($equipID, 10);
        switch ($mode) {
            case 0:
                //查看自己
                $leixing = value::get_game_prop_value($equipID, 'leixing');
                if ($leixing == 2) {
                    cmd::addcmd("e10004,$equipID", "卸下" . self::get($equipID, 'name'));
                    br();
                    cmd::add_last_cmd("map_zb");
                }
                if ($leixing == 3) {
                    cmd::addcmd("e10004_sz,$equipID", "卸下" . self::get($equipID, 'name'));
                    br();
                    cmd::add_last_cmd("map_zb1");
                }
                break;
            case 1:
                //查看玩家
                cmd::add_last_cmd("e12");
                break;
            case 2:
                //查看排行
                cmd::add_last_cmd("e47");
                break;
            case 3:
                //地图查看
                cmd::add_last_cmd("e0");
                break;
        }
    }

    //装备类型 数字转文本
    static function leixing_int2str($type_int)
    {
        $equip_subtype_arr = config::getConfigByName("equip_subtype");
        return $equip_subtype_arr[$type_int];
    }
}

//队伍类
class team
{
    //创建队伍
    static function new_team($user_id)
    {
        //开始创建队伍
        $sql = "INSERT INTO `game_team` (`captain_user_id`) VALUES ('{$user_id}')";
        sql($sql);
        $result = sql("SELECT LAST_INSERT_ID()");
        list($new_team_id) = $result->fetch_row();
        return $new_team_id;
    }

    //获取队伍ID
    static function get_user_team($user_id = 0)
    {
        if (!$user_id) {
            $user_id = uid();
        }
        return value::get_game_user_value('team_id', $user_id);
    }

    //获取队伍成员数量
    static function get_team_user_count($team_id = 0)
    {
        if ($team_id) {
            $sql = "SELECT COUNT(*) FROM `game_user` WHERE `team_id`={$team_id}";
            $result = sql($sql);
            list($user_count) = $result->fetch_row();
            return $user_count;
        } else {
            return 0;
        }
    }

    //获取队伍成员
    static function get_team_user($team_id, $need_self = true)
    {
        if ($team_id) {
            $add_sql = "";
            $user_arr = array();
            if (!$need_self) {
                $add_sql = "AND `id` != " . uid();
            }
            $sql = "SELECT `id` FROM `game_user` WHERE `team_id`={$team_id} $add_sql LIMIT 5";
            $result = sql($sql);
            while (list($user_id) = $result->fetch_row()) {
                array_push($user_arr, $user_id);
            }
            return $user_arr;
        } else {
            return false;
        }
    }

    //获取队长ID
    static function get_team_captain_user_id($team_id)
    {
        $sql = "SELECT `captain_user_id` FROM `game_team` WHERE `id`={$team_id} LIMIT 1";
        $result = sql($sql);
        list($captain_user_id) = $result->fetch_row();
        return $captain_user_id;
    }

    //设置队长ID
    static function set_team_captain_user_id($team_id, $user_id)
    {
        $sql = "UPDATE `game_team` SET `captain_user_id`={$user_id} WHERE `id`={$team_id} LIMIT 1";
        sql($sql);
    }

    //退出队伍
    static function exit_team($user_id)
    {
        //获取队伍ID
        $team_id = self::get_user_team($user_id);
        $dy_name = value::get_game_user_value('tp', $user_id);
        $hy_xs_lvl = value::get_user_value('hy_lvl', $user_id);
        if ($hy_xs_lvl < 1) {
            $tp = $dy_name;
        }
        if ($hy_xs_lvl == 1) {
            $tp = "<span style=color:red>{$dy_name}</span>";
        }
        if ($hy_xs_lvl == 2) {
            $tp = "<span style=color:green>{$dy_name}</span>";
        }
        if ($hy_xs_lvl == 3) {
            $tp = "<span style=color:purple>{$dy_name}</span>";
        }
        value::set_game_user_value('name', $tp, $user_id);
        //队伍是否存在
        if ($team_id) {
            //队长ID
            $captain_user_id = self::get_team_captain_user_id($team_id);
            if (self::get_team_user_count($team_id) > 1) {
                //队长退出
                if ($captain_user_id == $user_id) {
                    //获取新队长
                    $sql = "SELECT `id` FROM `game_user` WHERE `team_id`={$team_id} AND `id`!={$captain_user_id} LIMIT 1";
                    $result = sql($sql);
                    list($new_duizhang_id) = $result->fetch_row();
                    self::set_team_captain_user_id($team_id, $new_duizhang_id);
                    //新队长通知
                    $new_duizhang_name = value::get_game_user_value('name', $new_duizhang_id);
                    c_add_xiaoxi("{$new_duizhang_name}是新的队长。", 18, $new_duizhang_id, $team_id);
                }
                //退出消息
                c_add_xiaoxi("大家玩,我先走了。", 18, $user_id, $team_id);
            }
            //退出组队副本
            $map_id = value::get_game_user_value('in_map_id', $user_id);
            if (value::get_map_value($map_id, 'is_duorenfuben')) {
                //退出副本消息
                c_add_xiaoxi("你退出了副本!", 0, 0, $user_id);
                //副本区域ID
                $area_id = value::get_map_value($map_id, 'area_id');
                //山间小路副本
                if ($area_id == 9) {
                    //返回山间小路
                    value::set_game_user_value('in_map_id', 1, $user_id);
                }
                //天枢星坛副本
                if ($area_id == 22) {
                    //返回天枢星坛
                    value::set_game_user_value('in_map_id', 1, $user_id);
                }
            }
            //玩家退出队伍
            value::set_game_user_value('team_id', 0, $user_id);
            //最后玩家 解散队伍
            if (self::get_team_user_count($team_id) == 1) {
                //获取队伍最后玩家ID
                $sql = "SELECT `id` FROM `game_user` WHERE `team_id`={$team_id} LIMIT 1";
                $result = sql($sql);
                list($only_user_id) = $result->fetch_row();
                //告知组队解散消息
                c_add_xiaoxi("你的小队已经解散了。", 0, 0, $only_user_id);
                //判断该玩家是否正在挑战NPC
                if (value::get_game_user_value('in_ctm', $only_user_id) == 'ctm_pk_team_npc') {
                    sql("UPDATE `game_pet` SET `enemy_user`=$only_user_id WHERE `team_id`={$team_id} LIMIT 5");
                    value::set_game_user_value('in_ctm', 'ctm_pk_npc', $only_user_id);
                } else {
                    $sql = "SELECT `id` FROM `game_pet` WHERE `team_id`={$team_id} LIMIT 5";
                    $rs = sql($sql);
                    while (list($d_pet_id) = $rs->fetch_row()) {
                        pet::del_pet($d_pet_id);
                    }
                }
                //最后一人退出队伍
                self::exit_team($only_user_id);
                //删除队伍
                self::del_team($team_id);
            }
        }
    }

    //删除队伍
    static function del_team($team_id)
    {
        //删除队伍数据
        $sql = "DELETE FROM `game_team` WHERE `id`={$team_id} LIMIT 1";
        sql($sql);
        //重置玩家队伍ID
        $sql = "UPDATE `game_user` SET `team_id`=0 WHERE `team_id`={$team_id} LIMIT 5";
        sql($sql);
        //删除组队频道聊天
        sql("DELETE FROM `game_chat` WHERE `mode`=18 AND `oid`={$team_id}");
    }
}

//帮派类
class union
{
    //获取帮派table列名数组
    static function get_table_col_arr()
    {
        $col_arr = array();
        $sql = "DESC `game_union`;";
        $rs = sql($sql);
        while (list($col_name) = $rs->fetch_row()) {
            array_push($col_arr, $col_name);
        }
        return $col_arr;
    }

    //是否有帮派权限
    static function can_guanli()
    {
        $union_lvl = value::get_game_user_value('gonghui.lvl');
        if ($union_lvl < 2) {
            return true;
        } else {
            echo "无权操作!";
            br();
            return false;
        }
    }

    //获取帮派类型
    static function get_union_type_name($union_id)
    {
        return self::get($union_id, 'type');
    }

    //获取帮派类型名称
    static function get_type_name($type = 0)
    {
        $type_arr = array();
        $type_arr[1] = "军";
        $type_arr[2] = "帮";
        $type_arr[3] = "谷";
        $type_arr[4] = "寺";
        $type_arr[5] = "教";
        $type_arr[6] = "会";
        $type_arr[7] = "剑派";
        $type_arr[8] = "学院";
        $type_arr[9] = "道观";
        $type_arr[10] = "山庄";
        if ($type) {
            return $type_arr[$type];
        } else {
            return $type_arr;
        }
    }

    //获取帮派头衔
    static function get_touxian($num = 0)
    {
        $touxian_arr = config::getConfigByName("union_position");
        if ($num) {
            return $touxian_arr[$num];
        } else {
            return $touxian_arr;
        }
    }

    //获取建筑数组
    static function get_jianzhu()
    {
        $jianzhu = array("sbk" => "神兵库", "tjp" => "铁匠铺", "hyc" => "合成池");
        if (value::get_system_value("is_qiangyaotai_open")) {
            $jianzhu["qyt"] = "强妖台";
        }
        return $jianzhu;
    }

    //获取帮派属性
    static function get($union_id, $value_name)
    {
        if (in_array($value_name, self::get_table_col_arr())) {
            return value::getvalue('game_union', $value_name, 'id', $union_id);
        } else {
            return value::getvalue('game_union_value', 'value', json_encode(array("union_id" => $union_id, "valuename" => $value_name)));
        }
    }

    //设置帮派属性
    static function set($union_id, $value_name, $value)
    {
        if (in_array($value_name, self::get_table_col_arr())) {
            return value::setvalue('game_union', $value_name, $value, 'id', $union_id);
        } else {
            if (value::getvalue('game_union_value', 'value', json_encode(array("union_id" => $union_id, "valuename" => $value_name))) != "") {
                value::setvalue('game_union_value', 'value', $value, json_encode(array("union_id" => $union_id, "valuename" => $value_name)));
            } else {
                self::insert($union_id, $value_name, $value);
            }
            return $value;
        }
    }

    //增加帮派属性
    static function add($union_id, $value_name, $value)
    {
        if (in_array($value_name, self::get_table_col_arr())) {
            return value::addvalue('game_union', $value_name, $value, 'id', $union_id);
        } else {
            return self::set($union_id, $value_name, $value + self::get($union_id, $value_name));
        }
    }

    //插入帮派属性
    static function insert($union_id, $value_name, $value)
    {
        return value::insert('game_union_value', "`union_id`,`valuename`,`value`", "'{$union_id}','{$value_name}','{$value}'");
    }

    //获取帮派名称
    static function get_name($union_id)
    {
        return self::get($union_id, 'name') . self::get_type_name(self::get($union_id, 'type'));
    }

    //获取帮派会长
    static function get_huizhang($union_id)
    {
        $sql = "SELECT `id` FROM `game_user` WHERE `gonghui.id`=$union_id AND `gonghui.lvl` =0 LIMIT 1";
        $rs = sql($sql);
        list($huizhang) = $rs->fetch_row();
        return $huizhang;
    }

    //增加帮派成员
    static function add_user($union_id, $user_id)
    {
        if (self::get_user_count($union_id) < self::get_max_user_count($union_id) && !user::get_union($user_id)) {
            value::set_game_user_value('gonghui.id', $union_id, $user_id);
            value::set_game_user_value('gonghui.lvl', 6, $user_id);
            value::set_game_user_value('gonghui.gongxian', 0, $user_id);
            $union_name = self::get_name($union_id);
            c_add_xiaoxi($union_name . "同意了你的入会请求!", 0, $union_id, $user_id);
            echo "同意成功!";
            br();
        } else {
            echo "帮派人数达到上限或对方已有帮派!";
            br();
            return false;
        }
    }

    //踢出帮派成员
    static function del_user($union_id, $user_id)
    {
        if (user::get_union($user_id) == $union_id) {
            value::set_game_user_value('gonghui.id', 0, $user_id);
            value::set_game_user_value('gonghui.lvl', 6, $user_id);
            value::set_game_user_value('gonghui.gongxian', 0, $user_id);
            return true;
        } else {
            return false;
        }
    }

    //获取帮派成员数量
    static function get_user_count($union_id)
    {
        $sql = "SELECT COUNT(*) FROM `game_user` WHERE `gonghui.id`=$union_id AND `gonghui.lvl`<7 LIMIT 100";
        $rs = sql($sql);
        list($user_count) = $rs->fetch_row();
        return $user_count;
    }


    //获取最大成员人数
    static function get_max_user_count($union_id)
    {
        return self::get($union_id, 'lvl') * 20;
    }

    //获取帮派成员数组
    static function get_user_arr($union_id)
    {
        $user_arr = array();
        $sql = "SELECT `id` FROM `game_user` WHERE `gonghui.id`=$union_id AND `gonghui.lvl`<7 LIMIT 100";
        $rs = sql($sql);
        while (list($user_id) = $rs->fetch_row()) {
            array_push($user_arr, $user_id);
        }
        return $user_arr;
    }

    //获取帮派管理成员数组
    static function get_guanli_arr($union_id)
    {
        $user_arr = array();
        $sql = "SELECT `id` FROM `game_user` WHERE `gonghui.id`=$union_id AND `gonghui.lvl`<2 LIMIT 100";
        $rs = sql($sql);
        while (list($user_id) = $rs->fetch_row()) {
            array_push($user_arr, $user_id);
        }
        return $user_arr;
    }

    //获取同盟帮派数组
    static function get_tongmeng($union_id, &$o_union_count = null)
    {
        $union_arr = json_decode(self::get($union_id, "tongmeng"), true);
        if (!is_array($union_arr)) {
            $union_arr = array();
        }
        $o_union_count = count($union_arr);
        return $union_arr;
    }

    //申请同盟帮派
    static function add_tongmeng($union_id, $o_union_id)
    {
        self::get_tongmeng($union_id, $o_union_count);
        if ($o_union_count > 2 || self::is_tongmeng($union_id, $o_union_id)) {
            return false;
        } else {
            self::send_msg($union_id, $o_union_id, "结盟请求");
            return true;
        }
    }

    //设置同盟帮派
    static function set_tongmeng($union_id, $o_union_id)
    {
        $union_arr = self::get_tongmeng($union_id, $union_count);
        $o_union_arr = self::get_tongmeng($o_union_id, $o_union_count);
        if ($union_count > 2 || $o_union_count > 2 || self::is_tongmeng($union_id, $o_union_id)) {
            return false;
        } else {
            //帮派名称
            $union_name = self::get_name($union_id);
            $o_union_name = self::get_name($o_union_id);
            //增加己方
            self::del_didui($union_id, $o_union_id);
            array_push($union_arr, $o_union_id);
            self::set($union_id, "tongmeng", json_encode($union_arr));
            self::send_user_msg($union_id, "本帮派同意与" . $o_union_name . "结盟!");
            //增加对方
            self::del_didui($o_union_id, $union_id);
            array_push($o_union_arr, $union_id);
            self::set($o_union_id, "tongmeng", json_encode($o_union_arr));
            self::send_user_msg($o_union_id, $union_name . "同意与本帮派结盟!");
            return true;
        }
    }

    //删除同盟帮派
    static function del_tongmeng($union_id, $o_union_id)
    {
        if (!self::is_tongmeng($union_id, $o_union_id)) {
            return false;
        } else {
            //帮派名称
            $union_name = self::get_name($union_id);
            $o_union_name = self::get_name($o_union_id);
            //删除己方
            $union_arr = self::get_tongmeng($union_id);
            foreach ($union_arr as $k => $v) {
                if ($v == $o_union_id) {
                    unset($union_arr[$k]);
                    break;
                }
            }
            self::set($union_id, "tongmeng", json_encode($union_arr));
            self::send_user_msg($union_id, "本帮派宣布取消与" . $o_union_name . "结盟!");
            //删除对方
            $union_arr = self::get_tongmeng($o_union_id);
            foreach ($union_arr as $k => $v) {
                if ($v == $union_id) {
                    unset($union_arr[$k]);
                    break;
                }
            }
            self::set($o_union_id, "tongmeng", json_encode($union_arr));
            self::send_user_msg($o_union_id, $union_name . "宣布取消与本帮派结盟!");
            return true;
        }
    }

    //是否同盟帮派
    static function is_tongmeng($union_id, $o_union_id)
    {
        $union_arr = json_decode(self::get($union_id, "tongmeng"), true);
        foreach ($union_arr as $k => $v) {
            if ($v == $o_union_id) {
                return true;
            }
        }
        return false;
    }

    //获取敌对帮派数组
    static function get_didui($union_id, &$o_union_count = null)
    {
        $union_arr = json_decode(self::get($union_id, "didui"), true);
        if (!is_array($union_arr)) {
            $union_arr = array();
        }
        $o_union_count = count($union_arr);
        return $union_arr;
    }

    //设置敌对帮派
    static function set_didui($union_id, $o_union_id)
    {
        $union_name = self::get_name($union_id);
        $o_union_name = self::get_name($o_union_id);
        $union_arr = self::get_didui($union_id, $o_union_count);
        if ($o_union_count > 2 || self::is_didui($union_id, $o_union_id)) {
            return false;
        } else {
            self::del_tongmeng($union_id, $o_union_id);
            array_push($union_arr, $o_union_id);
            self::set($union_id, "didui", json_encode($union_arr));
            self::send_user_msg($union_id, $union_name . "正式对" . $o_union_name . "宣战!");
            self::send_user_msg($o_union_id, $union_name . "正式对" . $o_union_name . "宣战!");
            return true;
        }
    }

    //删除敌对帮派
    static function del_didui($union_id, $o_union_id)
    {
        $union_name = self::get_name($union_id);
        $o_union_name = self::get_name($o_union_id);
        $union_arr = self::get_didui($union_id, $o_union_count);
        if (!self::is_didui($union_id, $o_union_id)) {
            return false;
        } else {
            foreach ($union_arr as $k => $v) {
                if ($v == $o_union_id) {
                    unset($union_arr[$k]);
                    break;
                }
            }
            self::set($union_id, "didui", json_encode($union_arr));
            self::send_user_msg($o_union_id, $union_name . "取消对" . $o_union_name . "宣战!");
            return true;
        }
    }

    //是否敌对帮派
    static function is_didui($union_id, $o_union_id)
    {
        $union_arr = json_decode(self::get($union_id, "didui"), true);
        foreach ($union_arr as $k => $v) {
            if ($v == $o_union_id) {
                return true;
            }
        }
        return false;
    }

    //通知帮派消息
    static function send_msg($union_id, $o_union_id, $msg)
    {
        c_add_xiaoxi($msg, 19, $union_id, $o_union_id);
    }

    //通知帮派管理成员消息
    static function send_guanli_msg($union_id, $msg)
    {
        $user_arr = self::get_guanli_arr($union_id);
        foreach ($user_arr as $oid) {
            c_add_xiaoxi($msg, 0, 0, $oid);
        }
    }

    //通知帮派成员消息
    static function send_user_msg($union_id, $msg)
    {
        $user_arr = self::get_user_arr($union_id);
        foreach ($user_arr as $oid) {
            c_add_xiaoxi($msg, 0, 0, $oid);
        }
    }

    //显示帮派详情
    static function show_union($union_id, $mode = 0)
    {
        $huizhang = self::get_huizhang($union_id);
        $huizhang_name = value::get_game_user_value("name", $huizhang);
        $creator_id = self::get($union_id, 'creator_id');
        $creator_name = value::get_game_user_value("name", $creator_id);
        $lvl = self::get($union_id, 'lvl');
        $user_count = self::get_user_count($union_id);
        $max_user_count = self::get_max_user_count($union_id);
        $lingqi = self::get($union_id, 'lingqi');
        $union_lingqi = $lingqi >= 1000 ? $lingqi : 0;
        $union_lingqi = $union_lingqi < 50000 ? $union_lingqi : 50000;
        $lingqi_exp = ceil($union_lingqi / 1000);
        $ghsbjy_time = self::get($union_id, "ghsbjy_time");
        $now_time = time();
        $ghsbjy_time_str = $ghsbjy_time > $now_time ? date("剩余H小时i分钟s秒", $ghsbjy_time - $now_time - 28800) : "未激活";
        $jianzhu_arr = self::get_jianzhu();
        $jianzhu_str = "";
        foreach ($jianzhu_arr as $k => $v) {
            $tlvl = (int)self::get($union_id, 'jz.' . $k . '.lvl');
            $jianzhu_str .= $v . "(" . ($tlvl ? $tlvl . "级" : "未建造") . "),";
        }
        $jianzhu_str = trim($jianzhu_str, ',');
        cmd::addcmd('e106', '帮派');
        echo "<< ";
        echo self::get_name($union_id);
        br();
        echo "-";
        br();
        echo "等级:", $lvl;
        br();
        echo "人数:", $user_count, "/", $max_user_count;
        br();
        echo "灵气:", $lingqi, "点灵气";
        br();
        echo "资金:", self::get($union_id, 'money'), "个金币";
        br();
        echo "建筑:", $jianzhu_str;
        br();
        echo "增益:", "灵气经验加成(", $lingqi_exp, "%)、帮派双倍经验(", $ghsbjy_time_str, ")";
        br();
        echo "会长:";
        if (value::get_game_user_value('is_online', $huizhang)) {
            cmd::addcmd("e12,$huizhang", $huizhang_name);
        } else {
            echo $huizhang_name;
        }
        br();
        echo "创建者:";
        if (value::get_game_user_value('is_online', $creator_id)) {
            cmd::addcmd("e12,$creator_id", $creator_name);
        } else {
            echo $creator_name;
        }
        $tongmeng_arr = self::get_tongmeng($union_id, $tongmeng_count);
        if ($tongmeng_count) {
            br();
            echo "同盟帮派:";
            $i = 0;
            foreach ($tongmeng_arr as $tongmeng_id) {
                echo $i ? "," : "";
                cmd::addcmd("e176,0," . $tongmeng_id, self::get_name($tongmeng_id));
                $i++;
            }
        }
        $didui_arr = self::get_didui($union_id, $didui_count);
        if ($didui_count) {
            br();
            echo "敌对帮派:";
            $i = 0;
            foreach ($didui_arr as $didui_id) {
                echo $i ? "," : "";
                cmd::addcmd("e176,0," . $didui_id, self::get_name($didui_id));
                $i++;
            }
        }
        br();
        echo "公告:", self::get($union_id, 'gonggao');
    }

    //强妖台 提升最大等级
    static function up_lvl($step = 0, $pet_id = 0)
    {
        $user_id = uid();
        $union_id = user::get_union($user_id);
        $qyt_lvl = union::get($union_id, 'jz.qyt.lvl');
        $max_lvl_arr = array(1 => 150, 2 => 180, 3 => 210, 4 => 250);
        if (!$step) {
            echo "你好,{$qyt_lvl}级强妖台最高可以提升宠物到{$max_lvl_arr[$qyt_lvl]}级!";
            br();
            echo "请选择要强化的三转宠物:";
            $pet_count = 0;
            $u_pet_arr = user::get_pet_arr($user_id, 1);
            foreach ($u_pet_arr as $pet_id) {
                if (pet::get($pet_id, "zhuansheng") == 3) {
                    br();
                    $is_dead = value::get_pet_value($pet_id, 'is_dead');
                    $pet_nick_name = value::getvalue('game_pet', 'nick_name', 'id', $pet_id);
                    $pet_name = value::getvalue('game_pet', 'name', 'id', $pet_id);
                    $pet_lvl = value::getvalue('game_pet', 'lvl', 'id', $pet_id);
                    cmd::addcmd('union::up_lvl,1,' . $pet_id, ($pet_nick_name ? "({$pet_nick_name})" : "") . $pet_name . '(' . $pet_lvl . '/' . pet::get($pet_id, 'max_lvl') . '级)');
                    echo $is_dead ? "(已死亡)" : "";
                    $pet_count++;
                }
            }
            if (!$pet_count) {
                br();
                echo "身上没有三转宠物。";
            }
        } elseif ($step == 1) {
            echo "你好,{$qyt_lvl}级强妖台最高可以提升" . pet::get($pet_id, "name") . "到{$max_lvl_arr[$qyt_lvl]}级!";
            br();
            echo "提升1级三转宠物最大等级需要1000000个金币";
            $max_lvl = pet::get($pet_id, 'max_lvl');
            $can_up_lvl = $max_lvl_arr[$qyt_lvl] - $max_lvl;
            $url = cmd::addcmd2url("union::up_lvl,2,$pet_id");
            echo <<<html
<form action="$url" method="post">
<input type="text" name="up_lvl" placeholder="1-{$can_up_lvl}">
<br>
<input type="submit" value="确认提升">
</form>
html;
            cmd::add_last_cmd("union::up_lvl");
        } elseif ($step == 2) {
            if (user::have_pet($user_id, $pet_id, 1)) {
                $up_lvl = (int)post::get("up_lvl");
                $max_lvl = pet::get($pet_id, 'max_lvl');
                $can_up_lvl = $max_lvl_arr[$qyt_lvl] - $max_lvl;
                if ($up_lvl > 0 && $up_lvl <= $can_up_lvl) {
                    $zhp_money = item::get_zhp_money($user_id);
                    $need_money = 1000000 * $up_lvl;
                    if ($zhp_money >= $need_money) {
                        $new_max_lvl = $up_lvl + value::get_pet_value($pet_id, "max_lvl");
                        item::add_zhp_money(-1 * $need_money, $user_id);
                        value::set_pet_value($pet_id, "max_lvl", $new_max_lvl);
                        $now_lvl = pet::get($pet_id, 'lvl');
                        $now_max_exp = pet::get($pet_id, 'max_exp');
                        $chujing = pet::get($pet_id, 'chujing');
                        $true_exp = $chujing;
                        for ($i = 1; $i <= $now_lvl; $i++) {
                            if ($i != 1) {
                                $true_exp += $chujing * $i * $i / 4;
                            }
                        }
                        if ($now_max_exp != $true_exp) {
                            pet::set($pet_id, 'max_exp', $true_exp);
                        }
                        echo "恭喜你的" . pet::get($pet_id, "name") . "最大等级提升到{$new_max_lvl}级!";
                        br();
                        cmd::add_last_cmd("union::up_lvl");
                    } else {
                        echo "杂货铺里没有足够的金币了!";
                        br();
                        cmd::add_last_cmd("union::up_lvl,1,$pet_id");
                    }
                } else {
                    echo "输入提升的等级有误!";
                    br();
                    cmd::add_last_cmd("union::up_lvl,1,$pet_id");
                }
            } else {
                echo "该宠物已经不属于你了!";
                br();
                cmd::add_last_cmd("union::up_lvl");
            }
        }
    }
}


//竞技类
class sports
{
    //发起竞技
    static function launch_a_sports($o_user_id, $money)
    {
        $error = false;
        $error_msg = "";
        //判断己方条件
        $user_id = uid();
        $u_pet_arr = user::get_pet_arr($user_id, 1);
        foreach ($u_pet_arr as $pet_id) {
            $is_dead = value::get_pet_value($pet_id, 'is_dead');
            if ($is_dead) {
                $error = true;
                $error_msg = "你身上有已经死亡的宠物!";
                break;
            }
        }
        if (!$error) {
            $money = (int)$money;
            if ($money >= 0 && $money <= 10000000 && $money <= value::get_user_value('money', $user_id)) {

            } else {
                $error = true;
                $error_msg = "请押注1-10000000个金币或你的金币不足!";
            }
        }
        if (!$error) {
            if (user::get_zhang_count($user_id) || (int)(value::get_user_value('onlinetime', $user_id)) >= 0) {

            } else {
                $error = true;
                $error_msg = "你还是个不知名的玩家,还无法参加竞技场!";
            }
        }
        //判断对方条件
        if (!$error) {
            $money = (int)$money;
            if ($money >= 0 && $money <= 10000000 && $money <= value::get_user_value('money', $o_user_id)) {

            } else {
                $error = true;
                $error_msg = "请押注1-10000000个金币或对方的金币不足!";
            }
        }
        if (!$error) {
            if (user::get_zhang_count($o_user_id) || (int)(value::get_user_value('onlinetime', $o_user_id)) >= 0) {

            } else {
                $error = true;
                $error_msg = "对方还是个不知名的玩家,还无法参加竞技场!";
            }
        }
        if (!$error) {
            $map_id = value::get_game_user_value("in_map_id", $user_id);
            c_add_xiaoxi((int)$money, 21, $user_id, $o_user_id, $map_id);
            echo "挑战即将开始!<br>";
        } else {
            echo $error_msg;
        }
        br();
    }

    //接受竞技
    static function accept_sports($chat_id)
    {
        //挑战消息
        $l = "SELECT * FROM game_chat WHERE id={$chat_id} LIMIT 1";
        $rs = sql($l);
        $chat_arr = $rs->fetch_array(MYSQLI_ASSOC);
        if (!is_array($chat_arr)) {
            echo "竞技已经失效!";
            return;
        }
        $money = $chat_arr['chats'];
        //己方数据
        $user_id = uid();
        $user_name = user::get_game_user("name", $user_id);
        //对方数据
        $o_user_id = $chat_arr['uid'];
        $o_user_name = user::get_game_user("name", $o_user_id);
        $error = false;
        $error_msg = "";
        //判断己方条件
        $u_pet_arr = user::get_pet_arr($user_id, 1);
        foreach ($u_pet_arr as $pet_id) {
            $is_dead = value::get_pet_value($pet_id, 'is_dead');
            if ($is_dead) {
                $error_msg = "你身上有已经死亡的宠物!";
                break;
            }
        }
        if (!$error) {
            if ($money <= value::get_user_value('money', $user_id)) {

            } else {
                $error = true;
                $error_msg = "你的金币不足!";
            }
        }
        if (!$error) {
            if (user::get_zhang_count($user_id) || (int)(value::get_user_value('onlinetime', $user_id)) >= 0) {

            } else {
                $error = true;
                $error_msg = "你还是个不知名的玩家,还无法参加竞技场!";
            }
        }
        //判断对方条件
        if (!$error) {
            if (user::get_game_user("is_online", $o_user_id)) {

            } else {
                $error = true;
                $error_msg = "对方已经离线!";
            }
        }
        if (!$error) {
            $map_id = value::get_game_user_value("in_map_id", $user_id);
            $o_map_id = value::get_game_user_value("in_map_id", $o_user_id);
            if ($map_id == $o_map_id && $map_id == $chat_arr['map_id']) {

            } else {
                $error_msg = "对方已经不在竞技场!";
            }
        }
        if (!$error) {
            if ($money <= value::get_user_value('money', $o_user_id)) {

            } else {
                $error = true;
                $error_msg = "对方的金币不足!";
            }
        }
        if (!$error) {
            if (user::get_zhang_count($o_user_id) || (int)(value::get_user_value('onlinetime', $o_user_id)) >= 0) {

            } else {
                $error = true;
                $error_msg = "对方还是个不知名的玩家,还无法参加竞技场!";
            }
        }
        //PK竞技状态
        if (!$error) {
            if (!user::get("pkjjzt", $user_id)) {

            } else {
                $error = true;
                $error_msg = "你已经在一场竞技中!";
            }
        }
        if (!$error) {
            if (!user::get("pkjjzt", $o_user_id)) {

            } else {
                $error = true;
                $error_msg = "对方已经在一场竞技中!";
            }
        }
        //是否无错
        if (!$error) {
            //设置竞技状态
            user::set("pkjjzt", 1, $user_id);
            user::set("pkjjzt", 1, $o_user_id);
            //扣除杂货铺金币
            item::add_zhp_money1(-1 * $money, $user_id);
            item::add_zhp_money1(-1 * $money, $o_user_id);
            //发起竞技消息
            $l = "INSERT INTO game_sports (id, uid, oid, map_id, money) VALUES (NULL,$o_user_id,$user_id,$map_id," . ($money * 2) . ")";
            sql($l);
            $sports_id = $GLOBALS['mysqli']->insert_id;
            value::set_user_value('pkjjid', $sports_id, $user_id);
            value::set_user_value('pkjjid', $sports_id, $o_user_id);
            //输出消息
            c_add_xiaoxi("{$user_name}接受了你的竞技场挑战,10秒后进入战斗!", 0, $user_id, $o_user_id);
            //发出广播
            c_add_guangbo("{$o_user_name}在竞技场押上{$money}个金币挑战{$user_name}!", $sports_id, 3);
        } else {
            echo $error_msg;
        }
    }

    //进入竞技
    static function enter_the_sports($sports_id)
    {
        //竞技信息
        $l = "SELECT * FROM game_sports WHERE id={$sports_id} LIMIT 1";
        $rs = sql($l);
        $sports_arr = $rs->fetch_array(MYSQLI_ASSOC);
        //竞技双方是否符合条件
        $uid = $sports_arr['uid'];
        $uname = user::get_game_user("name", $uid);
        $oid = $sports_arr['oid'];
        $oname = user::get_game_user("name", $oid);
        $map_id = $sports_arr['map_id'];
        //挑战方是否符合要求
        $error_msg = "";
        $uok = true;
        $ook = true;
        $u_pet_id = 0;
        $o_pet_id = 0;
        //挑战方条件 是否在线 是否在竞技场 身上是否有已死亡宠物
        if (!user::get_game_user('is_online', $uid)) {
            $uok = false;
            $error_msg = "{$uname}已经离线,自动判负!";
        } else {
            $u_pet_arr = user::get_pet_arr($uid, 1);
            foreach ($u_pet_arr as $pet_id) {
                if (!$u_pet_id) {
                    $u_pet_id = $pet_id;
                }
                $is_dead = value::get_pet_value($pet_id, 'is_dead');
                if (($is_dead && !user::get_game_user('hp', $uid)) || (!$u_pet_id && !user::get_game_user('hp', $uid))) {
                    $uok = false;
                    $error_msg = "{$uname}身上有已经死亡的宠物,自动判负!";
                    break;
                }
            }
        }
        //挑战方具备条件
        if ($uok) {
            //被挑战方条件 是否在线 是否在竞技场 身上是否有已死亡将
            if (!user::get_game_user('is_online', $oid)) {
                $ook = false;
                $error_msg = "{$oname}已经离线,自动判负!";
            } else {
                $o_pet_arr = user::get_pet_arr($oid, 1);
                foreach ($o_pet_arr as $pet_id) {
                    if (!$o_pet_id) {
                        $o_pet_id = $pet_id;
                    }
                    $is_dead = value::get_pet_value($pet_id, 'is_dead');
                    if (($is_dead && !user::get_game_user('hp', $oid)) || (!$o_pet_id && !user::get_game_user('hp', $oid))) {
                        $ook = false;
                        $error_msg = "{$oname}身上有已经死亡的宠物,自动判负!";
                        break;
                    }
                }
            }
            if (!$ook) {
                //挑战方获胜
                c_add_xiaoxi($error_msg, 0, $oid, $uid);
                c_add_xiaoxi($error_msg, 0, $uid, $oid);
                self::out_of_sports($sports_id, $uid);
                return;
            }
        } else {
            //被挑战方获胜
            c_add_xiaoxi($error_msg, 0, $oid, $uid);
            c_add_xiaoxi($error_msg, 0, $uid, $oid);
            self::out_of_sports($sports_id, $oid);
            return;
        }
        //双方具备条件进入竞技
        //设置竞技状态
        self::set($sports_id, 'status', 1);
        //双方进入战斗
        value::set_user_value('pkjjid', $sports_id, $uid);
        value::set_user_value('pkjjid', $sports_id, $oid);
        value::set_user_value('pkjjzt', 2, $uid);
        value::set_user_value('pkjjzt', 2, $oid);
        value::set_game_user_value('is_pk', 1, $uid);
        value::set_game_user_value('is_pk', 1, $oid);
        //设置己方
        //设置全宠物未PK
        sql("UPDATE `game_pet` SET `is_pk`=0 WHERE  `master_id` ={$uid} AND `master_mode`=1 LIMIT 5");
        //设置角色属性 出战宠物 回合数 对战宠物 PK状态
        value::set_game_user_value('pk.user.id', $oid, $uid);
        value::set_game_user_value('pk.now_pet.id', $u_pet_id, $uid);
        value::set_user_value('pk.huihe', '0', $uid);
        value::set_user_value('pk.huihe.time', time(), $uid);
        value::set_user_value("pk_win_money", 0, $uid);
        value::set_user_value("pk_win_exp", 0, $uid);
        //清除战斗信息
        $obj = new game_user_object($uid);
        $obj->del("pk_chats");
        //设置敌方
        //设置全宠物未PK
        sql("UPDATE `game_pet` SET `is_pk`=0 WHERE  `master_id` ={$oid} AND `master_mode`=1 LIMIT 5");
        //设置角色属性 出战宠物 回合数 对战宠物 PK状态
        value::set_game_user_value('pk.user.id', $uid, $oid);
        value::set_game_user_value('pk.now_pet.id', $o_pet_id, $oid);
        value::set_user_value('pk.huihe', 0, $oid);
        value::set_user_value('pk.huihe.time', time(), $oid);
        value::set_user_value("pk_win_money", 0, $oid);
        value::set_user_value("pk_win_exp", 0, $oid);
        //清除战斗信息
        $obj = new game_user_object($oid);
        $obj->del("pk_chats");
        //双方进入战斗
        value::set_game_user_value('in_ctm', 'ctm_pk_user', $uid);
        cmd::delallcmd($uid);
        value::set_game_user_value('in_ctm', 'ctm_pk_user', $oid);
        cmd::delallcmd($oid);
    }

    //退出竞技
    static function out_of_sports($sports_id, $winner = 0, $renshu = false)
    {
        //获取竞技信息
        $sports_arr = self::get_arr($sports_id);
        //设置竞技状态
        self::set($sports_id, 'status', 2);
        //获取用户信息
        $uid = $sports_arr['uid'];
        $uname = user::get_game_user("name", $uid);
        $oid = $sports_arr['oid'];
        $oname = user::get_game_user("name", $oid);
        //获取赌注
        $duzhu = $sports_arr['money'] / 2;
        //发出广播
        if ($winner) {
            //胜者姓名
            if ($uid == $winner) {
                value::add_game_user_value('jingji.jifen', 1, $uid);
                $winner_name = $uname;
            } else {
                $winner_name = $oname;
            }
            //获取败者
            $loser = $winner == $uid ? $oid : $uid;
            //设置胜者
            self::set($sports_id, 'winner', $winner);
            //赌注分配
            $l = "SELECT SUM(money) FROM game_sports_stake WHERE sports_id=$sports_id AND winner=$winner";
            $r = sql($l);
            list($winner_stake) = $r->fetch_row();
            $winner_stake += $duzhu;
            $l = "SELECT SUM(money) FROM game_sports_stake WHERE sports_id=$sports_id AND winner=$loser";
            $r = sql($l);
            list($loser_stake) = $r->fetch_row();
            $loser_stake += $duzhu;
            $sports_stake = $winner_stake + $loser_stake;
            //获胜积分
            $not_jiesuanjifen = false;
            $winner_jifen = user::get_game_user('jingji.jifen', $winner);
            $loser_jifen = user::get_game_user('jingji.jifen', $loser);
            //胜者赌注
            $winner_money = (int)($duzhu / $winner_stake * $sports_stake);
            item::add_money($winner_money, $winner);
            c_add_xiaoxi("你赢得了{$winner_money}个金币与1点竞技积分!", 0, 0, $winner);
            //败者赌注
            c_add_xiaoxi("你输掉了{$duzhu}个金币!", 0, 0, $loser);
            //押赢提醒
            $l = "SELECT `uid`,`money` FROM `game_sports_stake` WHERE `sports_id`={$sports_id} AND `winner`={$winner} ";
            $r = sql($l);
            while (list($tid, $tmoney) = $r->fetch_row()) {
                $t_money = (int)($tmoney / $winner_stake * $sports_stake);
                item::add_zhp_money($t_money, $tid);
                c_add_xiaoxi("{$uname}VS{$oname},胜利者是{$winner_name},你赢得了{$t_money}个金币!", 0, 0, $tid);
            }
            //押输提醒
            $l = "SELECT `uid`,`money` FROM `game_sports_stake` WHERE `sports_id`={$sports_id} AND `winner`={$loser} ";
            $r = sql($l);
            while (list($tid, $tmoney) = $r->fetch_row()) {
                c_add_xiaoxi("{$uname}VS{$oname},胜利者是{$winner_name},你输掉了{$tmoney}个金币!", 0, 0, $tid);
            }
            //广播提醒
            c_add_guangbo("玩家竞技场,{$uname}VS{$oname},胜利者是{$winner_name}!");
        } else {
            //设置竞技状态
            sports::set($sports_id, "status", 3);
            //参与方押输提醒
            c_add_xiaoxi("平局!你输掉了{$duzhu}个金币!", 0, 0, $uid);
            c_add_xiaoxi("平局!你输掉了{$duzhu}个金币!", 0, 0, $oid);
            //第三方押输提醒
            $l = "SELECT `uid`,`money` FROM `game_sports_stake` WHERE `sports_id`={$sports_id}";
            $r = sql($l);
            while (list($tid, $tmoney) = $r->fetch_row()) {
                c_add_xiaoxi("{$uname}VS{$oname},最终结果为平局,你输掉了{$tmoney}个金币!", 0, 0, $tid);
            }
            //广播提醒
            c_add_guangbo("玩家竞技场,{$uname}VS{$oname},双方打的难解难分,最终结果为平局!");
        }
        //复活宠物
        $u_pet_arr = user::get_pet_arr($uid, 1);
        $o_pet_arr = user::get_pet_arr($oid, 1);
        $jingji_pet_arr = array_merge($u_pet_arr, $o_pet_arr);
        foreach ($jingji_pet_arr as $pet_id) {
            if (pet::get($pet_id, 'is_pk')) {
                $is_dead = value::get_pet_value($pet_id, 'is_dead');
                //宠物死亡
                if ($is_dead) {
                    //复活宠物
                    value::set_pet_value($pet_id, 'is_dead', 0);
                    //装备磨损
                    pet::prop_mosun($pet_id, -2, 0);
                } else {
                    //装备磨损
                    pet::prop_mosun($pet_id, -1, 0);
                    //胜利方
                    if ($winner == pet::get($pet_id, "master_id")) {
                        //忠诚度
                        value::add_pet_value($pet_id, 3, 1, false);
                    }
                }
                //回复血量
                pet::set($pet_id, 'hp', pet::get_max_hp($pet_id));
                //回复PP
                $zhuansheng = value::get_pet_value($pet_id, 'zhuansheng');
                $i = 0;
                while ($skill_id = value::get_pet_value2('skill.' . $i . '.id', $pet_id, false)) {
                    $max_pp = value::getvalue('skill', 'pp', 'id', $skill_id);
                    $max_pp *= $zhuansheng ? 1.5 : 1;
                    $max_pp = (int)$max_pp;
                    value::set_pet_value2('skill.' . $skill_id . '.pp', $max_pp, $pet_id);
                    $i++;
                }
                //恢复状态
                value::set_pet_value($pet_id, 'zhuangtai', 0);
            }
        }
        //显示战斗信息
        //挑战者
        $obj = new game_user_object($uid);
        $pk_chats = $obj->get("pk_chats", "string");
        $obj->del("pk_chats");
        if (mb_strlen($pk_chats) > 256) {
            $pk_chats = "";
        }
        if ($renshu) {
            if ($winner == $uid) {
                $pk_chats .= "{$oname}选择了认输,";
            } else {
                $pk_chats .= "你选择了认输,";
            }
        }
        if ($winner) {
            $pk_chats .= $winner == $uid ? "你打败了{$oname}!" : "你被{$winner_name}打败了!";
        }
        c_add_xiaoxi($pk_chats, 0, $uid, $uid);
        //被挑战者
        $obj = new game_user_object($oid);
        $pk_chats = $obj->get("pk_chats", "string");
        $obj->del("pk_chats");
        if (mb_strlen($pk_chats) > 256) {
            $pk_chats = "";
        }
        if ($renshu) {
            if ($winner == $oid) {
                $pk_chats .= "{$uname}选择了认输,";
            } else {
                $pk_chats .= "你选择了认输,";
            }
        }
        if ($winner) {
            $pk_chats .= $winner == $oid ? "你打败了{$uname}!" : "你被{$winner_name}打败了!";
        }
        c_add_xiaoxi($pk_chats, 0, $oid, $oid);
        //退出战斗
        value::set_user_value('pkjjid', 0, $uid);
        value::set_user_value('pkjjid', 0, $oid);
        value::set_user_value('pkjjzt', 0, $uid);
        value::set_user_value('pkjjzt', 0, $oid);
        user::exit_pk($uid);
        user::exit_pk($oid);
        cmd::delallcmd($uid);
        cmd::delallcmd($oid);
        if ($renshu) {
            e0();
        }
    }

    //获取竞技信息
    static function get_arr($sports_id)
    {
        $l = "SELECT * FROM game_sports WHERE id={$sports_id} LIMIT 1";
        $rs = sql($l);
        $sports_arr = $rs->fetch_array(MYSQLI_ASSOC);
        return $sports_arr;
    }

    //设置竞技属性
    static function set($sports_id, $valuename, $value)
    {
        $l = "UPDATE game_sports SET $valuename=$value WHERE id=$sports_id LIMIT 1";
        sql($l);
        return;
    }

    //竞技押注
    static function stake($sports_id, $winner, $user_id)
    {
        $money = (int)post::get("money");
        if (!$money) {
            $winner_name = user::get_game_user("name", $winner);
            $url = cmd::addcmd2url("sports::stake,{$sports_id},{$winner},{$user_id}");
            echo <<<html
你想要押{$winner_name}多少个金币?
<form action="$url" method="post">
<input type="text" name="money" maxlength="8" placeholder="1000-10000000">
<br>
<input type="submit" value="确认押注">
</form>
html;
        } else {
            $time = time();//现在时间
            $sports_arr = self::get_arr($sports_id);//竞技详情
            $sports_time = strtotime($sports_arr['time']);
            if ($money > 999 && $money < 10000001) {
                if ($sports_time + 60 > $time) {
                    if (item::get_money($user_id) >= $money) {
                        item::add_money(-1 * $money, $user_id);
                        $l = "INSERT INTO game_sports_stake (sports_id, winner, money, uid) VALUES ($sports_id,$winner,$money,$user_id)";
                        sql($l);
                        echo "押注{$money}个金币成功!";
                        br();
                    } else {
                        echo "你没有足够的金币了!";
                        br();
                    }
                } else {
                    echo "押注时间已过!";
                    br();
                }
            } else {
                echo "押注金币有误!";
                br();
            }
        }
        cmd::add_last_cmd("e205,{$sports_id}");
    }
}

//Gtring类
class gstring
{
    //获取string.json的预置文本
    static function get($name, $print = false, $dir = "")
    {
        $string_json_arr = config::getConfigByName("string", false, $dir);
        $string = $string_json_arr[$name];
        if ($print) {
            print_r($string);
        }
        return $string;
    }
}

//Config类
class config
{
    //获取config文件夹指定名字的json
    static function getConfigByName($name, $print = false, $dir = "")
    {
        if ($dir) {
            $file_name = "{$dir}/config/{$name}.json";
        } else {
            $file_name = dirname(dirname(__FILE__)) . "/json/config/{$name}.json";
        }
        $json_arr = json_decode(file_get_contents($file_name), true);
        if ($print) {
            print_r($json_arr);
        }
        return $json_arr;
    }
}

//POST类
class post
{
    static function get($value_name)
    {
        return value::real_escape_string($_POST[$value_name]);
    }
}

//系统类

class game_object
{
    protected $table_name = "";
    protected $game_object_id = 0;
    protected $obj_key_name = "";

    public function __construct($table_name, $game_object_id)
    {
        // 不再使用Redis，改用MySQL数据库
        $this->table_name = $table_name;
        $this->game_object_id = $game_object_id;
        $this->obj_key_name = $this->table_name . "." . $this->game_object_id;
        
        // 确保表存在（这里假设表已经创建，实际使用时需要手动创建）
        // 表结构: game_object_data(id, obj_key, property_key, property_value, create_time, update_time)
    }

    public function __destruct()
    {
    }

    // 搜索（替代Redis的hscan）
    public function scan(&$iterator = null, $pattern = null, $count = 0)
    {
        $mysqli = $GLOBALS['mysqli'];
        $obj_key = $this->obj_key_name;
        
        $sql = "SELECT property_key, property_value FROM game_object_data WHERE obj_key = '$obj_key'";
        
        if ($pattern) {
            // 简单的模式匹配
            $pattern = str_replace('*', '%', $pattern);
            $sql .= " AND property_key LIKE '%" . $mysqli->real_escape_string($pattern) . "%'";
        }
        
        $result = $mysqli->query($sql);
        $data = array();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['property_key']] = $row['property_value'];
            }
        }
        
        $iterator = null; // MySQL版本不需要迭代器
        return $data;
    }

    // 设置（替代Redis的hset）
    public function set($key, $value, $expire = 0)
    {
        $mysqli = $GLOBALS['mysqli'];
        $obj_key = $this->obj_key_name;
        $key = $mysqli->real_escape_string($key);
        $value = $mysqli->real_escape_string($value);
        
        // 检查是否已存在
        $sql = "SELECT id FROM game_object_data WHERE obj_key = '$obj_key' AND property_key = '$key' LIMIT 1";
        $result = $mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            // 更新
            $sql = "UPDATE game_object_data SET property_value = '$value', update_time = NOW() WHERE obj_key = '$obj_key' AND property_key = '$key'";
        } else {
            // 插入
            $sql = "INSERT INTO game_object_data (obj_key, property_key, property_value, create_time, update_time) VALUES ('$obj_key', '$key', '$value', NOW(), NOW())";
        }
        
        $mysqli->query($sql);
        return true;
    }

    // 获取（替代Redis的hget）
    public function get($key, $type = "int")
    {
        $mysqli = $GLOBALS['mysqli'];
        $obj_key = $this->obj_key_name;
        $key = $mysqli->real_escape_string($key);
        
        $sql = "SELECT property_value FROM game_object_data WHERE obj_key = '$obj_key' AND property_key = '$key' LIMIT 1";
        $result = $mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $value = $row['property_value'];
        } else {
            $value = "";
        }
        
        if ($value == "") {
            switch ($type) {
                case "int":
                    $value = 0;
                    break;
            }
        }
        return $value;
    }

    // 删除（替代Redis的hdel）
    public function del($key)
    {
        $mysqli = $GLOBALS['mysqli'];
        $obj_key = $this->obj_key_name;
        $key = $mysqli->real_escape_string($key);
        
        $sql = "DELETE FROM game_object_data WHERE obj_key = '$obj_key' AND property_key = '$key'";
        $mysqli->query($sql);
    }

    // 加减（替代Redis的hincrby/hincrbyfloat）
    public function incrby($key, $increment)
    {
        $old_value = $this->get($key);
        if ($increment < 0 && $old_value < 1) {
            return 0;
        }
        
        // 使用PHP计算，支持整数和浮点数
        $new_value = floatval($old_value) + floatval($increment);
        $this->set($key, $new_value);
        return $new_value;
    }

    // 推入json
    public function jpush($key, $son_key, $value, $expire = 0)
    {
        $old_arr = json_decode($this->get($key), true);
        if (!is_array($old_arr)) {
            $old_arr = array();
        }
        if ($son_key) {
            $old_arr[$son_key] = $value;
        } else {
            $old_arr[] = $value;
        }
        $this->set($key, json_encode($old_arr, JSON_UNESCAPED_UNICODE), $expire);
    }

    // 推出json
    public function jpop($key, $son_key)
    {
        $old_arr = json_decode($this->get($key), true);
        if (is_array($old_arr) && isset($old_arr[$son_key])) {
            unset($old_arr[$son_key]);
            $this->set($key, json_encode($old_arr, JSON_UNESCAPED_UNICODE));
        }
    }

    // 推出json
    public function jpop_by_value($key, $son_value)
    {
        $old_arr = json_decode($this->get($key), true);
        if (is_array($old_arr)) {
            $old_key = array_search($son_value, $old_arr);
            if ($old_key !== false) {
                array_splice($old_arr, $old_key, 1);
                $this->set($key, json_encode($old_arr, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    // 获取json
    public function jget($key)
    {
        $value = $this->get($key);
        $arr = json_decode($value, true);
        return is_array($arr) ? $arr : array();
    }

    // 批量设置（替代Redis的hmset）
    public function mset($kv_array)
    {
        foreach ($kv_array as $key => $value) {
            $this->set($key, $value);
        }
        return true;
    }

    // 批量获取（替代Redis的hmget）
    public function mget($k_array)
    {
        $result = array();
        foreach ($k_array as $key) {
            $result[] = $this->get($key);
        }
        return $result;
    }

    // 批量搜索匹配
    public function ascan($pattern = null, $count = 0)
    {
        $iterator = null;
        return $this->scan($iterator, $pattern, $count);
    }

    // 批量获取匹配
    public function aget($pattern = "*")
    {
        $data = $this->ascan($pattern);
        return $data;
    }

    // 批量设置匹配
    public function aset($pattern = "*")
    {
        return true; // 简化实现
    }

    // 批量加减匹配
    public function aincrby($pattern = "*", $increment = 1)
    {
        $data = $this->ascan($pattern);
        foreach ($data as $key => $value) {
            $this->incrby($key, $increment);
        }
    }

    // 批量删除匹配（替代Redis操作）
    public function adel($pattern = "*")
    {
        $mysqli = $GLOBALS['mysqli'];
        $obj_key = $this->obj_key_name;
        
        if ($pattern != "*") {
            // 简单的模式匹配
            $pattern = str_replace('*', '%', $pattern);
            $sql = "DELETE FROM game_object_data WHERE obj_key = '$obj_key' AND property_key LIKE '%" . $mysqli->real_escape_string($pattern) . "%'";
        } else {
            // 删除所有相关记录
            $sql = "DELETE FROM game_object_data WHERE obj_key = '$obj_key'";
        }
        
        $mysqli->query($sql);
    }
}

class game_pet_object extends game_object
{
    protected $table_name = "p";

    public function __construct($game_object_id)
    {
        parent::__construct($this->table_name, $game_object_id);
    }
}

class game_user_object extends game_object
{
    protected $table_name = "u";

    public function __construct($game_object_id)
    {
        parent::__construct($this->table_name, $game_object_id);
    }
}

class game_map_object extends game_object
{
    protected $table_name = "m";

    public function __construct($game_object_id)
    {
        parent::__construct($this->table_name, $game_object_id);
    }
}
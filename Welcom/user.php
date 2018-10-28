<?php 
/*
主要用户动态修改用户数据
*/

//错误屏蔽
error_reporting(0);
//修正时间
date_default_timezone_set('Asia/Shanghai');
//时间日期
$date=date('Y-m-d H:i:s');
//时间戳
$time=time();
//临时文件
$tmp_file=sys_get_temp_dir().'/portal.sh';
//用户文件
$user_file='user.json';
//获取用户文件
$json_string=file_get_contents($user_file);
//json解码
$data=json_decode($json_string, true);
//生成用户名
$user_count=count($data)."_$time";

//创建json文件
if (!is_array($data)) {
  file_put_contents($user_file, '[]', LOCK_EX);
}

//根绝mac获得对应地址
function user_ip($data, $user_mac) {
    foreach ($data as $key => $value) {
        foreach ($value as $user => $info) {
          if ($info['mac_address'] == $user_mac) {
            return $info['ip_address'];
          }
        }
    }
}

//用户状态改变函数
function user_change($data, $status, $user_mac) {
    foreach ($data as $key => $value) {
        foreach ($value as $user => $info) {
            $macaddress = $info['mac_address'];
            if ($macaddress == $user_mac) {
                $data[$key][$user]['status']=$status;
            }
        }
    }
    return array_filter($data);
}

//用户添加函数
function user_add($data, $user_count, $date, $user_ip, $user_mac) {
    $add_user = array(
        "user_$user_count" => array(
            'ip_address' => $user_ip,
            'mac_address' => $user_mac,
            'status' => 'OK',
            'reg_time' => $date
        )
    );
    array_push($data, $add_user);
    return array_filter($data);
}

//删除用户函数
function user_del($data, $user_count, $user_ip, $user_mac) {
    foreach ($data as $key => $value) {
        foreach ($value as $user => $info) {
            $ipaddress = $info['ip_address'];
            $macaddress = $info['mac_address'];
            if ($user == $user_count) {
                unset($data[$key][$user]);
            }
            if ($ipaddress == $user_ip) {
                unset($data[$key][$user]);
            }
            if ($macaddress == $user_mac) {
                unset($data[$key][$user]);
            }
        }
    }
    return array_filter($data);
}

//修改状态后应用
function run_script($tmp_file, $command) {
  file_put_contents($tmp_file, $command, LOCK_EX);
  chmod($tmp_file, 0700);
  shell_exec("su -c $tmp_file");
}

//获取mac地址
function get_mac($user_ip) {
    $arp_file = explode(PHP_EOL, file_get_contents('/proc/net/arp'));
    foreach ($arp_file as $arp) {
        $ip = preg_match('/[0-9]{1,3}(\.[0-9]{1,3}){3}/', $arp, $matchs);
        $ip = $matchs[0];
        $mac = preg_match('/[0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5}/', $arp, $matchs);
        $mac = $matchs[0];
        if ($ip == $user_ip && $mac) {
            return $mac;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $yhdz = $_POST['yhdz'];
    $yhmac = $_POST['yhmac'];
    $number = $_POST['number'];
}
//修改的同时执行脚本立即生效
if ($yhdz and $yhmac and $number) {
    $yhip=user_ip($data, $yhmac);
    if ($yhdz == 'activation') {
        file_put_contents($user_file, json_encode(user_change($data, 'OK', $yhmac)) , LOCK_EX);
        $command_run="iptables -t nat -D user_portal -s $yhip -m mac --mac-source $yhmac -j RETURN".PHP_EOL."iptables -t nat -I user_portal -s $yhip -m mac --mac-source $yhmac -j RETURN".PHP_EOL."iptables -t filter -D user_block -m mac --mac-source $yhmac -j DROP";
        run_script($tmp_file, $command_run);
    }
    if ($yhdz == 'block') {
        file_put_contents($user_file, json_encode(user_change($data, 'Block', $yhmac)) , LOCK_EX);
        $command_run="iptables -t nat -D user_portal -s $yhip -m mac --mac-source $yhmac -j RETURN".PHP_EOL;
        run_script($tmp_file, $command_run);
    }
    if ($yhdz == 'deleted') {
        file_put_contents($user_file, json_encode(user_del($data, '', '', $yhmac)) , LOCK_EX);
        $command_run="iptables -t nat -D user_portal -s $yhip -m mac --mac-source $yhmac -j RETURN".PHP_EOL."iptables -t filter -D user_block -m mac --mac-source $yhmac -j DROP";
        run_script($tmp_file, $command_run);
    }
}
?>
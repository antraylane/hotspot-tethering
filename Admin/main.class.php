<?php
$dir = __DIR__ . '/admin.php';
if (file_exists($dir)) {
    require $dir;
} else {
    die('管理员密码配置文件遗失');
}
$rza = $_COOKIE['user_name'];
$rzb = $_COOKIE['pass_word'];
$rzc = hash('sha512', U);
$rzd = hash('sha512', P);
if ($rza && $rzb) {
    if ($rza != $rzc || $rzb != $rzd) {
        die('非法登录！');
    }
} else {
    header('Location: ../Admin/login.php');
    die('需要登录认证才能访问!');
}
function geturlkeyvalue($url) {
    $result = array();
    $mr = preg_match_all('/(\?|&)(.+?)=([^&?]*)/i', $url, $matchs);
    if ($mr !== false) {
        for ($i = 0; $i < $mr; $i++) {
            $result[$matchs[2][$i]] = $matchs[3][$i];
        }
    }
    return $result;
    //$_SERVER['QUERY_STRING'];
    
}
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
function toolbox_check() {
    $binary_name = array(
        'toybox',
        'busybox'
    );
    $binary_dir = array(
        '/system/bin/',
        '/system/xbin/',
        sys_get_temp_dir() . '/'
    );
    foreach ($binary_dir as $dir) {
        foreach ($binary_name as $name) {
            if (file_exists($dir . $name)) {
                return array(
                    $name,
                    $dir . $name
                );
            }
        }
    }
}
function toolbox_copy($binary_file) {
    $tmp_file = sys_get_temp_dir() . '/busybox';
    if (file_exists($binary_file)) {
        if (!is_executable($tmp_file)) {
            if (copy($binary_file, $tmp_file)) {
                if (chmod($tmp_file, 0700)) {
                    return $tmp_file;
                }
            }
        }
    }
}
function fast_ps() {
    $ps = toolbox_check();
    if ($ps[0] == 'toybox') {
        $run = $ps[1] . ' ps -A';
    } else {
        $run = $ps[1] . ' ps';
    }
    return $run;
}
function binary_status($order) {
    $run = fast_ps();
    exec("su -c ${run}", $output, $return_val);
    if ($return_val != 0) {
        die('执行命令失败！返回值: ' . $return_val);
    }
    if (is_array($order) || is_object($order)) {
        foreach ($order as $key) {
            foreach ($output as $val) {
                if (stripos($val, $key)) {
                    $status[] = $key;
                }
            }
        }
        return $status;
    } else {
        foreach ($output as $val) {
            if (stripos($val, $order)) {
                return $order;
            }
        }
    }
}
function GET($url) {
    $ssl = substr($url, 0, 8) == 'https://' ? true : false;
    $ch = curl_init();
    $opt = array(
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
        CURLOPT_COOKIE => '',
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
    );
    if ($ssl) {
        $opt[CURLOPT_SSL_VERIFYHOST] = 2;
        $opt[CURLOPT_SSL_VERIFYPEER] = false;
    }
    curl_setopt_array($ch, $opt);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function get_exec($shell,$toast) {
    foreach ($shell as $key) { 
      exec('su -c ' . $key, $output, $return_var);
         if(empty($output) && $return_var == 0) { 
           echo $toast;
           die(0);
         } elseif ($return_var != 0) {
           echo '失败！';
           die(1);
         }
    }
}
function Console($of) {
    if ($of == 'on') {
      get_exec(array('svc data enable'),'已经打开数据连接!');
    }
    if ($of == 'off') {
      get_exec(array('svc data disable'),'已经关闭数据连接!');
    }
    if ($of == 'restart') {
        $command = array(
            'svc power reboot',
            'am start -a android.intent.action.REBOOT',
            'reboot',
            'killall system_server',
            'stop',
            'killall zygote'
        );
        get_exec($command,'重启中…');
    }
    if ($of == 'shutdown') {
        $command = array(
            'svc power shutdown',
            'am start -a android.intent.action.ACTION_REQUEST_SHUTDOWN --ez KEY_CONFIRM true --activity-clear-task',
            'am start -n android/com.android.internal.app.ShutdownActivity',
            'am start -a android.intent.action.SHUTDOWN',
            'reboot -p'
        );
        get_exec($command,'关机中…');
    }
    function android_share_input($ss) {
        if (stripos("$ss", 'plugin') !== false) {
            die('暂不支持添加插件解析');
        }
        if (stripos("$ss", '#') !== false) {
            $ss = explode('#', $ss);
            $name = urldecode($ss[1]);
            $ss = explode('@', $ss[0]);
            $s_p = explode(':', $ss[1]);
            $server = $s_p[0];
            $server_port = $s_p[1];
            $ss = str_replace('ss://', '', explode('@', $ss[0]));
            $m_p = base64_decode($ss[0]);
            $m_p = explode(':', $m_p);
            $password = $m_p[1];
            $method = $m_p[0];
        } else {
            $ss = str_replace('ss://', '', explode('@', $ss));
            $s_p = explode(':', $ss[1]);
            $ss = explode('@', $ss[0]);
            $server = $s_p[0];
            $server_port = $s_p[1];
            $m_p = base64_decode($ss[0]);
            $m_p = explode(':', $m_p);
            $password = $m_p[1];
            $method = $m_p[0];
        }
        if ($server and $server_port and $password and $method) {
            return array(
                $name,
                $server,
                $server_port,
                $password,
                $method
            );
        }
    }
}
function share_input($ss) {
    $ss = str_replace('ss://', '', $ss);
    if (stripos("$ss", '#') !== false) {
        $ss = explode('#', $ss);
        $name = urldecode($ss[1]);
        $ss = $ss[0];
    }
    $ss = base64_decode($ss);
    $m_p = explode('@', $ss);
    $s_p = $m_p[1];
    $s_p = explode(':', $m_p[1]);
    $server = $s_p[0];
    $server_port = $s_p[1];
    $m_p = explode(':', $m_p[0]);
    $method = $m_p[0];
    $password = $m_p[1];
    if ($server and $server_port and $password and $method) {
        return array(
            $name,
            $server,
            $server_port,
            $password,
            $method
        );
    }
}
?>

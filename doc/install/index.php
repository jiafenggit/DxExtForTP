<?php
session_name('JGGL');
define('APP_NAME', 'JGGL');
define('APP_DEBUG', true);
define('DXINFO_PATH','DXINFO_DIR_PATH');
define('DX_PUBLIC',dirname($_SERVER["SCRIPT_NAME"]).'/DxWebRoot');
require_once '../FirePHPCore-0.3.2/lib/FirePHPCore/fb.php';
fb::setEnabled(true);
define('THINK_PATH', '../ThinkPHP/ThinkPHP312/');
error_reporting(E_ALL);
ini_set("display_errors","On");

if(ini_get("magic_quotes_gpc")=="1"){
    die("please set php.php magic_quotes_gpc=off\n");
}
define('APP_PATH', '../'.APP_NAME.'/');
//设置临时路径
if(strpos($_SERVER["SERVER_SOFTWARE"],"Unix")===false && strpos($_SERVER["SERVER_SOFTWARE"],"CentOS")===false){
    define('DXINFO_PATH','C:/Users/fei/OneDrive/DxInfo');
    define('RUNTIME_PATH', 'e:/tmp/'.APP_NAME."/");
}else{
    define('DXINFO_PATH','/m/SkyDrive/DxInfo');
    if(file_exists("/dev/shm")){
        define('RUNTIME_PATH', '/dev/shm/'.APP_NAME."/");
    }else{
        define('RUNTIME_PATH', '/tmp/'.APP_NAME."/");
    }
    define("LOG_PATH","/tmp/".APP_NAME."_log/");
}

//加载框架入口函数
require(THINK_PATH."ThinkPHP.php");


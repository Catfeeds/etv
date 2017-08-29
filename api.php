<?php
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);
// 定义应用目录
define('APP_PATH','./Api/');
define('FILE_UPLOAD_ROOTPATH', dirname(__FILE__) . '/Public');
// 定义运行时目录
define('RUNTIME_PATH','./Runtime/');/*非必须 本项目本来没有*/

// 引入ThinkPHP入口文件
define('THINK_PATH', realpath('Core').'/');
define('BIND_MODULE','Admin');
require THINK_PATH.'ThinkPHP.php'; /*定义绝对路径会提高系统的加载效率*/
// require './Core/ThinkPHP.php';

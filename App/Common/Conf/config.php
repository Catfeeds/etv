<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2014-2016 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues 
// +-------------------------------------------------------------------------
// | Description: 配置文件
// +-------------------------------------------------------------------------

return array(
	'MODULE_ALLOW_LIST'=>array('Admin','Api'),
	'DEFAULT_MODULE'=>'Admin',// 默认模块
    'DEVICE_ONLINE_TIME'=>120,//设备在线判断（秒数）
    'LOAD_EXT_CONFIG'=> 'const', //自定义常量
    'URL_CASE_INSENSITIVE' => false,
	'LOG_RECORD' => true,
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR', //记录允许的日志
    //数据库链接配置
    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'   => 'localhost', // 服务器地址
    'DB_NAME'   => 'etv_init', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => '', // 密码
    'DB_PORT'   => 3306, // 端口
    'DB_PREFIX' => 'zxt_', // 数据库表前缀
    'DB_CHARSET'=>  'utf8', // 数据库编码默认采用utf8
    //备份配置
    'DB_PATH_NAME'=> 'db', //备份目录名称,主要是为了创建备份目录
    'DB_PATH'     => './db/', //数据库备份路径必须以 / 结尾
    'DB_PART'     => '20971520', //该值用于限制压缩后的分卷最大长度。单位：B；建议设置20M
    'DB_COMPRESS' => '1', //压缩备份文件需要PHP环境支持gzopen,gzwrite函数        0:不压缩 1:启用压缩
    'DB_LEVEL'    => '9', //压缩级别   1:普通   4:一般   9:最高
);
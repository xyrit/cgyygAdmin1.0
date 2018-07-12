<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * UCenter客户端配置文�?
 * 注意：该配置文件请使用常量方式定�?
 */

define('UC_APP_ID', 1); //应用ID
define('UC_API_TYPE', 'Model'); //可�?��?? Model / Service
define('UC_AUTH_KEY', 'nR+Mvgu@cD9K,tVk7]$%F_[wC:ZP1pa)6-;3I8TY'); //加密KEY
define('UC_DB_DSN', 'mysql://root:root@192.168.1.250:3306/oneshop'); // 内网数据库连接，使用Model方式调用API必须配置此项
//define('UC_DB_DSN', 'mysql://cgtest:Cgtestcg@rds433q5145bogt7112d.mysql.rds.aliyuncs.com:3306/oneshop'); // 外网测试数据库连接，使用Model方式调用API必须配置此项
// define('UC_DB_DSN', 'mysql://cgoscgdb:Cg_cg123cg@rdsv1fa8p18494g8ui8n.mysql.rds.aliyuncs.com:3306/oneshop'); // 外网正式数据库连接，使用Model方式调用API必须配置此项
define('UC_TABLE_PREFIX', 'os_'); // 数据表前�?，使用Model方式调用API必须配置此项

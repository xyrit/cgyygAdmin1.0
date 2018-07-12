<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 系统配文�?
 * �?有系统级别的配置
 */
return array(
    /*关闭缓存。部署时注释掉*/
    'TMPL_CACHE_ON'=>FALSE,//默认开启模板缓存
    'ACTION_CACHE_ON'=>FALSE,//默认关闭action缓存
    'HTML_CACHE_ON'=>FALSE,//默认关闭静态缓存
    'DB_FIELD_CACHE'=>FALSE,//关闭数据库字段缓存
    
    /* 模块相关配置 */
    'AUTOLOAD_NAMESPACE' => array('Addons' => ONETHINK_ADDON_PATH), //扩展模块列表
    'DEFAULT_MODULE'     => 'Home',
    'MODULE_DENY_LIST'   => array('Common', 'User'),
    //'MODULE_ALLOW_LIST'  => array('Home','Admin'),

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'nR+Mvgu@cD9K,tVk7]$%F_[wC:ZP1pa)6-;3I8TY', //默认数据加密KEY

    /* 调试配置 */
    'SHOW_PAGE_TRACE' => true,

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //�?大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小�? true则表示不区分大小�?
    'URL_MODEL'            => 3, //URL模式
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割�?

    /* 全局过滤配置 */
    'DEFAULT_FILTER' => '', //全局过滤函数

    /* 数据库配�? */
    'DB_TYPE'   => 'mysql', // 数据库类�?
    'DB_HOST'   => '192.168.1.250', // 内网数据库服务器地址
//    'DB_HOST'   => 'rds433q5145bogt7112d.mysql.rds.aliyuncs.com', // 外网数据库测试服务器地址
//    'DB_HOST'   => 'rdsv1fa8p18494g8ui8n.mysql.rds.aliyuncs.com', // 外网数据库正式服务器地址
    'DB_NAME'   => 'oneshop', // 数据库名
     'DB_USER'   => 'root', // 内网数据库用户名
  //  'DB_USER'   => 'cgtest', // 外网数据库测试服务器用户名
//    'DB_USER'   => 'cgoscgdb', // 外网数据库正式服务器用户名
    'DB_PWD'    => 'root',  // 密码
//    'DB_PWD'    => 'Cgtestcg',  // 外网数据库测试服务器密码
//    'DB_PWD'    => 'Cg_cg123cg',  // 外网数据库正式服务器密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'os_', // 数据库表前缀

    /* 文档模型配置 (文档模型核心配置，请勿更�?) */
    'DOCUMENT_MODEL_TYPE' => array(2 => '主题', 1 => '目录', 3 => '段落'),
);

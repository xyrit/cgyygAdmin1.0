<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.thinkphp.cn>
// +----------------------------------------------------------------------

/**
 * 前台配置文件
 * 所有除开系统级别的前台配置
 */
return array(
    /*打开日志*/
    'LOG_RECORDER' => true,
    
    /*页面trace*/
    'SHOW_PAGE_TRACE' =>true,
    /*设置页面trace信息保存到日志中*/
    'PAGE_TRACE_SAVE' => array('base', 'file', 'sql'),
    
    /*本地数据库连接配置*/
//     'DB_TYPE' => 'mysql', // 数据库类型
//     'DB_HOST' => '192.168.0.117', // 服务器地址
//     'DB_NAME' => 'oneshop', // 数据库名
//     'DB_USER' => 'root', // 用户名
//     'DB_PWD' => 'root', // 密码
//     'DB_PORT' => '3306', // 端口
//     'DB_PREFIX' => 'os_', // 数据库表前缀
//     'DB_FIELDTYPE_CHECK' => false, // 是否进行字段类型检查 3.2.3版本废弃
// //     'DB_FIELDS_CACHE' => true, // 启用字段缓存
//     'DB_FIELDS_CACHE' => false, // 启用字段缓存
//     'DB_CHARSET' => 'utf8', // 数据库编码默认采用utf8
//     'DB_DEPLOY_TYPE' => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
//     'DB_RW_SEPARATE' => false, // 数据库读写是否分离 主从式有效
//     'DB_MASTER_NUM' => 1, // 读写分离后 主服务器数量
//     'DB_SLAVE_NO' => '', // 指定从服务器序号
//     'DB_SQL_BUILD_CACHE' => false, // 数据库查询的SQL创建缓存 3.2.3版本废弃
//     'DB_SQL_BUILD_QUEUE' => 'file', // SQL缓存队列的缓存方式 支持 file xcache和apc 3.2.3版本废弃
//     'DB_SQL_BUILD_LENGTH' => 20, // SQL缓存的队列长度 3.2.3版本废弃
//     'DB_SQL_LOG' => false, // SQL执行日志记录 3.2.3版本废弃
//     'DB_BIND_PARAM' => false, // 数据库写入数据自动参数绑定
//     'DB_DEBUG' => false, // 数据库调试模式 3.2.3新增
//     'DB_LITE' => false, // 数据库Lite模式 3.2.3新增

    /* 数据缓存设置 */
    'DATA_CACHE_PREFIX'    => 'onethink_', // 缓存前缀
    'DATA_CACHE_TYPE'      => 'File', // 数据缓存类型
    'URL_MODEL'            => 3, //URL模式

    /* 文件上传相关配置 */
    'DOWNLOAD_UPLOAD' => array(
        'mimes'    => '', //允许上传的文件MiMe类型
        'maxSize'  => 50*1024*1024, //上传的文件大小限制 (0-不做限制)
        'exts'     => 'jpg,gif,png,jpeg,zip,rar,tar,gz,7z,doc,docx,txt,xml', //允许上传的文件后缀
        'autoSub'  => true, //自动子目录保存文件
        'subName'  => array('date', 'Ym/d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/Download/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt'  => '', //文件保存后缀，空则使用原后缀
        'replace'  => false, //存在同名是否覆盖
        'hash'     => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), //下载模型上传配置（文件上传类配置）

    /* 图片上传相关配置 */
    'PICTURE_UPLOAD' => array(
		'mimes'    => '', //允许上传的文件MiMe类型
		'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
		'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
		'autoSub'  => true, //自动子目录保存文件
		'subName'  => array('date', 'Ym/d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
		'rootPath' => './Uploads/Picture/', //保存根路径
		'savePath' => '', //保存路径
		'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
		'saveExt'  => '', //文件保存后缀，空则使用原后缀
		'replace'  => false, //存在同名是否覆盖
		'hash'     => true, //是否生成hash编码
		'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), //图片上传相关配置（文件上传类配置）

    'PICTURE_UPLOAD_DRIVER'=>'oss',
    //本地上传文件驱动配置
    'UPLOAD_LOCAL_CONFIG'=>array(),
	'UPLOAD_OSS_CONFIG'=>array(
		'accessKeyId'=>'08iJabGVcaucodBT',
		'accessKeySecret'=>'RkyzXVJI7TRPlM0e8SrgyTsS2RU4P7',
		'endpoint'=>'oss-cn-shenzhen.aliyuncs.com',
		'bucket' => 'cgchengguo'
	),
    'UPLOAD_BCS_CONFIG'=>array(
        'AccessKey'=>'',
        'SecretKey'=>'',
        'bucket'=>'',
        'rename'=>false
    ),
    'UPLOAD_QINIU_CONFIG'=>array(
        'accessKey'=>'__ODsglZwwjRJNZHAu7vtcEf-zgIxdQAY-QqVrZD',
        'secrectKey'=>'Z9-RahGtXhKeTUYy9WCnLbQ98ZuZ_paiaoBjByKv',
        'bucket'=>'onethinktest',
        'domain'=>'onethinktest.u.qiniudn.com',
        'timeout'=>3600,
    ),


    /* 编辑器图片上传相关配置 */
    'EDITOR_UPLOAD' => array(
		'mimes'    => '', //允许上传的文件MiMe类型
		'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
		'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
		'autoSub'  => true, //自动子目录保存文件
		'subName'  => array('date', 'Ym/d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
		'rootPath' => './Uploads/Editor/', //保存根路径
		'savePath' => '', //保存路径
		'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
		'saveExt'  => '', //文件保存后缀，空则使用原后缀
		'replace'  => false, //存在同名是否覆盖
		'hash'     => true, //是否生成hash编码
		'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ),

    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
        '__CSS__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
        '__JS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
    ),

    /* SESSION 和 COOKIE 配置 */
    'SESSION_PREFIX' => 'onethink_admin', //session前缀
    'COOKIE_PREFIX'  => 'onethink_admin_', // Cookie前缀 避免冲突
    'VAR_SESSION_ID' => 'session_id',	//修复uploadify插件无法传递session_id的bug

    /* 后台错误页面模板 */
    'TMPL_ACTION_ERROR'     =>  MODULE_PATH.'View/Public/error.html', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_SUCCESS'   =>  MODULE_PATH.'View/Public/success.html', // 默认成功跳转对应的模板文件
    'TMPL_EXCEPTION_FILE'   =>  MODULE_PATH.'View/Public/exception.html',// 异常页面的模板文件
    'START_CODE'=>10000000  //幸运码起始码/期号开始数
);
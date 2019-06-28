<?php
require(__DIR__ . '/vendor/autoload.php');

//获取 OPEN_ID
$wechat = new \sfsoft\wechat\base('app_id', 'app_secret');
$detail = $wechat->getOpenID();

//获取附带用户信息的详细信息
$wechat = new \sfsoft\wechat\base('app_id', 'app_secret');
$detail = $wechat->getUserDetail();

//获取用户详细信息并存储到数据库
$wechat = new \sfsoft\wechat\base('app_id', 'app_secret');
$wechat->db_name = 't_wechat_user_log';
$wechat->dataBaseConfig = array(
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'wechat_manager',
    'username' => 'demo',
    'password' => 'demo',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_bin',
    'prefix' => '',
);
$detail = $wechat->getUserDetail();

//获取 JS 签名包
$wechat = new \sfsoft\wechat\jsapi('app_id', 'app_secret');
$detail = $wechat->getSignPackage('http://www.example.com');

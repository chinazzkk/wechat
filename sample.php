<?php
require(__DIR__ . '/vendor/autoload.php');

//获取 OPEN_ID
$wechat = new \sfsoft\wechat\SFBase('app_id', 'app_secret');
$detail = $wechat->getOpenID();

//获取 JS 签名包
$wechat = new \sfsoft\wechat\SFJs('app_id', 'app_secret');
$detail = $wechat->getSignPackage('http://www.example.com');

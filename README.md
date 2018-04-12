# mp-wechat
####这是一个微信公众平台基础功能Lib库。
####用于微信公众号获取JS授权以及 open_id 获取


#### 获取 OPEN_ID
    $openID = new \SFWechat\WechatBase('app_id', 'app_secret');
    $openID->checkState = false;
    $openID = $openID->getOpenID();

#### 获取 JS 签名包
    $single = new \SFWechat\WechatJs('app_id', 'app_secret');
    $single = $single->getSignPackage('http://www.example.com');

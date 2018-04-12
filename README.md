# wechat helper
    这是一个微信公众平台基础功能Lib库。
    用于微信公众号获取JS授权以及 open_id 获取。


#### 获取 OPEN_ID
    //获取 OPEN_ID
    $wechat = new \sfsoft\wechat\SFBase('app_id', 'app_secret');
    $detail = $wechat->getOpenID();

#### 获取 JS 签名包
    $wechat = new \sfsoft\wechat\SFJs('app_id', 'app_secret');
    $detail = $wechat->getSignPackage('http://www.example.com');


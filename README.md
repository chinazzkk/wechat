
#### composer 安装
    composer require sfsoft/wechat

#### wechat helper
    这是一个微信公众平台基础功能Lib库。
    用于微信公众号获取JS授权以及 open_id 获取。

#### 获取 OPEN_ID
    //获取 OPEN_ID
    $wechat = new \sfsoft\wechat\base('app_id', 'app_secret');
    $detail = $wechat->getOpenID();
    
#### 获取用户详细信息并存储到数据库
    $wechat = new \sfsoft\wechat\base('app_id', 'app_secret');
    $wechat->dataBaseConfig =  array(
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

#### 获取 JS 签名包
    $wechat = new \sfsoft\wechat\jsapi('app_id', 'app_secret');
    $detail = $wechat->getSignPackage('http://www.example.com');


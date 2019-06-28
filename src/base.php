<?php

namespace sfsoft\wechat;


use Illuminate\Database\Capsule\Manager as DB;

class base
{
    public $data = null;
    public $dataBaseConfig = false;
    public $open_id = '';
    public $checkState = false;
    private $appid = '';
    private $secret = '';
    private $code = '';

    public function __construct($app_id, $secret)
    {
        if (($app_id && $secret) === true) {
            $this->appid = $app_id;
            $this->secret = $secret;
        } else {
            echo 'sfs wechat notice:app_id and app_secret config error.';
            die();
        }
    }

    public function getOpenID($baseUrl = null)
    {
        $this->_getCode($baseUrl, true);
        //获取code码，获取个人用户token信息，包含open_id信息
        $url = $this->_createAccessTokenURL();
        $data = $this->_sendRequest($url);
        if ($data) {
            if (isset($data['access_token']) && isset($data['openid'])) {
                $this->open_id = $data['openid'];
                return $data['openid'];
            } else {
                return false;
            }
        } else {
            var_dump($data);
            die();
        }
    }

    //获取用户完整信息
    public function getUserDetail($baseUrl = null)
    {
        //通过code获得openid
        $this->_getCode($baseUrl, false);
        //获取code码，获取个人用户token信息，包含open_id信息
        $url = $this->_createAccessTokenURL();
        $data = $this->_sendRequest($url);
        if ($data) {
            if (isset($data['access_token']) && isset($data['openid'])) {
                $this->open_id = $data['openid'];
                $scope = $data['scope'];
                if ($scope === 'snsapi_userinfo') {
                    return $this->getUserInfo($data['access_token'], $data['openid']);
                } else {
                    return array('code' => -100, 'msg' => 'scope type error');
                }
            }
        } else {
            return array('code' => -100, 'msg' => 'request data error');
        }
    }

    /**
     *
     * 拼接CODE
     *
     * @return bool
     */
    private function _getCode($baseUrl, $isBase = false)
    {
        //未获取的情况下查询当前URL
        if ($this->checkState) {
            if (isset($_GET['state'])) {
                $st = $_GET['state'];
                if ($st == '7030' || $st == '7031') {
                    unset($_GET['code']);
                }
            }
        }
        if (!isset($_GET['code'])) {
            if (!$baseUrl)
                $baseUrl = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . $_SERVER['QUERY_STRING']);
            $url = $this->_createCodeURL($baseUrl, $isBase);
            Header("Location: $url");
            exit();
        } else {
            $this->code = $_GET['code'];
        }
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return string
     */
    private function _toUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }


        $buff = trim($buff, "&");
        return $buff;
    }


    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return string
     */
    private function _createCodeURL($redirectUrl, $isBase = false)
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        if ($isBase)
            $urlObj["scope"] = "snsapi_base";
        else
            $urlObj["scope"] = "snsapi_userinfo";
        if ($isBase)
            $state = '7030';
        else
            $state = '7031';
        $urlObj["state"] = $state . "#wechat_redirect";
        $bizString = $this->_toUrlParams($urlObj);
        $baseUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?";
        $result = $baseUrl . $bizString;
        return $result;
    }

    /**
     * UnionID用户资料信息
     */

    public function getUnionIdInfo($open_id, $ln = 'zh_CN')
    {
        $accessToken = lib::getAccessToken($this->appid, $this->secret);
        $baseUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$accessToken&openid=$open_id&lang=$ln";
        $result = $this->_sendRequest($baseUrl);
        return $result ? $result : false;
    }


    /**
     * 获取用户资料信息
     */

    public function getUserInfo($token, $open_id, $ln = 'zh_CN')
    {

        if ($this->checkToken($token, $open_id)) {
            $baseUrl = "https://api.weixin.qq.com/sns/userinfo?access_token=$token&openid=$open_id&lang=$ln";
            $result = $this->_sendRequest($baseUrl);

            if ($this->dataBaseConfig) {
                lib::initDataBase($this->dataBaseConfig);
                $detail = DB::table(DB_NAME)->where('f_openid', '=', $open_id)->count();
                $updateData = array(
                    'f_openid' => $open_id,
                    'f_token' => $token,
                    'f_nickname' => @$result['nickname'],
                    'f_sex' => @$result['sex'],
                    'f_city' => @$result['city'],
                    'f_province' => @$result['province'],
                    'f_country' => @$result['country'],
                    'f_headimgurl' => @$result['headimgurl'],
                    'f_unionid' => @$result['unionid'],
                    'f_json' => json_encode($result),
                    'f_token_expire' => time() + 7200
                );
                if ($detail > 0) {
                    unset($updateData['f_openid']);
                    $updateData['f_update'] = date('Y-m-d H:i:s');
                    DB::table(DB_NAME)->where('f_openid', $open_id)->update($updateData);
                } else {
                    $updateData['f_create'] = date('Y-m-d H:i:s');
                    $updateData['f_update'] = date('Y-m-d H:i:s');
                    DB::table(DB_NAME)->insertGetId(
                        $updateData
                    );
                }
            }

            return $result ? $result : false;
        } else {
            return false;
        }
    }


    /**
     *
     * 检测token 和 open_id是否有效
     *
     * @return bool
     */
    private function checkToken($token, $open_id)
    {
        $checkUrl = "https://api.weixin.qq.com/sns/auth?access_token=$token&openid=$open_id";
        $result = $this->_sendRequest($checkUrl);
        if ($result) {
            if (isset($result['errcode'])) {
                return $result['errcode'] == 0 ? true : false;
            }
        } else {
            return false;
        }
    }

    /**
     *
     * 构造获取网页授权access_token
     *
     * @return string
     */
    private function _createAccessTokenURL()
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["secret"] = $this->secret;
        $urlObj["code"] = $this->code;
        $urlObj["grant_type"] = 'authorization_code';
        $bizString = $this->_toUrlParams($urlObj);
        $baseUrl = "https://api.weixin.qq.com/sns/oauth2/access_token?";
        $result = $baseUrl . $bizString;
        return $result;
    }


    /**
     *
     * 网络请求
     *
     * @return object
     */
    public function _sendRequest($url)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            $res = json_decode($res, true);
        }
        return $res;
    }

}
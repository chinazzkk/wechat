<?php

namespace SFWechat;

define("ACCESS_TOKEN_DIR", __DIR__ . DIRECTORY_SEPARATOR . "token" . DIRECTORY_SEPARATOR . 'access_token.php');
define("JSAPI_TICKET_DIR", __DIR__ . DIRECTORY_SEPARATOR . "token" . DIRECTORY_SEPARATOR . 'jsapi_ticket.php');

class WechatJs
{
    private $appid = '';
    private $secret = '';

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

    public function getToken()
    {
        $expireTime = 0;
        $tokenFile = WechatLib::getJsonFile(ACCESS_TOKEN_DIR);
        if ($tokenFile) {
            $tokenFile = json_decode($tokenFile);
        } else {
            return false;
        }
        if ($tokenFile) {
            $expireTime = $tokenFile->expire_time;
        }
        if (($expireTime > time()) && isset($tokenFile->access_token)) {
            return $tokenFile->access_token;
        }

        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appid&secret=$this->secret";
        $result = WechatLib::sendRequest($tokenUrl);
        if (is_array($result) && isset($result['errcode'])) {
            return false;
        }
        if (is_array($result) && !isset($result['errcode'])) {
            $expireTime = time() + (int)$result['expires_in'];
            $token = $result['access_token'];
            $result['expire_time'] = $expireTime;
            WechatLib::setJsonFile(ACCESS_TOKEN_DIR, json_encode($result));
            return $token;
        }
        return false;
    }

    public function getSignPackage($url)
    {
        $jsapiTicket = $this->getJsApiTicket();
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        if ($url == '')
            $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";


        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $this->appid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    public function writeTest()
    {
        WechatLib::setJsonFile(JSAPI_TICKET_DIR,'1111');
    }

    private function getJsApiTicket()
    {
        $tickFile = WechatLib::getJsonFile(JSAPI_TICKET_DIR);

        if ($tickFile)
            $data = json_decode($tickFile);
        else
            return false;
        if (isset($data->expire_time) && isset($data->jsapi_ticket)) {
            $f_expire = $data->expire_time;
            if ($f_expire > time()) {
                return $data->jsapi_ticket;
            }
        }
        $accessToken = $this->getToken();
        // 如果是企业号用以下 URL 获取 ticket
        // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
        $result = WechatLib::sendRequest($url);
        if (is_array($result) && isset($result['ticket'])) {
            $res = [
                'jsapi_ticket' => $result['ticket'],
                'expire_time' => time() + (int)$result['expires_in']
            ];
            WechatLib::setJsonFile(JSAPI_TICKET_DIR, json_encode($res));
            return $result['ticket'];
        } else {
            return false;
        }
    }


    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


}
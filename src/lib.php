<?php

namespace sfsoft\wechat;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
define("ACCESS_TOKEN_DIR", __DIR__ . DIRECTORY_SEPARATOR . "token" . DIRECTORY_SEPARATOR . 'access_token.php');
define("JSAPI_TICKET_DIR", __DIR__ . DIRECTORY_SEPARATOR . "token" . DIRECTORY_SEPARATOR . 'jsapi_ticket.php');


class lib
{

    public static function initDataBase($database)
    {
        try {
            $capsule = new DB;
            // 创建链接
            $capsule->addConnection($database);
            // 设置全局静态可访问
            $capsule->setAsGlobal();
            // 启动Eloquent
            $capsule->bootEloquent();

            $isExists = DB::schema()->hasTable(DB_NAME);
            if ($isExists === false) {
                DB::schema()->create(DB_NAME, function (Blueprint $table) {
                    $table->increments('f_id');
                    $table->string('f_openid');
                    $table->string('f_token');
                    $table->string('f_token_expire');
                    $table->string('f_nickname');
                    $table->string('f_sex');
                    $table->string('f_country');
                    $table->string('f_province');
                    $table->string('f_city');
                    $table->string('f_headimgurl');
                    $table->string('f_unionid')->nullable();
                    $table->text('f_json');
                    $table->dateTime('f_create');
                    $table->dateTime('f_update')->nullable();
                });
            }

            return $capsule;

        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     *
     * 获取基础token
     *
     */

    public static function getAccessToken($appid, $secret)
    {
        $expireTime = 0;
        $tokenFile = self::getJsonFile(ACCESS_TOKEN_DIR);
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

        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$secret";
        $result = lib::sendRequest($tokenUrl);
        if (is_array($result) && isset($result['errcode'])) {
            return false;
        }
        if (is_array($result) && !isset($result['errcode'])) {
            $expireTime = time() + (int)$result['expires_in'];
            $token = $result['access_token'];
            $result['expire_time'] = $expireTime;
            self::setJsonFile(ACCESS_TOKEN_DIR, json_encode($result));
            return $token;
        }
        return false;
    }

    /**
     *
     * 网络请求
     *
     */
    public static function sendRequest($url)
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


    /**
     *
     * 获取json数据
     *
     * @return string
     */
    public static function getJsonFile($filename)
    {
        return trim(substr(file_get_contents($filename), 15));
    }

    /**
     *
     * 设置json数据
     *
     * @return string
     */
    public static function setJsonFile($filename, $content)
    {
        $fp = fopen($filename, "w");
        fwrite($fp, "<?php exit();?>" . $content);
        fclose($fp);
    }

}
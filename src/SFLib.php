<?php

namespace sfsoft\wechat;

class SFLib
{
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
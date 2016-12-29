<?php

namespace Crisen\LaravelWeixinpay\payment;


use Crisen\LaravelWeixinpay\Exception\WxpayException;


abstract class WxpayDataBase
{
    protected $values = array();
    protected $key;
    protected $appid;
    protected $mchid;
    protected $url;
    protected $notify_url;


    public function __construct()
    {
        $config = config('weixinpay');
        $this->key = $config['key'];
        $this->appid = $config['appid'];
        $this->mchid = $config['mch_id'];
        $this->notify_url = $config['notifyUrl'];
        $this->init();
    }

    public function init()
    {

    }

    public function __get($key)
    {
        return $this->$key ?: '';
    }


    public function __set($key, $value)
    {
        if (isset($this->$key)) {
            $this->$key = $value;
        } else {
            throw new WxpayException("禁止设置该数据");
        }
    }


    public function getValue($value = false)
    {
        if ($value) {
            return $this->values[$value] ?: false;
        } else {
            return $this->values;
        }
    }


    public function setValue($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function isExist($key)
    {
        return array_key_exists($key, $this->values);
    }


    public function SetSign()
    {
        $sign = $this->MakeSign();
        $this->values['sign'] = $sign;
        return $sign;
    }

    public function GetSign()
    {
        return $this->values['sign'];
    }


    protected function checkSign()
    {
        if (!$this->isExist('sign')) {
            throw new WxpayException("签名不存在！");
        }

        $sign = $this->getValue('sign');
        unset($this->getValue['sign']);
        if ($sign == $this->MakeSign()) {
            return true;
        } else {
            throw new WxpayException("签名错误！");
        }
    }


    public function ToXml()
    {
        if (!is_array($this->values)
            || count($this->values) <= 0
        ) {
            throw new WxpayException("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($this->values as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }


    public function FromXml($xml)
    {
        if (!$xml) {
            throw new WxpayException("xml数据异常！");
        }
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }


    public function ToUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }


    public function MakeSign()
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }


    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvWeixinyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    protected function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);

        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new WxpayException("curl出错，错误码:$error");
        }
    }


    public function options($options = [])
    {
        if (is_array($options)) {
            foreach ($options as $key => $val) {
                $this->setValue($key, $val);
            }
        }
        return $this;
    }


    protected function throwException($message)
    {
        throw new WxpayException($message);
    }
}
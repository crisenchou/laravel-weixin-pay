<?php

namespace Crisen\LaravelWeixinpay\payment;


use Crisen\LaravelWeixinpay\Exception\WeixinPayException;

/**
 *
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出xml格式的参数、从xml读取数据对象等
 * @author widyhu
 *
 */
abstract class WxPayDataBase
{
    protected $values = array();
    protected $key;
    protected $appid;
    protected $mchid;
    protected $url;
    protected $notify_url;

    public function __construct()
    {
        $config = include __DIR__ . '/../config/weixinpay.php';
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
            throw new WeixinPayException("禁止设置该数据");
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


    abstract function checkParams();


    /**
     * 设置签名，详见签名生成算法
     * @param string $value
     **/
    public function SetSign()
    {
        $sign = $this->MakeSign();
        $this->values['sign'] = $sign;
        return $sign;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return 值
     **/
    public function GetSign()
    {
        return $this->values['sign'];
    }

    /**
     * 判断签名，详见签名生成算法是否存在
     * @return true 或 false
     **/
    public function IsSignSet()
    {
        return array_key_exists('sign', $this->values);
    }

    /**
     *
     * 检测签名
     */
    public function CheckSign()
    {
        if (!$this->IsSignSet()) {
            $this->throwException("签名错误！");
        }

        $sign = $this->MakeSign();
        if ($this->GetSign() == $sign) {
            return true;
        }
        $this->throwException("签名错误！");
    }


    /**
     * 输出xml字符
     * @throws WeixinPayException
     **/
    public function ToXml()
    {
        if (!is_array($this->values)
            || count($this->values) <= 0
        ) {
            throw new WeixinPayException("数组数据异常！");
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

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WeixinPayException
     */
    public function FromXml($xml)
    {
        if (!$xml) {
            throw new WeixinPayException("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }

    /**
     * 格式化参数格式化成url参数
     */
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

    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
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

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return string $str
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvWeixinyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml 需要post的xml数据
     * @param string $url url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second url执行超时时间，默认30s
     * @throws WeixinPayException
     */
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

//        if ($useCert == true) {
//            //设置证书
//            //使用证书：cert 与 key 分别属于两个.pem文件
//            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
//            curl_setopt($ch, CURLOPT_SSLCERT, WeixinPayConfig::SSLCERT_PATH);
//            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
//            curl_setopt($ch, CURLOPT_SSLKEY, WeixinPayConfig::SSLKEY_PATH);
//        }

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
            throw new WeixinPayException("curl出错，错误码:$error");
        }
    }


    protected function throwException($message)
    {
        throw new WeixinPayException($message);
    }
}
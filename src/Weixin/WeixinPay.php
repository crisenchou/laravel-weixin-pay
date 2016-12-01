<?php


namespace crisen\weixinPay\Weixin;

use crisen\weixinPay\Exception\WeixinPayException;

class WeixinPay
{

    public $url;
    public $values;
    public $key;
    public $appid;
    public $mchid;
    public $notify_url;

    public function __construct()
    {
        $config = include __DIR__ . '/../config/weixinpay.php';
        $this->appid = $config['appid'];
        $this->mchid = $config['mch_id'];
        $this->notify_url = $config['notifyUrl'];
        $this->key = $config['key'];
    }

    public function createOrder()
    {
        //配置参数
        $this->values['appid'] = $this->appid;
        $this->values['mch_id'] = $this->mchid;
        $this->values['notify_url'] = $this->notify_url;
        //固定数据
        $this->values['trade_type'] = 'NATIVE';//交易方式
        $this->values['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];//终端IP
        $this->values['nonce_str'] = $this->getNonceStr();
        $this->values['sign'] = $this->MakeSign();
    }

    public function setOrder($options)
    {
        //订单信息
        $this->values['body'] = $options['body'];
        $this->values['out_trade_no'] = $options['out_trade_no'];
        $this->values['total_fee'] = $options['total_fee'];
    }


    public function unifiedOrder()
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $xml = $this->ToXml();
        $response = $this->postXmlCurl($xml, $url);
        return $this->Init($response);
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
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml 需要post的xml数据
     * @param string $url url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second url执行超时时间，默认30s
     * @throws WeixinPayException
     */
    private static function postXmlCurl($xml, $url, $second = 30)
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

        //post提交方式
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


    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return @str
     */
    public function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvWeixinyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    /**
     * 将xml转为array
     * @param string $xml
     * @throws WeixinPayException
     */
    public static function Init($xml)
    {
        $obj = new self();
        $obj->FromXml($xml);
        if ($obj->values['return_code'] != 'SUCCESS') {
            return $obj->GetValues();
        }
        $obj->CheckSign();
        return $obj->GetValues();
    }


    /**
     *
     * 检测签名
     */
    public function CheckSign()
    {
        //fix异常
        if (!$this->IsSignSet()) {
            throw new WeixinPayException("签名错误！");
        }

        $sign = $this->MakeSign();
        if ($this->GetSign() == $sign) {
            return true;
        }
        throw new WeixinPayException("签名错误！");
    }

    /**
     *
     * 使用数组初始化
     * @param array $array
     */
    public function FromArray($array)
    {
        $this->values = $array;
    }

    /**
     *
     * 使用数组初始化对象
     * @param array $array
     * @param bool $noCheckSign 是否检测签名
     */
    public static function InitFromArray($array, $noCheckSign = false)
    {
        $obj = new self();
        $obj->FromArray($array);
        if ($noCheckSign == false) {
            $obj->CheckSign();
        }
        return $obj;
    }

    /**
     *
     * 设置参数
     * @param string $key
     * @param string $value
     */
    public function SetData($key, $value)
    {
        $this->values[$key] = $value;
    }


    /**
     * 获取设置的值
     */
    public function GetValues()
    {
        return $this->values;
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
     * 获取签名，详见签名生成算法的值
     * @return value
     **/
    public function GetSign()
    {
        return $this->values['sign'];
    }


    public function getPayUrl($result)
    {
        if ('SUCCESS' == $result["return_code"] && 'SUCCESS' == $result["result_code"]) {
            return $result["code_url"];
        } else {
            return false;
        }
    }
}
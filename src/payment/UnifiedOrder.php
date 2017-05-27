<?php

namespace Crisen\LaravelWeixinpay\payment;


class UnifiedOrder extends DataBase
{

    public $request;

    private $needle = [
        'appid',
        'mch_id',
        'body',
        'total_fee',
        'trade_type',
        'nonce_str',
        'spbill_create_ip',
        'notify_url',
    ];


    public function init()
    {
        parent::init();
        $this->setValue('appid', $this->appid);
        $this->setValue('mch_id', $this->mchid);
        $this->url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $this->setValue('nonce_str', $this->getNonceStr());
        $this->setValue('spbill_create_ip', \Request::ip());
        $this->setValue('notify_url', $this->notify_url);
        $this->setValue('trade_type', 'NATIVE');
        $this->setValue('product_id', time());
    }

    public function checkParams()
    {
        foreach ($this->needle as $needle) {
            if (!$this->isExist($needle)) {
                $this->throwException("缺少统一支付接口必填参数{$needle}！");
            }
        }
        //关联参数
        if ($this->getValue('trade_type') == "JSAPI" && !$this->isExist('openid')) {
            $this->throwException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }

        if ($this->getValue('trade_type') == "NATIVE" && !$this->isExist('product_id')) {
            $this->throwException("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
        }
    }

    //获取支付url
    public function send()
    {
        $this->checkParams();
        $this->SetSign();
        $xml = $this->ToXml();
        $response = $this->postXmlCurl($xml, $this->url, false);
        $this->FromXml($response);
        if ($this->values['return_code'] == 'SUCCESS') {
            $this->request = $this->getValue();
        } else {
            $this->throwException('sign error');
        }
        return $this;
    }


    public function isSuccessful()
    {
        if ('SUCCESS' == $this->request['result_code'] && 'SUCCESS' == $this->request['return_code']) {
            return true;
        }
        return false;
    }

    public function getCodeUrl()
    {
        return $this->request['code_url'];
    }
}
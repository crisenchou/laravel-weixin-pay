<?php

namespace Crisen\LaravelWeixinpay\payment;


class WxpayOrderQuery extends WxpayDataBase
{

    public $request;

    public function init()
    {
        parent::init();
        $this->setValue('appid', $this->appid);
        $this->setValue('mch_id', $this->mchid);
        $this->setValue('nonce_str', $this->getNonceStr());//随机字符串
        $this->url = "https://api.mch.weixin.qq.com/pay/orderquery";
    }

    public function checkParams()
    {
        if (!$this->isExist('out_trade_no') && !$this->isExist('transaction_id')) {
            $this->throwException("订单查询接口中，out_trade_no、transaction_id至少填一个！");
        }
    }

    public function send()
    {
        $this->checkParams();
        $this->SetSign();
        $xml = $this->ToXml();
        $response = $this->postXmlCurl($xml, $this->url, false);
        $this->request = $this->FromXml($response);
        return $this;
    }

    /*
     *  @return bool
     */
    public function isSuccessful()
    {
        if ('SUCCESS' == $this->request['result_code'] && 'SUCCESS' == $this->request['return_code']) {
            return true;
        } else {
            return false;
        }
    }

    /*
    *  @return bool
    */
    public function isPaid()
    {
        if ('SUCCESS' == $this->request['trade_state']) {
            return true;
        }
        return false;
    }

}
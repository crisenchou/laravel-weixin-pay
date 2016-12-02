<?php

namespace Crisen\LaravelWeixinpay\Weixin;


class WeixinPayOrderQuery extends WeixinPayDataBase
{
    public function init()
    {
        parent::init();
        $this->setValue('appid', $this->appid);
        $this->setValue('mch_id', $this->mchid);
        $this->setValue('nonce_str',$this->getNonceStr());//随机字符串
        $this->url = "https://api.mch.weixin.qq.com/pay/orderquery";
    }

    public function checkParams()
    {
        //检测必填参数
        if (!$this->isExist('out_trade_no') && !$this->isExist('transaction_id')) {
            $this->throwException("订单查询接口中，out_trade_no、transaction_id至少填一个！");
        }
    }

    public function orderQuery()
    {
        $this->checkParams();
        $this->SetSign();
        $xml = $this->ToXml();
        $response = $this->postXmlCurl($xml, $this->url, false);
        return $this->FromXml($response);
    }


}
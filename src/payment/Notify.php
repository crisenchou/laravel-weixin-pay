<?php

namespace Crisen\LaravelWeixinpay\payment;


use Illuminate\Http\Request;


class Notify extends DataBase
{


    public function options(Request $request)
    {
        $xml = $request->getContent();
        $this->FromXml($xml);
        return $this;
    }


    public function isSuccessful()
    {
        if (isset($this->values['return_code']) && 'SUCCESS' == $this->values['return_code']) {
            return true;
        } else {
            return false;
        }
    }


    public function isPaid()
    {

        if (!$this->checkSign()) {
            return false;
        }

        if ('SUCCESS' == $this->values['result_code']) {
            return true;
        } else {
            return false;
        }
    }

    public function getOutTradeNo()
    {
        return $this->values['out_trade_no'];
    }


    public function reply()
    {
        $reply = new WxpayReply();
        $reply->setValue('return_code', 'SUCCESS');
        $reply->setValue('return_msg', 'SUCCESS');
        return $reply->ToXml();
    }
}
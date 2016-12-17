<?php

namespace Crisen\LaravelWeixinpay\payment;


use Illuminate\Http\Request;

/**
 *
 * 回调基础类
 * @author widyhu
 *
 */
class WxpayNotifyReply extends WxpayDataBase
{

    public $request;

    public function checkParams()
    {
        //
    }


    public function options(Request $request)
    {
        $xml = $request->getContent();
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $this->request = $result;
        return $this;
    }


    public function isSuccessful()
    {
        if (isset($this->request['return_code']) && 'SUCCESS' == $this->request['return_code']) {
            return true;
        } else {
            info('notify error');
            return false;
        }

    }


    public function isPaid()
    {
        if ('SUCCESS' == $this->request['result_code']) {
            return true;
        } else {
            return false;
        }
    }

    public function getOutTradeNo()
    {
        return $this->request['out_trade_no'];
    }


    public function reply()
    {
        $this->setValue('return_code', 'SUCCESS');
        $this->setValue('return_msg', 'SUCCESS');
        return $this->ToXml();
    }

    /**
     *
     * 设置错误码 FAIL 或者 SUCCESS
     * @param string
     */
    public function SetReturn_code($return_code)
    {
        $this->values['return_code'] = $return_code;
    }

    /**
     *
     * 获取错误码 FAIL 或者 SUCCESS
     * @return string $return_code
     */
    public function GetReturn_code()
    {
        return $this->values['return_code'];
    }

    /**
     *
     * 设置错误信息
     * @param string $return_code
     */
    public function SetReturn_msg($return_msg)
    {
        $this->values['return_msg'] = $return_msg;
    }

    /**
     *
     * 获取错误信息
     * @return string
     */
    public function GetReturn_msg()
    {
        return $this->values['return_msg'];
    }
}
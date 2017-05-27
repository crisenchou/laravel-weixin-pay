<?php

namespace Crisen\LaravelWeixinpay;


class WxpayFactory
{

    public static function factory($gateway)
    {
        $className = 'Crisen\LaravelWeixinpay\payment\\' . ucfirst($gateway);
        if (class_exists($className)) {
            return new $className;
        } else {
            throw new \Exception('gateway is wrong');
        }
    }
}
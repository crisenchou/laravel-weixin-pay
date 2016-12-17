<?php

namespace Crisen\LaravelWeixinpay;


class WxpayFactory
{

    public static function factory($gateway)
    {
        $classname = 'Crisen\LaravelWeixinpay\payment\Wxpay' . ucfirst($gateway);
        if (class_exists($classname)) {
            return new $classname;
        } else {
            throw new \Exception('gateway is wrong');
        }
    }
}
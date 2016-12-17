<?php

namespace Crisen\LaravelWeixinpay\Facades;


use Illuminate\Support\Facades\Facade;

class Wxpay extends Facade
{
    public static function getFacadeAccessor()
    {
        return "Wxpay";
    }
}
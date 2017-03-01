<?php

namespace Crisen\LaravelWeixinpay\test;

use Crisen\LaravelWeixinpay\Facades\Wxpay;

class PayTest extends PHPUnit_Framework_TestCase
{
    public function testUnifiorder()
    {
        $pay = Wxpay::factory('UnifiedOrder');
        $payment = $pay->options([
            'body' => 'test goods',
            'out_trade_no' => time(),
            'total_fee' => 1
        ])->send();
        if ($payment->isSuccessful()) {
            assert('true');
        }
    }
}
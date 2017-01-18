# laravel -Weixinpay 微信支付

> 微信支付  目前只有扫码支付 持续完善中

## 安装

> 使用composer require命令进行安装

~~~
composer require "crisen/laravel-weixinpay":"dev-master"
~~~

> 或者在composer.json中添加

~~~
"require": {
		....
        "crisen/laravel-weixinpay": "~1.0*"
 },
~~~

## 配置

> 注册服务提供者(Service Provider)

~~~
'providers' => [  
    ...
    Crisen\LaravelWeixinpay\WxpayServiceProvider::class,
}
~~~

> 添加门面(Facade)

~~~
'aliases' => [
    ...
	'Wxpay' => Crisen\LaravelWeixinpay\Facades\Wxpay::class
]
~~~

> 配置文件部署

~~~
php artisan vendor:publish
~~~

## 使用方法

#### 统一下单--扫码支付

~~~
 $pay = Wxpay::factory('UnifiedOrder');
 $payment = $pay->options([
     'body' => $order->body,
     'out_trade_no' => $order->orderid,
     'total_fee' => 1
 ])->send();
 if ($payment->isSuccessful()) {
     dump($payment->getCodeUrl());
 }
~~~

### 订单查询

~~~
   $payment = Wxpay::factory('OrderQuery')->options([
       'out_trade_no' => 'xxxxxxx'//订单号
   ])->send();
   if ($payment->isSuccessful() && $payment->isPaid()) {
       //do something
   }
~~~

### 异步通知

~~~
	$payment = Wxpay::factory('notify')->options($request);
	if ($payment->isSuccessful()) {
        //$outTradeNo = $payment->getOutTradeNo(); //do something with $outTradeNo
        $reply = $payment->reply();
        return response($reply);
    }
~~~

## License

MIT

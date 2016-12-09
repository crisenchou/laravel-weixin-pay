<?php


namespace Crisen\LaravelWeixinpay;

use Illuminate\Support\ServiceProvider;

class WeixinpayServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/weixinpay.php' => config_path('weixinpay.php'),
        ]);
    }

    public function register()
    {
        
    }

}

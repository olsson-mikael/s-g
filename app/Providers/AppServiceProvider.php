<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Payout;
use Illuminate\Support\Facades\Validator;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
	    Validator::extend('possiblecashback', function ($attribute, $value, $parameters, $validator) {
		    $payout = new Payout();
		    $totalPossibleCashback = $payout->totalPossibleCashbackNow();
		    return $value <= $totalPossibleCashback;
	    });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

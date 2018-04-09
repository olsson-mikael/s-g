<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Partner;
use App\Transaction;
use App\Payout;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     *  Portal dashboard.
     */
    public function index()
    {
	    $transaction = new Transaction();
	    $payout = new Payout();

	    $totalBalance           = $transaction->totalBalance();
	    $totalPossibleCashback  = $payout->totalPossibleCashbackNow();

	    $accountsOverviewCurrentMonth   = $transaction->accountsOverviewCurrentMonth();
	    $sumsCurrentMonth               = $transaction->getSums($accountsOverviewCurrentMonth);

		if($sumsCurrentMonth['total_balance'] == '') {
			$sumsCurrentMonth['total_balance'] = '0';
		}

	    $newestPartners = Partner::getNewestPartners();

        return view('home', [
        	'partners'                  => $newestPartners,
	        'totalPossibleCashback'     => $totalPossibleCashback,
	        'totalBalance'              => $totalBalance,
	        'sumsCurrentMonth'          => $sumsCurrentMonth,
        ]);
    }
}

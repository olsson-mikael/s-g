<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Payout;
use App\Http\Controllers\Controller;
use Validator;
use Carbon\Carbon;



class PayoutController extends Controller
{
    public function index() {
    	$payout = new Payout();

	    $totalPossibleCashback  = $payout->totalPossibleCashbackNow();
	    $totalFutureCashback    = $payout->totalFutureCashback();
	    $nextPossiblePayoutDate = $payout->nextPossiblePayoutDate();
	    $userPayoutHistory      = $payout->userPayoutHistory();

	    $lastDayOfPreviousMonth = new Carbon('last day of last month');

	    $transactionsBeforeThisDate = $lastDayOfPreviousMonth->todatestring();

	    return view('payout', [
		    'transactionsBeforeThisDate'    => $transactionsBeforeThisDate,
	    	'totalPossibleCashback'         => $totalPossibleCashback,
		    'totalFutureCashback'           => $totalFutureCashback,
		    'nextPossiblePayoutDate'        => $nextPossiblePayoutDate,
		    'userPayoutHistory'             => $userPayoutHistory
	    ]);
    }


    public function store(Request $request) {
	    $payout = new Payout();

	    $rules =
		    [
			    'bankaccount'           => 'required|max:15',
			    'payoutSum'             => 'required|possiblecashback',
			    'account_holder_name'   => 'required'
		    ];

	    $messages =
		    [
			    'bankaccount'                   => 'Något stämmer inte med ditt kontonummer',
			    'payoutSum.possiblecashback'    => 'Du har angivet en ogiltlig summa',
			    'account_holder_name'           => 'Mottagarens namn måste anges'
		    ];

	    $validator = Validator::make($request->all(), $rules, $messages);

	    if ($validator->fails()) {
		    return redirect('payout')
			    ->withErrors($validator)
			    ->withInput();
	    }

	    $payout->savePayout($request);

	    return redirect()->back()->with('message', 'Din utbetalning är sparad, du kommer ha pengarna på ditt bankkonto inom några dagar!');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;
use App\Partner;


class TransactionController extends Controller
{
    public function index() {

	    $transaction = new Transaction();
	    $accountsOverviewTotal  = $transaction->accountsOverviewTotal();
	    $totalSums              = $transaction->getTotalSums();

	    $accountsOverviewCurrentMonth   = $transaction->accountsOverviewCurrentMonth();
	    $sumsCurrentMonth               = $transaction->getSums($accountsOverviewCurrentMonth);

	    $accountsOverviewPreviousMonth  = $transaction->accountsOverviewPreviousMonth();
	    $sumsLastMonth                  = $transaction->getSums($accountsOverviewPreviousMonth);


	    return view('accounts', [
	    	'allAccountsTotal'            => $accountsOverviewTotal,
		    'totalSums'                   => $totalSums,
		    'allAccountsCurrentPeriod'    => $accountsOverviewCurrentMonth,
		    'sumsCurrentMonth'            => $sumsCurrentMonth,
		    'allAccountsPreviousPeriod'   => $accountsOverviewPreviousMonth,
		    'sumsLastMonth'               => $sumsLastMonth,
	    ]);
    }


	/**
	 *
	 * NOT IMPLEMENTED
	 * TODO: Implement
	 * get specific details about a users gambling with a specific partner.
	 */
    public function getDetails($partnerId){
	    $transaction = new Transaction();
	    $allTransactionsDetailsThisPartner  = $transaction->allTransactionsDetailsThisPartner($partnerId);
	    $totalBalance                       = $transaction->totalBalanceThisPartner($partnerId);

	    $allTransactionsDetailsCurrentPeriodThisPartner = $transaction->allTransactionsDetailsCurrentPeriodThisPartner($partnerId);
	    $totalBalanceCurrentPeriod                      = $transaction->totalBalanceCurrentPeriodThisPartner($partnerId);
	    $totalCashbackCurrentPeriod                     = $transaction->totalCashbackCurrentPeriodThisPartner($partnerId);

	    $allTransactionsDetailsPreviousPeriodThisPartner = $transaction->allTransactionsDetailsPreviousPeriodThisPartner($partnerId);
	    $totalBalancePreviousPeriod                      = $transaction->totalBalancePreviousPeriodThisPartner($partnerId);
	    $totalCashbackPreviousPeriod                     = $transaction->totalCashbackPreviousPeriodThisPartner($partnerId);


	    $partner = Partner::find($partnerId);

	    return view('details', [
		    'allDetails'                  => $allTransactionsDetailsThisPartner,
		    'currentPeriod'               => $allTransactionsDetailsCurrentPeriodThisPartner,
		    'previousPeriod'              => $allTransactionsDetailsPreviousPeriodThisPartner,
		    'totalBalance'                => $totalBalance,
		    'totalBalanceCurrentPeriod'   => $totalBalanceCurrentPeriod,
		    'totalCashbackCurrentPeriod'  => $totalCashbackCurrentPeriod,
		    'totalBalancePreviousPeriod'  => $totalBalancePreviousPeriod,
		    'totalCashbackPreviousPeriod' => $totalCashbackPreviousPeriod,
		    'partner'                     => $partner
	    ]);

    }


}

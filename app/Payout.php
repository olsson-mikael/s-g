<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Auth;


class Payout extends Model
{
	public function user() {
		return $this->belongsTo('App\user');
	}

	/**
	 * Includes cashback that the user won't be able to claim until the 15th
	 */
	public function totalFutureCashback() {
		$totalCashback = DB::table('transactions')->select('cashback')->where('user_id', Auth::id())->sum('cashback');
		return $totalCashback - $this->totalPayout();
	}


	public function totalPossibleCashbackNow() {
		$totalCashback = $this->totalCashback();
		$totalPayout = $this->totalPayout();

		$totalPossibleCashback = round($totalCashback - $totalPayout);
		return $totalPossibleCashback;
	}


	public function totalCashback() {
		DB::select('
			SELECT SUM(monthly_cashback) as total_cashback
			FROM users_result
			WHERE user_id = :authUserId
			AND end_date <= :beforeThisDate',
			['authUserId' => Auth::id(), 'beforeThisDate' => $this->transactionsBeforeThisDate()]);
	}


	public function totalPayout() {
		DB::select('
			SELECT SUM(sum) as sum
			FROM payouts
			WHERE user_id = :authUserId',
			['authUserId' => Auth::id()]);
	}

	/**
	 * TODO: come up with a better method name
	 * Calculate cashback based on transacations before this date.
	 */
	public function transactionsBeforeThisDate() {
		$today = Carbon::now('Europe/Stockholm');

		if($today->day >= 15) {
			$lastDayOfPreviousMonth = new Carbon('last day of last month');
			return $lastDayOfPreviousMonth->toDateString();
		}

		$twoMonthsAgo = Carbon::createFromDate($today->year, $today->month -2, 1, $today->tz);
		return $twoMonthsAgo->endOfMonth()->toDateString();

	}


	public function nextPossiblePayoutDate() {
		$today = Carbon::now('Europe/Stockholm');

		if($today->day > 15) {
			return Carbon::createFromDate($today->year, $today->month +1, 15, $today->tz);
		}
		return Carbon::createFromDate($today->year, $today->month, 15, $today->tz);
	}


	public function userPayoutHistory() {
		return DB::table('payouts')->select('created_at', 'transfer_account', 'sum')->where('user_id', Auth::id())->latest()->get();
	}


	public function savePayout($request) {
		var_dump($request);
		DB::insert('
			INSERT INTO payouts (user_id, account_name, transfer_account, sum, created_at)
			VALUES (:authUserId, :account_name, :bankaccount, :payoutSum, :now)',
			['authUserId' => Auth::id(), 'account_name' => $request->account_holder_name, 'bankaccount' => $request->bankaccount, 'payoutSum' => $request->payoutSum, 'now' => Carbon::now('Europe/Stockholm')]);
	}

}

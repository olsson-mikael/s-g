<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Auth;
use Carbon\Carbon;



class Transaction extends Model
{
	public function user()
	{
		return $this->belongsTo('App\user');
	}

	public function partner()
	{
		return $this->belongsTo('App\partner');
	}


	public function firstDayOfCurrentMonth() {
		return Carbon::now()->startOfMonth();
	}


	public function previousPeriod() {
		$startDate = new Carbon('first day of last month');
		$endDate = new Carbon('last day of last month');

		return array('startDate' => $startDate, 'endDate' => $endDate);
	}


	/**
	 * TODO: REFACTOR!!
	 * Takes the object with accountData and returns an array with the sums.
	 * Used for summarizing the total cashback of multiple partners.
	 */
	public function getSums($accountData) {
		$keys = array('total_balance', 'total_cashback_in_currency');
		$sum = array_fill_keys($keys, "");

		foreach($accountData as $account) {
			$sum['total_balance'] += $account->total_balance;
			$sum['total_cashback_in_currency'] += $account->cashback_in_currency;
		}

		$sum['total_cashback_in_currency'] = round($sum['total_cashback_in_currency']);

		return $sum;
	}

	/**
	 * Returns the preliminary cashback data for a users gambling based on entries in the current month
	 */
	public function accountsOverviewCurrentMonth() {
		 $accountsOverviewCurrentMonth = DB::select(
			'SELECT
				partners.id, partners.image, partners.login_link, partners.name, partners.company_group, by_groups.cashback_in_percentage, by_partners.latest_transaction, by_partners.total_balance
				FROM partners
				JOIN
				(
					SELECT partners.company_group, round(SUM(transactions.balance) / SUM(CASE WHEN transactions.balance < 0 THEN transactions.balance ELSE 0 END)
					* (partners.default_cashback / 100) * 100, 2) AS cashback_in_percentage
					FROM transactions
					JOIN partners ON transactions.partner_id = partners.id
					WHERE transactions.transaction_date >= CAST(:startOfCurrentMonth AS DATE)
					GROUP BY partners.company_group
				) AS by_groups
				ON by_groups.company_group = partners.company_group
				JOIN
				(
					SELECT partners.id, MAX(transactions.transaction_date) AS latest_transaction, SUM(transactions.balance) AS total_balance
					FROM partners
					JOIN transactions ON partners.id = transactions.partner_id
					WHERE transactions.transaction_date >= CAST(:alsoFirstDayOfMonth AS DATE) AND transactions.user_id = :authUserID
					GROUP BY transactions.partner_id
				) AS by_partners
				ON by_partners.id = partners.id
				GROUP BY by_partners.id',
			['startOfCurrentMonth' => $this->firstDayOfCurrentMonth(), 'alsoFirstDayOfMonth' => $this->firstDayOfCurrentMonth(), 'authUserID' => Auth::id()]);

		 foreach($accountsOverviewCurrentMonth as $account) {
		 	$account->cashback_in_currency = 0;

		 	if($account->cashback_in_percentage < 0) {
			  $account->cashback_in_percentage = 0;
		 	}

		 	if($account->total_balance < 0) {
		 		$account->cashback_in_currency = round($account->cashback_in_percentage / 100 * ($account->total_balance * -1));
		 	}
		 }

		 return $accountsOverviewCurrentMonth;
	}

	/**
	 * Returns the cashback data for a users gambling based on entries in the previous month
	 */
	public function accountsOverviewPreviousMonth() {
		$dates = $this->previousPeriod();
		$accountsOverviewPreviousMonth = DB::select(
			'SELECT
				partners.id, partners.image, partners.login_link, partners.name, partners.company_group, by_groups.cashback_in_percentage, by_partners.latest_transaction, by_partners.total_balance
				FROM partners
				JOIN
				(
					SELECT partners.company_group, round(SUM(transactions.balance) / SUM(CASE WHEN transactions.balance < 0 THEN transactions.balance ELSE 0 END)
					* (partners.default_cashback / 100) * 100, 2) AS cashback_in_percentage
					FROM transactions
					JOIN partners ON transactions.partner_id = partners.id
					WHERE transactions.transaction_date >= CAST(:startOfMonth AS DATE)
					AND transactions.transaction_date <= CAST(:endOfMonth AS DATE)
					GROUP BY partners.company_group
				) AS by_groups
				ON by_groups.company_group = partners.company_group
				JOIN
				(
					SELECT partners.id, MAX(transactions.transaction_date) AS latest_transaction, SUM(transactions.balance) AS total_balance
					FROM partners
					JOIN transactions ON partners.id = transactions.partner_id
					WHERE transactions.transaction_date >= CAST(:alsoStartOfMonth AS DATE) 
					AND transactions.transaction_date <= CAST(:alsoEndOfMonth AS DATE) 
					AND transactions.user_id = :authUserID
					GROUP BY transactions.partner_id
				) AS by_partners
				ON by_partners.id = partners.id
				GROUP BY by_partners.id',
			['startOfMonth' => $dates['startDate'], 'endOfMonth' => $dates['endDate'], 'alsoStartOfMonth' => $dates['startDate'],
			 'alsoEndOfMonth' => $dates['endDate'], 'authUserID' => Auth::id()]);

		foreach($accountsOverviewPreviousMonth as $account) {
			$account->cashback_in_currency = 0;

			if($account->cashback_in_percentage < 0) {
				$account->cashback_in_percentage = 0;
			}

			if($account->total_balance < 0) {
				$account->cashback_in_currency = round($account->cashback_in_percentage / 100 * ($account->total_balance * -1));
			}
		}

		return $accountsOverviewPreviousMonth;
	}

	/**
	 * Returns the cashback data based on all gambling made by the user
	 */
	public function accountsOverviewTotal() {
			return DB::select('
				SELECT SUM(monthly_balance) AS total_balance, SUM(monthly_cashback) AS total_cashback, user_id, partner_id, 
				MIN(start_date) AS start_date, MAX(END_DATE) AS end_date, partners.image, partners.name, partners.login_link
				FROM users_result
				JOIN
				(
				SELECT partners.id, partners.image, partners.name, partners.login_link
				FROM partners
				) AS partners
				ON users_result.partner_id = partners.id
				WHERE user_id = :authUserID
				GROUP BY partner_id;',
		['authUserID' => Auth::id()]);
	}

	/**
	 * Total sums of all gambling made by the user
	 */
	public function getTotalSums() {
		$result =  DB::select('
			SELECT SUM(monthly_balance) AS total_balance, SUM(monthly_cashback) AS total_cashback
			FROM users_result
			WHERE user_id = :authUserID',
			['authUserID' => Auth::id()]);

		$resultArray = json_decode(json_encode($result), true);

		return $resultArray;

	}


	public function totalBalance() {
		return DB::table('transactions')
			->select('balance')
			->where('user_id', Auth::id())
			->sum('balance');
	}


	public function totalBalanceCurrentPeriod() {
		return DB::table('transactions')
			->select('balance')
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>=', $this->firstDayOfCurrentMonth())
			->sum('balance');
	}


	public function totalBalancePreviousPeriod() {
		$previousPeriod = $this->previousPeriod();

		return DB::table('transactions')
			->select('balance')
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $previousPeriod['startDate'])
			->where('transactions.transaction_date', '<', $previousPeriod['endDate'])
			->sum('balance');
	}


	public function totalCashbackCurrentPeriod() {
		return DB::table('transactions')
			->select('cashback')
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $this->firstDayOfCurrentMonth())
			->sum('cashback');
	}


	public function totalCashbackPreviousPeriod() {
		$previousPeriod = $this->previousPeriod();

		return DB::table('transactions')
			->select('cashback')
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $previousPeriod['startDate'])
			->where('transactions.transaction_date', '<', $previousPeriod['endDate'])
			->sum('cashback');
	}


	public function allTransactionsDetailsThisPartner($partnerId) {
		return DB::table('transactions')
			->select('cashback', 'balance', 'transaction_date')
			->where('partner_id', $partnerId)
			->where('user_id', Auth::id())
			->orderBy('transaction_date', 'desc')
			->paginate(31);
	}


	public function allTransactionsDetailsCurrentPeriodThisPartner($partnerId) {
		return DB::table('transactions')
			->select('cashback', 'balance', 'transaction_date')
			->where('partner_id', $partnerId)
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $this->firstDayOfCurrentMonth())
			->orderBy('transaction_date', 'desc')
			->get();
	}


	public function allTransactionsDetailsPreviousPeriodThisPartner($partnerId) {
		$previousPeriod = $this->previousPeriod();

		return DB::table('transactions')
			->select('cashback', 'balance', 'transaction_date')
			->where('partner_id', $partnerId)
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $previousPeriod['startDate'])
			->where('transactions.transaction_date', '<', $previousPeriod['endDate'])
			->orderBy('transaction_date', 'desc')
			->get();
	}


	public function totalBalanceThisPartner($partnerId) {
		return DB::table('transactions')
			->select('balance')
			->where('partner_id', $partnerId)
			->where('user_id', Auth::id())
			->sum('balance');
	}


	public function totalCashbackCurrentPeriodThisPartner($partnerId) {
		return DB::table('transactions')
			->select('cashback')
			->where('partner_id', $partnerId)
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $this->firstDayOfCurrentMonth())
			->sum('cashback');
	}


	public function totalBalanceCurrentPeriodThisPartner($partnerId) {
		return DB::table('transactions')
			->select('balance')
			->where('partner_id', $partnerId)
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $this->firstDayOfCurrentMonth())
			->sum('balance');
	}


	public function totalCashbackPreviousPeriodThisPartner($partnerId) {
		$previousPeriod = $this->previousPeriod();

		return DB::table('transactions')
			->select('cashback')
			->where('partner_id', $partnerId)
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $previousPeriod['startDate'])
			->where('transactions.transaction_date', '<', $previousPeriod['endDate'])
			->sum('cashback');
	}


	public function totalBalancePreviousPeriodThisPartner($partnerId) {
		$previousPeriod = $this->previousPeriod();

		return DB::table('transactions')
			->select('balance')
			->where('partner_id', $partnerId)
			->where('user_id', Auth::id())
			->where('transactions.transaction_date', '>', $previousPeriod['startDate'])
			->where('transactions.transaction_date', '<', $previousPeriod['endDate'])
			->sum('balance');
	}

}

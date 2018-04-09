<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
	public function transaction()
	{
		return $this->hasMany('App\transaction');
	}

	/**
	 * Preliminary cashback in the current period
	 */
	public function currentCashbackPerPartner() {
		$totalSum = DB::table('transactions')
			->select(DB::raw('sum(transactions.balance) as total_balance'))
			->where('transactions.partner_id', 1)
			->get();

		$totalLosses = DB::table('transactions')
			->select(DB::raw('sum(transactions.balance) as total_balance'))
			->where('transactions.balance' > 0)
			->get();

		// don't want to show negative value
		if($totalSum >= 0) {
			return 0;
		}

		$cashbackInDecimal = $totalLosses/$totalSum * 0.25;

		$cashbackPercentage = $cashbackInDecimal * 100;

		return $cashbackPercentage;
	}

	public static function getNewestPartners() {
		return DB::select(
			'
				SELECT partners.id, 
				partners.name, 
				partners.default_bonus, 
				partners.description, 
				partners.image,
				partners.default_cashback as preliminary_cashback,
				partners.referral_link
				FROM partners
				WHERE partners.featured = 1
				GROUP BY partners.id;
    		'
		);
	}

}

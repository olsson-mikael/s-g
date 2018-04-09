<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;

class MonthlySum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MonthlySum:monthlysum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and saves the cashback per partner and balance per user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	    $startDateTime = new Carbon('first day of last month');
	    $endDateTime = new Carbon('last day of last month');
	    $startDate = $startDateTime->toDateString();
	    $endDate = $endDateTime->toDateString();
	    $now = Carbon::now('Europe/Stockholm');
	    $nowAsString = $now->toDateTimeString();

	    $resultByPartners = DB::select('
					SELECT
						partners.id, by_groups.monthly_cashback_percentage
						FROM partners
						LEFT JOIN
						(
						SELECT partners.company_group, round(SUM(transactions.balance) / SUM(CASE WHEN transactions.balance < 0 THEN transactions.balance ELSE 0 END)
						* (partners.default_cashback / 100) * 100, 2) AS monthly_cashback_percentage
						FROM transactions
						JOIN partners ON transactions.partner_id = partners.id
						WHERE transactions.transaction_date >= CAST(:startOfLastMonth AS DATE)
						AND transactions.transaction_date <= CAST(:lastDayOfLastMonth AS DATE)
						GROUP BY partners.company_group
						) AS by_groups
						ON by_groups.company_group = partners.company_group
						GROUP BY partners.id',
		    ['startOfLastMonth' => $startDate, 'lastDayOfLastMonth' => $endDate]);

	    foreach($resultByPartners as $partner) {
		    $partner->start_date = $startDate;
		    $partner->end_date = $endDate;
		    $partner->partner_id = $partner->id;
		    $partner->created_at = $nowAsString;
		    unset($partner->id);
	    }

	    $resultByPartnersAsArray = json_decode(json_encode($resultByPartners), true);

	    DB::table('partners_result')->insert($resultByPartnersAsArray);

	    $resultByUsers = DB::select('
				SELECT transactions.user_id, transactions.partner_id, SUM(transactions.balance) AS monthly_balance
				FROM transactions
				WHERE transactions.transaction_date >= CAST(:startOfLastMonth AS DATE)
				AND transactions.transaction_date <= CAST(:lastDayOfLastMonth AS DATE)
				GROUP BY transactions.user_id, transactions.partner_id',
		    ['startOfLastMonth' => $startDate, 'lastDayOfLastMonth' => $endDate]);

	    foreach($resultByUsers as $user) {
		    $user->start_date = $startDate;
		    $user->end_date = $endDate;
		    $user->created_at = $nowAsString;
		    $partnerId = $user->partner_id;
		    unset($user->id);

		    if($user->monthly_balance < 0) {
			    $partnerData = array_filter($resultByPartnersAsArray, function ($partner) use ($partnerId) {
				    return $partner['partner_id'] == $partnerId;
			    });

			    $partnerDataAsArray = array_values($partnerData);
			    $user->monthly_cashback = round($partnerDataAsArray[0]['monthly_cashback_percentage'] / 100 * (-1 * $user->monthly_balance));
		    }
	    }

	    $resultByUsersAsArray = json_decode(json_encode($resultByUsers), true);

	    DB::table('users_result')->insert($resultByUsersAsArray);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Partner;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{

	/**
	 * Returns data about all the partners
	 */
    public function index() {

	    $allPartners = DB::select(
		    '
				SELECT partners.id, 
				partners.name, 
				partners.default_bonus, 
				partners.description, 
				partners.image,
				partners.default_cashback as preliminary_cashback,
				partners.referral_link
				FROM partners
				GROUP BY partners.id;
    		'
	    );

	    return view('partners', ['partners' => $allPartners]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Commissions;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        //$this->middleware(['auth', 'verified']);
        $this->middleware(['auth']);
    }

    public function index()
    {
        $userid =  Auth::user()->id;
        $withdrawals = Withdrawal::where('user_id', $userid)->sum('amount');
        $commissionstotal =  Commissions::where('user_id', $userid)->sum('commission');
        $pageTitle =  "Dashboard";



        return view('dashboard.home', [
            'withdrawals' => $withdrawals,
            'commissionstotal' => $commissionstotal,
            'pageTitle' => $pageTitle
        ]);
    }





    public function page()
    {
        $pageTitle =  "Page";

        return view(
            'dashboard.page',
            ['pageTitle' =>  $pageTitle]
        );
    }

    public function data()
    {
        $pageTitle =  "Purchase Data";

        return view(
            'dashboard.data',
            ['pageTitle' =>  $pageTitle]
        );
    }

    public function airtime()
    {
        $pageTitle =  "Purchase airtime";

        return view(
            'dashboard.airtime',
            ['pageTitle' =>  $pageTitle]
        );
    }

    public function airtime2cash()
    {
        $pageTitle =  "Purchase airtime to cash";

        return view(
            'dashboard.airtime2cash',
            ['pageTitle' =>  $pageTitle]
        );
    }


    public function commissions()
    {
        $pageTitle =  "Commissions";

        return view(
            'dashboard.commissions',
            ['pageTitle' =>  $pageTitle]
        );
    }

    public function withdrawals()
    {
        $pageTitle =  "Withdrawals";

        return view(
            'dashboard.withdrawals',
            ['pageTitle' =>  $pageTitle]
        );
    }

    public function profile()
    {
        $pageTitle =  "Profile";
        return view('dashboard.profile', ['pageTitle' =>  $pageTitle]);
    }
}

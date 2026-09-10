<?php

namespace App\Http\Controllers;

use App\Http\Resources\DispatchResource;
use App\Models\Dispatch;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{

    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index()
    {
        $userscount =  User::count();
        $offtakerscount =  User::where('role', '0')->count();
        $agentscount =  User::where('role', '2')->count();
        $productscount =  Product::count();
        $pageTitle =  "Dashboard";



        return view('admin.home', [
            'usercount' =>  $userscount,
            'offtakerscount' => $offtakerscount,
            'productscount' => $productscount,
            'agentscount' => $agentscount,
            'pageTitle' => $pageTitle
        ]);
    }

    public function users($role)
    {
        $pageTitle =  "Users";
        return view('admin.users', ['pageTitle' =>  $pageTitle, 'role' => $role]);
    }


    public function user_details($id)
    {
        $pageTitle =  "Users";
        return view('admin.user_details', ['pageTitle' =>  $pageTitle, 'id' => $id]);
    }



    public function profile()
    {
        $pageTitle =  "Profile";
        return view('admin.profile', ['pageTitle' =>  $pageTitle]);
    }



    public function settings()
    {
        $pageTitle =  "Settings";
        return view('admin.settings', ['pageTitle' =>  $pageTitle]);
    }

  
    public function products()
    {
        $pageTitle =  "Products";

        return view(
            'admin.products',
            ['pageTitle' =>  $pageTitle]
        );
    }

    public function commissions()
    {
        $pageTitle =  "Commissions";

        return view(
            'admin.commissions',
            ['pageTitle' =>  $pageTitle]
        );
    }

    public function withdrawals()
    {
        $pageTitle =  "Withdrawals";

        return view(
            'admin.withdrawals',
            ['pageTitle' =>  $pageTitle]
        );
    }

    public function categories()
    {
        $pageTitle =  "Categories";

        return view(
            'admin.categories',
            ['pageTitle' =>  $pageTitle]
        );
    }



    public function payments()
    {
        $pageTitle =  "Payments";

        return view(
            'admin.payments',
            ['pageTitle' =>  $pageTitle]
        );
    }



    public function orders()
    {
        $pageTitle =  "Orders";

        return view(
            'admin.orders',
            ['pageTitle' =>  $pageTitle]
        );
    }

}

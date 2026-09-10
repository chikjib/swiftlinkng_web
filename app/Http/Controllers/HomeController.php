<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Slides;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Traits\IntegrationsTrait;
use App\Models\Order;
use App\Traits\MyCrypto;
use Storage;


class HomeController extends Controller
{
    use IntegrationsTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware(['auth', 'verified']);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $slides =  Slides::where('status', '1')->get();
        $settings =  Setting::where('key', 'ABOUT')->first();

        return view('home.index', compact('slides', 'settings'));
    }

    public function about()
    {
        $settings =  Setting::where('key', 'ABOUT')->first();
        return view('home.about', compact('settings'));
    }

    public function contact()
    {
        $settings =  Setting::where('key', 'CONTACT')->first();
        return view('home.contact', compact('settings'));
    }

    public function terms()
    {
        $settings =  Setting::where('key', 'TERMS')->first();
        return view('home.terms', compact('settings'));
    }

    public function privacy()
    {
        $settings =  Setting::where('key', 'PRIVACY')->first();
        return view('home.privacy', compact('settings'));
    }


    public function faq()
    {
        $settings =  Setting::where('key', 'FAQ')->first();
        return view('home.faq', compact('settings'));
    }

    public function pricing()
    {
        $category =  Subcategory::whereIn('category_id', [1, 2, 3, 4])->get();
        return view('home.pricing', compact('category'));
    }

    public function report()
    {
        $category =  Subcategory::whereIn('category_id', [1, 2, 3, 4])->get();
        return view('home.pricing', compact('category'));
    }
    public function run_cron()
    {
        Artisan::call('schedule:run');

        return view('home.cron');
    }
    
    public function airtime_nigeria_requery(Request $request)
    {
        $ref = $request->ref;
        if(!is_null($ref)){
          $requery_response = $this->reQueryAirtimeNigeria($ref);
          if($requery_response['status'] == 'success'){
             Order::where('ref',$ref)->update(['status' => 1]);
          }
             // dd("error1");
          
        }
           // dd("error");
        
        
        //else{
          //  Order::where('ref',$ref)->update(['status' => 4]) likely reverse
       // }
        
        return view('home.requery', ["requery_response" => $requery_response]);
        
        
    }
    
   
        
}

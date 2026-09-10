<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

class AirtelCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'airtel:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Keep login to airtel server alive';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        $cookie = Setting::where('key','AIRTEL_COOKIE')->first();
        $cookiefile = $cookie->value;
        //\Log::info($cookiefile);
        $cookiefile2 = $cookiefile;
    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://selfcare.airtel.com.ng/EducationSuite/Contract/Balance',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "cookie: edusuite=$cookiefile",
            ),
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.2309.372 Safari/537.36',
            CURLOPT_COOKIEFILE => $cookiefile,
            CURLOPT_COOKIEJAR => $cookiefile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_REFERER => 'https://selfcare.airtel.com.ng/educationsuite/account/login/',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            
            
        ));

        $response = curl_exec($curl);
        $q=curl_getinfo($curl, CURLINFO_COOKIELIST);
        \Log::info("AIRTEL CRON");
        \Log::info($q);
    $ct = count($q);
    \Log::info($ct);
    if($ct > 1){
       $txt=$q[1];
            if (strpos($txt, 'edusuite') !== false) {
             \Log::info("It entered here");
             $qi=explode("edusuite",$txt);
             $st=trim($qi[1]);
             $fp = Setting::where('key','AIRTEL_COOKIE')->update(['value' => $st]);
             \Log::info($fp);
         }
   }
   
 $mp = trim(get_string_between($response, 'Wallet Balance <span class="font-weight-bold text-gray-800">', "GB"));
        \Log::info($mp);   
    
//Check for errors!

if (curl_errno($curl)) {
    // this would be your first hint that something went wrong
    die('Couldn\'t send request: ' . curl_error($curl));
} else {
    // check the HTTP status code of the request
    $resultStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    \Log::info("My Cookie 1");
    \Log::info($resultStatus);
    
    if ($resultStatus == 200) {
      $q=curl_getinfo($curl, CURLINFO_COOKIELIST);
     //  echo $q[1];
    //  if($ct > 1){
    //     $out=explode("i1I", $q[1]);
    //     $cooker= trim($out[1]);
    //  }
     if($ct > 1){
        $out=explode("i1I", $q[1]);
        \Log::info("for out");
        \Log::info($out);
        $cooker= trim($out[0]);
     }
        // everything went better than expected
    } else {
        // the request did not complete as expected. common errors are 4xx
        // (not found, bad request, etc.) and 5xx (usually concerning
        // errors/exceptions in the remote script execution)

      die('Request failed: HTTP status code: ' . $resultStatus);
    }
}

        return Command::SUCCESS;
    }
}

<?php

namespace App\Providers;

use AfricasTalking\SDK\AfricasTalking;
use App\AppConstants;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** @var FilesystemAdapter $disk */

use Illuminate\Filesystem\FilesystemManager;

class UploadImageClass
{


    public function uploadFileS3(Request $request)
    {

        $request->validate([
            'cat_image' => 'required|mimes:jpg,jpeg,png,csv,txt,xlx,xls,pdf|max:2048'
        ]);


        if ($request->file()) {
            $image = $request->file('cat_image');
            $file_name = uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('category', $file_name, 's3');
            $s3filePath = Storage::disk('s3')->url($path);
            return $s3filePath;
        }
    }

    public function uploadFile(Request $request)
    {

        $request->validate([
            'cat_image' => 'required|mimes:jpg,jpeg,png,csv,txt,xlx,xls,pdf|max:2048'
        ]);


        if ($request->file()) {
            $image = $request->file('cat_image');
            $file_name = uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('category', $file_name, 'public');
            $s3filePath = Storage::disk('public')->url($path);
            return $file_name;
        }
    }
    public function upload(Request $request)
    {

        $request->validate([
            'user_image' => 'required|mimes:jpg,jpeg,png,csv,txt,xlx,xls,pdf|max:2048'
        ]);


        if ($request->file()) {


            $image = $request->file('user_image');

            # get s3 object make sure your key matches with
            # config/filesystem.php file configuration
            // $s3 = Storage::disk('s3');

            # rename file name to random name
            $file_name = uniqid() . '.' . $image->getClientOriginalExtension();

            # define s3 target directory to upload file to
            // $s3filePath = '/assets/' . $file_name;

            # finally upload your file to s3 bucket
            //$ //s3->put($s3filePath, file_get_contents($image), 'public');

            $path = $image->storeAs('assets', $file_name, 's3');



            $s3filePath = Storage::disk('s3')->url($path);

            // $imageName = time() . '.' . $request->image->extension();

            // $path = Storage::disk('s3')->put('images', $request->image);
            // $path = Storage::disk('s3')->url($path);


            // $extension = $request->file('user_image')->getClientOriginalExtension();
            // $timestampName = microtime(true) . '.' . $extension;

            // // $file_name = time() . '_' . $request->file('user_image')->getClientOriginalName();
            // $file_path = $request->file('user_image')->storeAs('uploads', trim($timestampName), 'public');

            // //$name = time() . '_' . $request->file('user_image')->getClientOriginalName();
            // $path = '/storage/' . $file_path;

            return $s3filePath;
        }
    }




    public function sendPush($deviceToken, $notification, $data, $priority = 'high')
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        $serverKey = AppConstants::FIREBASE_SERVER_KEY;

        // $arrayInfo = array(
        //     'product' => $product, 'orderId' => $orderid,
        //     'name' => $name, 'userlocation' => $userlocation,
        //     'location' => $location,
        //     'pickupdate' => $pickupDate,
        //     'km' => $km, 'phone' => $phone,
        //     'userImage' => $userImage
        // );
        $arrayToSend = array('to' => $deviceToken, 'notification' => $notification, 'data' => $data, 'priority' => $priority);

        $json = json_encode($arrayToSend);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: key=' . $serverKey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //Send the request
        $result = curl_exec($ch);
        //Close request
        // if ($response === FALSE) {
        //     die('FCM Send Error: ' . curl_error($ch));
        // }
        curl_close($ch);
        return $result;
    }



    public function sendSMS($phone, $message)
    {
        $AT =  new AfricasTalking(AppConstants::AFST_USERNAME, AppConstants::AFRICAISTALKING_APIKEY_LIVE);
        $sms      = $AT->sms();
        // Use the service
        $result   = $sms->send([
            'to'      => $this->formatPhone($phone),
            'message' => $message,
            'from' => "Winich",
        ]);
        return $result;
    }

    public function formatPhone($phone)
    {
        $phone2 = substr($phone, 1, 11);
        return "234" . $phone2;
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\BucketResource;
use Illuminate\Http\Request;
use App\Models\Bucket;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\API\BaseController as BaseController;


class BucketController extends BaseController
{

    public function index(Request $request)
    {
        if ($request->has('category_id')) {

                $data = Bucket::where('category_id', $request->category_id)->where('status', 1)->orderBy('created_at','asc')->get();

        } else if ($request->has('search')) {

            $data = Bucket::where('title', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%')
                ->orWhere('description2', 'like', '%' . $request->search . '%')
                ->orderBy('category_id', 'asc')->orderBy('created_at', 'desc')->paginate(10);
        } else if ($request->has('all')) {
            $data = Bucket::orderBy('category_id', 'asc')->orderBy('created_at', 'desc')->get();

        } else {
            $data = Bucket::orderBy('category_id', 'asc')->orderBy('created_at', 'desc')->paginate(10);
        }


        $products = BucketResource::collection($data);

        return $products;
    }

    // public function index(Request $request)
    // {
    //     $data = Bucket::where('title', 'like', '%' . $request->search . '%')
    //     ->orderBy('id', 'asc')->orderBy('created_at', 'desc')->paginate(10);

    //     $bucket = BucketResource::collection($data);

    //     return $bucket;

    // }


    public function load_admin_bucket(Request $request)
    {
        $data =  Bucket::all();
        $buckets = BucketResource::collection($data);

        return $buckets;
    }
    //
    public function load_bucket(Request $request)
    {

        $data =  Bucket::where('status', 1)->get();
        $buckets = BucketResource::collection($data);

        return $buckets;
    }
    
    public function load_bucket_balance(Request $request)
    {

        $data =  Bucket::all();
        $buckets = BucketResource::collection($data);

        return $buckets;
    }
    
    public function get_all_bucket_balances(Request $request)
    {
        $result = DB::table('users')
        ->selectRaw('
            SUM(users.mtn_sme_wallet) as sumSME,
            SUM(users.airtel_eds_wallet) as sumAirtel,
            SUM(users.glo_cg_wallet) as sumGlo,
            SUM(users.nmobile_cg_wallet) as sumNMobile,
            SUM(users.mtn_smart_wallet) as sumSmart,
            SUM(users.airtel_awoof_wallet) as sumAirtelAwoof,
            SUM(users.glo_awoof_wallet) as sumGloAwoof')
        ->get();


        \Log::info($result);



        return $this->sendResponse($result, 'User login successfully.');
    }

    public function show($id)
    {
        $bucket = Bucket::find($id);

        if (is_null($bucket)) {
            return $this->sendError('Bucket not found.');
        }

        return $this->sendResponse(new BucketResource($bucket), 'Bucket retrieved successfully.');
    }
    
    public function switchOffBucketPurchase(Request $request, $id){
        $bucket = Bucket::find($id);
        if($bucket->is_purchase == 1){
            $bucket->is_purchase = 0;
            $switch = "off";
            $bucket->save();
        }else{
            $bucket->is_purchase = 1;
            $switch = "on";
            $bucket->save();
        }
        return $this->sendResponse("Bucket switched ".$switch." successfully", "Bucket switched ".$switch." successfully");
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        \Log::info($input);

        $validator = Validator::make($input, [
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        // $subcategory->title = $input['title'];
        // $subcategory->category_id = $input['category_id'];
        // $subcategory->save();
        $admin_user = User::find($this->user->id);
        if($admin_user->role == 1){
            $input = $request->all();
            $bucket = Bucket::findOrFail($id);
            $bucket->fill($input)->save();
            return $this->sendResponse(new BucketResource($bucket), 'Bucket updated successfully.');
        }else{
            return $this->sendError("Processing failed");
        }
    }


    public function destroy($id)
    {
        $bucket =  Bucket::findOrFail($id);
        $bucket->delete();

        return $this->sendResponse([], 'Bucket deleted successfully.');
    }

}

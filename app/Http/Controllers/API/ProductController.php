<?php

namespace App\Http\Controllers\API;

use App\AppConstants;
use Illuminate\Http\Request;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductResource;
use App\Models\AgentStock;
use App\Models\Category;
use App\Models\Commissions;
use App\Models\Farmer;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Models\User;
use App\Providers\UploadImageClass;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = Product::orderBy('created_at', 'desc')->paginate(20);

        $search = $request->input('search');

        $subcategory_id = $request->input('subcategory_id');


        if ($search) {
            $data =  Product::where('tags', 'like', '%' . $search . '%')
                ->orWhere('price', 'like', '%' . $search . '%')
                ->paginate(10);
        }

        if ($request->input('chart')) {
            $data = Product::all();
        }

        if ($subcategory_id) {
            $data =  Product::where('subcategory_id', $subcategory_id)->get();
        }



        $products = ProductResource::collection($data);



        return $products;
        //return $this->sendResponse(ProductResource::collection($products), 'Products retrieved successfully.');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'subcategory_id' => 'required',
            'category_id' => 'required',
            'amount' => 'required',
            'ussdcode' => 'required',
            'item' => 'required',
            'channel' => 'required',
            'userlevel' => 'required',
            'status' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }


        try {

            DB::beginTransaction();
            $product = Product::create($input);
            DB::commit();
            return $this->sendResponse(new ProductResource($product), 'Product created successfully.');
            //code...
        } catch (\Exception $ex) {
            //throw $th;
            return $this->sendError($ex->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::find($id);

        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }

        return $this->sendResponse(new ProductResource($product), 'Product retrieved successfully.');
    }



    public function showByUser($user_id)
    {
        $data = Product::where('user_id', $user_id)->get();

        if (is_null($data)) {
            return $this->sendError('No product for user was found');
        }

        $products = ProductResource::collection($data);

        return $products;

        //  return $this->sendResponse(new ProductResource($dispatch), 'Product retrieved successfully.');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'subcategory_id' => 'required',
            'category_id' => 'required',
            'amount' => 'required',
            'ussdcode' => 'required',
            'item' => 'required',
            'channel' => 'required',
            'userlevel' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try {
            DB::beginTransaction();
            //code...
            $product = Product::find($id);

            $product->update($request->all());

            DB::commit();


            return $this->sendResponse(new ProductResource($product), 'Product updated successfully.');
        } catch (\Exception $ex) {
            //throw $th;

            return $this->sendError($ex->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        try {
            DB::beginTransaction();
            //recalculate farmers score
            $product = Product::find($id);

            $product->delete();

            DB::commit();
            return $this->sendResponse([], 'Product deleted successfully.');
            //code...
        } catch (\Exception $ex) {
            //throw $th;
            return $this->sendError($ex->getMessage());
        }
    }
}

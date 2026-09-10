<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Commissions;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CommissionResource;

class CommissionController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $user_id = $request->input('user_id');


        //$search = $request->input('search');


        // if ($search) {
        //     $data =  Commissions::where('firstname', 'like', '%' . $search . '%')
        //         ->orWhere('lastname', 'like', '%' . $search . '%')
        //         ->orWhere('phone', 'like', '%' . $search . '%')
        //         //   ->orWhere('state', 'like', '%' . $search . '%')
        //         ->orWhere('email', 'like', '%' . $search . '%')
        //         ->paginate(2);
        // }

        if ($user_id) {
            $data =  Commissions::where('user_id', $user_id)->paginate(10);

            // if ($request->input('chart')) {
            //     $data = Category::get();
            // }
        } else {
            $data = Commissions::orderBy('created_at', 'desc')->paginate(10);
        }

        $products = CommissionResource::collection($data);

        return $products;


        // return $this->sendResponse(CommissionResource::collection($products), 'Commissions retrieved successfully.');
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
            'user_id' => 'required',
            'product_id' => 'required',
            'total' => 'required',
            'commission' => 'required',
            'com_percent' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $category = Commissions::create($input);

        return $this->sendResponse(new CommissionResource($category), 'Commissions created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Commissions::find($id);

        if (is_null($category)) {
            return $this->sendError('Commission not found.');
        }

        return $this->sendResponse(new CommissionResource($category), 'Commission retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Commissions $category)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'status' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $category->status = $input['status'];
        $category->save();

        return $this->sendResponse(new CommissionResource($category), 'Commission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $commission = Commissions::find($id);
        $commission->delete();

        return $this->sendResponse([], 'Commission deleted successfully.');
    }
}

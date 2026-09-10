<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\WithdrawalResource;
use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Withdrawal::orderBy('created_at', 'desc')->paginate(10);

        $products = WithdrawalResource::collection($data);
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
            'amount' => 'required|digits_between:1,99999999999999',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $user_id =  $request->input('user_id');

        $amount = $request->input('amount');


        $getuser = User::find($user_id);
        if ($getuser->amount > $amount) {

            return $this->sendError('Error.', "Amount is higher than wallet balance");
        } else if ($getuser->account_number == null) {
            return $this->sendError('Error.', "Bank Details not found. Please add a bank account");
        } else if ($amount <= 0) {

            return $this->sendError('Error.', "Invalid amount");
        } else {
            //check if amount is available

            $getuser->amount =  $getuser->amount - $amount;
            $getuser->save();
        }

        $category = Withdrawal::create($input);

        return $this->sendResponse(new WithdrawalResource($category), 'Withdrawal successful.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Withdrawal::find($id);

        if (is_null($category)) {
            return $this->sendError('Withdrawal not found.');
        }

        return $this->sendResponse(new WithdrawalResource($category), 'Withdrawal retrieved successfully.');
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
            'status' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $category = Withdrawal::find($id);
        $category->status = $input['status'];
        $category->save();
        $userid =  $category->user_id;
        $amount =  $category->amount;
        $getuser = User::find($userid);

        $getuser->withdrawn = $getuser->withdrawn + $amount;
        $getuser->save();

        return $this->sendResponse(new WithdrawalResource($category), 'Withdrawal Status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payment = Withdrawal::find($id);
        $payment->delete();
        return $this->sendResponse([], 'Withdrawal deleted successfully.');
    }
}

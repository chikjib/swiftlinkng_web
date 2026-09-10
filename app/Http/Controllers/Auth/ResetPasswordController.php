<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;

class ResetPasswordController extends BaseController
{


    public function reset(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'email' => ['email', 'required'],
            'code' => ['string', 'required']
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return $this->sendError($msg, $msg);
        }

        $validRecord = DB::table('password_resets')->where('email', $request->email)->where('token', $request->code)->first();

        if ($validRecord == null) {
            return $this->sendError("email or token is incorrect", "email or token is incorrect");
        }

        $user = User::where('email', $request->email)->first();
        $hash = password_hash($request->password, PASSWORD_DEFAULT);

        $user->password = $hash;
        $user->save();

        return $this->sendResponse($user, "Password reset successful.");
    }
}

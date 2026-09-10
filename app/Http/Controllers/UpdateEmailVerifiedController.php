<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\Request;

class UpdateEmailVerifiedController extends BaseController
{
    public function __invoke(Request $request, User $user)
    {
        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }

        RewardService::reconcileUser($user->fresh());

        return redirect('/login?result=Email verification successful');
    }
}

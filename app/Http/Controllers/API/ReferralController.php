<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\UserResource;
use App\Services\ReferralDashboardService;
use App\Services\ReferralProgramConfig;
use Illuminate\Http\Request;

class ReferralController extends BaseController
{
    public function show(Request $request)
    {
        $user = $request->user();
        $data = array_merge(
            (new UserResource($user))->resolve($request),
            ReferralDashboardService::forUser($user),
            [
                'referral_code' => (string) $user->id,
                'referral_link' => url('/register?ref=' . $user->id),
                'program_settings' => ReferralProgramConfig::all(),
            ]
        );

        return $this->sendResponse($data, 'Referral dashboard retrieved successfully.');
    }

    public function referrals(Request $request)
    {
        $request->validate([
            'verification' => ['nullable', 'in:verified,unverified'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        return $this->sendResponse(
            ReferralDashboardService::referralsForUser(
                $request->user(),
                $request->input('verification'),
                (int) $request->input('per_page', 20)
            ),
            'Your referrals were retrieved successfully.'
        );
    }
}

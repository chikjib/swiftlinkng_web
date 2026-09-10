<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TransactionPinController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'has_transaction_pin' => !empty($request->user()->transaction_pin),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        $user = $request->user();
        if (!empty($user->transaction_pin)) {
            return response()->json([
                'status' => false,
                'message' => 'A transaction PIN already exists. Use change PIN instead.',
                'code' => 'TRANSACTION_PIN_ALREADY_SET',
            ], 409);
        }

        $user->transaction_pin = Hash::make($validated['pin']);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Transaction PIN created successfully.',
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_pin' => ['required', 'digits:4'],
            'pin' => ['required', 'digits:4', 'confirmed', 'different:current_pin'],
        ]);

        $user = $request->user();
        if (empty($user->transaction_pin) ||
            !Hash::check($validated['current_pin'], $user->transaction_pin)) {
            return response()->json([
                'status' => false,
                'message' => 'The current transaction PIN is incorrect.',
                'code' => 'INVALID_TRANSACTION_PIN',
            ], 422);
        }

        $user->transaction_pin = Hash::make($validated['pin']);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Transaction PIN changed successfully.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        $user = $request->user();
        if (empty($user->transaction_pin)) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have a transaction PIN yet. Create one when making your next purchase.',
                'code' => 'TRANSACTION_PIN_NOT_SET',
            ], 422);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'The account password you entered is incorrect.',
                'code' => 'INVALID_ACCOUNT_PASSWORD',
            ], 422);
        }

        $user->transaction_pin = Hash::make($validated['pin']);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Transaction PIN reset successfully.',
        ]);
    }
}

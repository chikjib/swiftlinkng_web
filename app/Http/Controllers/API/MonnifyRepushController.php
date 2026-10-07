<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MonnifyFunding;
use App\Services\MonnifyTransactions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MonnifyRepushController extends Controller
{
    public function __invoke(Request $request, MonnifyTransactions $monnify, MonnifyFunding $funding)
    {
        $input = $request->validate(['transaction_reference' => ['required', 'string', 'max:255']]);
        try {
            $order = $funding->credit($monnify->verify(trim($input['transaction_reference'])));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Repush could not be completed. Please retry; recorded transactions cannot be credited twice.'], 502);
        }
        return response()->json([
            'message' => 'Monnify transaction repushed successfully.',
            'data' => ['reference' => $order->ref, 'amount' => $order->amount, 'email' => $order->user->email],
        ]);
    }
}

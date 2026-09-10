<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use App\Notifications\TransactionNotification;
use Illuminate\Support\Facades\Notification;

class NotificationController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('product');
    }

    public function sendTransactionNotification()
    {
        $userSchema = User::first();

        $offerData = [
            'name' => 'BOGO',
            'body' => 'You received an offer.',
            'thanks' => 'Thank you',
            'transactionText' => 'Check out the offer',
            'transactionUrl' => url('/'),
            'transaction_id' => 007
        ];

        Notification::send($userSchema, new TransactionNotification($offerData));

        dd('Task completed!');
    }
}

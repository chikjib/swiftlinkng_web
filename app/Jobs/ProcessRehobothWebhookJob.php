<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Subcategory;
use App\Models\User;
use App\Traits\IntegrationsTrait;
use App\Traits\ReferenceTrait;
use App\Traits\WalletTrait;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;


class ProcessRehobothWebhookJob extends SpatieProcessWebhookJob
{
    use IntegrationsTrait;
    use WalletTrait;
    use ReferenceTrait;

    public function handle()
{
    $payload = $this->webhookCall;

    \Log::info("Payload from rehoboth");
    \Log::info($payload);

    $response = $payload['payload'];

    \Log::info("Response Payload from rehoboth payload");
    \Log::info($response);

    if ($response['Amount'] >= 5000) {
        $amount = $response['Amount'] - 30;
    } else {
        $amount = $response['Amount'];
    }

    $findOrder = Order::where('rehoboth_reference', $response['TransactionReference'])->first();

    if (is_null($findOrder)) {
        $findUser = User::where('rehoboth_account', $response['AccountNumber'])->first();

        if (!is_null($findUser)) {

            $blockedEmails = [
                'salihusanusi853@gmail.com',
                'polmimusa9@gmail.com',
                'adamshauwa744@gmail.com',
                'davetech261@gmail.com',
                'preciousadeola42@gmail.com',
                'mojeedbolaji340@gmail.com',
                'musamuhammad9215@gmail.com',
                'rayyanumuazu5@gmail.com',
                'jonathanazubike90@gmail.com',
            ];

            $subcategory = Subcategory::where('title', 'Rehoboth')->first();

            $ref = $response['TransactionReference'];

            $order = new Order();
            $order->ref = $ref;
            $order->rehoboth_reference = $ref;
            $order->user_id = $findUser->id;
            $order->subcategory_id = $subcategory->id;
            $order->plan = $subcategory->title;
            $order->amount = $amount;
            $order->quantity = 1;
            $order->prev_bal = $findUser->wallet;
            $order->subtotal = $amount;
            $order->total = $amount;

            // if (in_array(strtolower($findUser->email), array_map('strtolower', $blockedEmails))) {
            //     // Block crediting
            //     $order->bal = $findUser->wallet; // wallet unchanged
            //     $order->description = $subcategory->title . " Auto Credited";
            //     $order->status = 2; // blocked status
            //     $order->save();
            // } else {
            //     // Normal crediting
            //     if ($this->isCredited($amount, $findUser->id)) {
            //         $order->bal = $findUser->wallet + $amount;
            //         $order->description = $subcategory->title . " Auto Credited";
            //         $order->status = 1;
            //         $order->save();
            //     }
            // }
        }
    }
}

}

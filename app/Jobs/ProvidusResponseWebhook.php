<?php

namespace App\Jobs;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookConfig;
use Symfony\Component\HttpFoundation\Response;
use Spatie\WebhookClient\WebhookResponse\RespondsToWebhook as RespondsToWebhook;

class ProvidusResponseWebhook implements RespondsToWebhook
{
    public function respondToValidWebhook(Request $request, WebhookConfig $config): Response
    {


        $data = array(
            "requestSuccessful" => true,
            "sessionId" => $request->sessionId,
            "responseMessage" => "success",
            "responseCode" => "00",
        );
        return response()->json($data);
    }
}

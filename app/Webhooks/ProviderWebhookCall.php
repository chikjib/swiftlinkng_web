<?php
namespace App\Webhooks;
use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
class ProviderWebhookCall extends WebhookCall {
    protected $table='webhook_calls';
    protected static function buildPayloadFromRequest(Request $request): array {
        $payload=$request->json()->all();
        if ($request->is('esim-access-webhook')) {
            return array_intersect_key($payload,array_flip(['notifyId','notifyType','eventGenerateTime'])) + [
                'content'=>array_intersect_key($payload['content'] ?? [],array_flip(['orderNo','transactionId','iccid','esimTranNo'])),
            ];
        }
        $ref=data_get($payload,'data.merchant_reference') ?? data_get($payload,'merchant_reference')
            ?? data_get($payload,'transaction.reference') ?? data_get($payload,'data.reference');
        abort_unless(is_string($ref) && preg_match('/^SLB[a-f0-9]{28}$/D',$ref),422,'Missing betting payment reference.');
        return ['reference'=>$ref];
    }
}

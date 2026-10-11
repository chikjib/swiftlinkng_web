<?php
namespace App\Webhooks;
use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;
class EsimAccessProfile implements WebhookProfile {
    public function shouldProcess(Request $request): bool {
        $event=$request->validate([
            'notifyId'=>'required|string|max:128','notifyType'=>'required|string|max:32','content'=>'required|array',
            'content.orderNo'=>'nullable|string|max:128','content.transactionId'=>'nullable|string|max:128',
            'content.iccid'=>'nullable|string|max:32','content.esimTranNo'=>'nullable|string|max:128',
        ]);
        if (!in_array($event['notifyType'],['ORDER_STATUS','ESIM_STATUS','SMDP_EVENT','DATA_USAGE','VALIDITY_USAGE'],true)) return false;
        abort_unless(!empty($event['content']['transactionId']) || !empty($event['content']['orderNo']) || !empty($event['content']['iccid']),422,'Missing event identity.');
        return true; // CHECK_HEALTH and unknown notifications receive HTTP 200 without a job.
    }
}

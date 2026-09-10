<?php

namespace App\Http\Resources;

use App\Models\Commissions;
use App\Models\Withdrawal;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'email_verified' => ! is_null($this->email_verified_at),
            'email_verified_at' => $this->email_verified_at
                ? $this->email_verified_at->toIso8601String()
                : null,
            'phone' => $this->phone,

            'role' => $this->role,
            'wallet' => $this->wallet,

            'userlevel' => $this->userlevel,
            'hear_about_us' => $this->hear_about_us,

            'commission' => $this->commission,
            'withdrawal' => $this->withdrawal,
            
            'wema_reserved_acct' => $this->wema_reserved_acct,
            'moniepoint_reserved_acct'  => $this->moniepoint_reserved_acct,
            'gtbank_reserved_acct'  => $this->gtbank_reserved_acct,
            'providus_reserved_acct'  => $this->providus_reserved_acct,
            'rehoboth_reserved_acct'  => $this->rehoboth_reserved_acct,
            'palmpay_reserved_acct'  => $this->palmpay_reserved_acct,
            'opay_reserved_acct' => $this->opay_wallet_number ? [
                'bankCode' => 'OPAY',
                'bankName' => 'OPay',
                'accountNumber' => $this->opay_wallet_number,
                'accountName' => $this->opay_wallet_name,
                'refId' => $this->opay_wallet_ref_id,
                'accountType' => $this->opay_wallet_account_type,
                'status' => $this->opay_wallet_status,
            ] : null,
            
            'mtn_sme_wallet' => $this->mtn_sme_wallet,
            'mtn_sme_wallet_status' => $this->mtn_sme_wallet_status,
            'airtel_eds_wallet' => $this->airtel_eds_wallet,
            'airtel_eds_wallet_status' => $this->airtel_eds_wallet_status,
            'glo_cg_wallet' => $this->glo_cg_wallet,
            'glo_cg_wallet_status' => $this->glo_cg_wallet_status,
            'nmobile_cg_wallet' => $this->nmobile_cg_wallet,
            'nmobile_cg_wallet_status' => $this->nmobile_cg_wallet_status,
            'mtn_smart_wallet' => $this->mtn_smart_wallet,
            'mtn_smart_wallet_status' => $this->mtn_smart_wallet_status,
            'airtel_awoof_wallet' => $this->airtel_awoof_wallet,
            'airtel_awoof_wallet_status' => $this->airtel_awoof_wallet_status,
            'glo_awoof_wallet' => $this->glo_awoof_wallet,
            'glo_awoof_wallet_status' => $this->glo_awoof_wallet_status,

            
            'reserved_acct' => $this->reserved_acct,
            'userToken' => $this->userToken,
            'deviceToken' => $this->deviceToken,
            
            'bvn' => $this->bvn,
            'nin' => $this->nin,
            'webhook_url' => $this->webhook_url,

            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,

            'referral_id' => $this->referral_id,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'status' => $this->status,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}

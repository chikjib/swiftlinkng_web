<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MtnLoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $mtn_balance = \DB::table("mtn_balance")->where('mtn_login_id', $this->id)->first();

        return [
            'id' => $this->id,
            'phone_number' => $this->phone_number,
            'mtn_access_token' => $this->mtn_access_token,
            'mtn_refresh_token' => $this->mtn_refresh_token,
            'balance' => $mtn_balance->balance,
            'status' => $this->status,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}

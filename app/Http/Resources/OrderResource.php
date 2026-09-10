<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'ref' => $this->ref,
            'user' => $this->user,
            'category' => $this->subcategory->category,
            'subcategory' => $this->subcategory,
            'plan' => $this->plan,
            'amount' => $this->amount,
            'quantity' => $this->quantity,
            'total' => $this->total,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'phone' => $this->phone,

            'iuc' => $this->iuc,
            'meter' => $this->meter,
            'bal' => $this->bal,
            'prev_bal' => $this->prev_bal,
            'channel' => $this->channel,

            'description' => $this->description,
            'response' => $this->response,

            'payment_method' => $this->payment_method,
            'created_at' => $this->created_at->format('d/m/Y H:i:s'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i:s'),
        ];
    }
}

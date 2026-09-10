<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            //  'category' => $this->subCategory->[$this->subCategory->category],
            'user' => $this->user,
            'order_ref' => $this->order_ref,
            // 'cat' => json_encode(SubCategory::where('id', $this->subcategory_id)->get()),
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'response' => $this->response,

            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Traits\ReferenceTrait;
use Illuminate\Support\Facades\Auth;


class BucketResource extends JsonResource
{
    use ReferenceTrait;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $this->user = Auth::user();
        $bucket_wallet_name = $this->getBucketTitle($this->id);
        $bucket_wallet_bal = $this->user->$bucket_wallet_name;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'bucket_wallet' => $bucket_wallet_name,
            'bucket_balance' => $bucket_wallet_bal,
            'category' => $this->category,
            'description' => $this->description,
            'description2' => $this->description2,
            'products' => $this->products,
            'pins' => $this->pins,
            'status' => $this->status,
            'is_purchase' => $this->is_purchase,
            'price_per_gb' => $this->price_per_gb,
            'telegram' => $this->telegram,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}

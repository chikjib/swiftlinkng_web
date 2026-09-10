<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JambPinResource extends JsonResource
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
            'pin_no' => $this->pin_no,
            'variation_code' => $this->variation_code,
            'used' => $this->used,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}

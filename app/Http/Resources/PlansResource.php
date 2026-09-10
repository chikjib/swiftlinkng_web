<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlansResource extends JsonResource
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
            'subcategory_id' => $this->id,
            'title' => $this->new_title,
            'serviceID' => $this->when($this->category_id == 4, $this->service_id),
            'category' => $this->category->title,

            'plan' =>  $this->when(!is_null($this->plan), $this->plan),
            'status' => $this->status,
            // 'created_at' => $this->created_at->format('d/m/Y'),
            // 'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}

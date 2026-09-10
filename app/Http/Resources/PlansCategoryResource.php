<?php

namespace App\Http\Resources;

use App\Traits\ReferenceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class PlansCategoryResource extends JsonResource
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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subcat_image' => $this->subcat_image,
            'description' => $this->description,
            'description2' => $this->description2,
            'products' =>  $this->when(!is_null($this->plan),  $this->plan),
            'amount' => $this->when($this->category_id > 1, $this->amount_discount),

            'status' => $this->status,
        ];
    }
}

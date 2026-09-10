<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'key' => $this->key,
            'value' => $this->value,

            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
    public function with($request)
    {
        return  [
            'meta' => [
                'rave_publickey' => env('MIX_RAVE_PUBLICKEY'),
                'bonus' => $this->bonus,
                'fundmin' => $this->fundmin,
                'fundmax' => $this->fundmax,

            ]

        ];
    }
}

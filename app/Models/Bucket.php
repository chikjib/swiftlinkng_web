<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bucket extends Model
{
    use HasFactory;

    protected $table = 'bucket';

    protected $fillable = [
        'title', 'category_id', 'pins', 'telegram', 'products', 'price_per_gb', 'status', 'description', 'description2'
    ];


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        return $this->hasMany(Product::class);
    }

    public function getNewTitleAttribute()
    {
        if ($this->category_id == 4) {
            $rec = explode('-', $this->title);
            $recIndex = $rec[1];
            $data = str_replace(' ', '-', trim($recIndex));
            $title = trim($data);

            return $title;
        }
        return $this->title;
    }

    public function getServiceIDAttribute()
    {
        return $this->title;
    }

    public function getPlanAttribute()
    {
        $result = null;

        if ($this->category_id == 1) {
            $productCollection = collect(json_decode($this->products));
            $result =
                $productCollection
                ->map(
                    function ($item, $key) {
                        // $item = $item->first(); //as item is a collection of models
                        $new = [];
                        $new['plan'] = $item->plan;
                        return $new;
                    }
                );
        }


        return $result;
    }
}

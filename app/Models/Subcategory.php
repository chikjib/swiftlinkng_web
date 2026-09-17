<?php

namespace App\Models;

use App\Traits\ReferenceTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subcategory extends Model
{
    use HasFactory;

    protected $table = 'subcategory';
    use ReferenceTrait;


    protected $fillable = [
        'title', 'category_id',  'subcat_image',  'pins', 'telegram', 'products', 'status', 'description', 'description2'
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

        if (in_array((int) $this->category_id, [1, 13], true)) {
            $productCollection = collect($this->decodeProductPlans());
            $result =
                $productCollection
                ->filter(function ($item) {
                    return is_object($item) && isset($item->plan);
                })
                ->map(
                    function ($item, $key) {
                        // $item = $item->first(); //as item is a collection of models
                        $new = [];
                        $new['plan']                 = $item->plan;
                        $new['amount']          = $this->getUserLevel($item, Auth::user()->userlevel);
                        return $new;
                    }
                );
        }


        return $result;
    }

    private function decodeProductPlans(): array
    {
        $value = $this->products;

        for ($depth = 0; $depth < 3 && is_string($value); $depth++) {
            $value = json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
        }

        if (is_object($value)) {
            if (isset($value->plans)) {
                $value = $value->plans;
            } elseif (isset($value->products)) {
                $value = $value->products;
            } elseif (isset($value->data)) {
                $value = $value->data;
            } elseif (isset($value->plan)) {
                $value = [$value];
            } else {
                $value = array_values((array) $value);
            }
        }

        for ($depth = 0; $depth < 3 && is_string($value); $depth++) {
            $value = json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
        }

        return is_array($value) ? array_values($value) : [];
    }
    public function getAmountDiscountAttribute()
    {
        $result = null;

        if ($this->category_id > 1 && (int) $this->category_id !== 13) {
            $result = $this->getUserLevel(json_decode($this->products), Auth::user()->userlevel);
        }


        return $result;
    }
}

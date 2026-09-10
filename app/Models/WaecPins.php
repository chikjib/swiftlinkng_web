<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaecPins extends Model
{
    use HasFactory;
    protected $fillable = [
        "pin_no",
        "serial_no",
        "variation_code",
        "used",
    ];
}

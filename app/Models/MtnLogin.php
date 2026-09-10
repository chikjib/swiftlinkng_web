<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtnLogin extends Model
{
    use HasFactory;
    protected $fillable = [
        "phone_number",
        "mtn_access_token",
        "mtn_refresh_token",
        "expires_in",
        "status",
    ];
}

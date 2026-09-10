<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralProgress extends Model
{
    use HasFactory;

    protected $table = 'referral_progress';

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'qualifying_spend',
        'welcome_rewarded_at',
        'became_active_at',
        'active_rewarded_at',
    ];

    protected $casts = [
        'qualifying_spend' => 'decimal:2',
        'welcome_rewarded_at' => 'datetime',
        'became_active_at' => 'datetime',
        'active_rewarded_at' => 'datetime',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}

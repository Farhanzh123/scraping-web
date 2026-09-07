<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopeeSessionStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_state',
        'status_message',
        'chrome_connected',
        'affiliate_logged_in',
        'auto_check_enabled',
        'interval_days',
        'last_checked_at',
    ];

    protected $casts = [
        'chrome_connected' => 'boolean',
        'affiliate_logged_in' => 'boolean',
        'auto_check_enabled' => 'boolean',
        'interval_days' => 'integer',
        'last_checked_at' => 'datetime',
    ];
}
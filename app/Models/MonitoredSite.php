<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoredSite extends Model
{
    protected $fillable = [
        'user_id',
        'domain',
        'last_score',
        'previous_score',
        'last_grade',
        'last_scan_id',
        'last_checked_at',
        'notify_score_drop',
        'notify_cert_expiry',
    ];

    protected $casts = [
        'last_checked_at'   => 'datetime',
        'notify_score_drop' => 'boolean',
        'notify_cert_expiry' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastScan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'last_scan_id');
    }
}

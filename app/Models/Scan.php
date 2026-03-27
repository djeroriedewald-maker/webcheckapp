<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Scan extends Model
{
    protected $fillable = [
        'uid',
        'url',
        'host',
        'status',
        'score',
        'grade',
        'results',
        'ip_address',
        'completed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $scan) {
            if (empty($scan->uid)) {
                do {
                    $uid = Str::random(8);
                } while (static::where('uid', $uid)->exists());

                $scan->uid = $uid;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    protected $casts = [
        'results' => 'array',
        'completed_at' => 'datetime',
    ];

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'running']);
    }

    public function getGradeColorClass(): string
    {
        return match(true) {
            $this->score >= 90 => 'text-emerald-400',
            $this->score >= 75 => 'text-green-400',
            $this->score >= 60 => 'text-yellow-400',
            $this->score >= 40 => 'text-orange-400',
            default            => 'text-red-400',
        };
    }

    public function getGradeBgClass(): string
    {
        return match(true) {
            $this->score >= 90 => 'bg-emerald-500',
            $this->score >= 75 => 'bg-green-500',
            $this->score >= 60 => 'bg-yellow-500',
            $this->score >= 40 => 'bg-orange-500',
            default            => 'bg-red-500',
        };
    }
}

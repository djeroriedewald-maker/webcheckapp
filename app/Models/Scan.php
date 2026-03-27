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
        // Aligned with scoreToGrade(): A/A-/A+ ≥85, B-/B/B+ ≥70, C-/C/C+ ≥55, D-/D/D+ ≥40, F
        return match(true) {
            $this->score >= 85 => 'text-emerald-400',
            $this->score >= 70 => 'text-green-400',
            $this->score >= 55 => 'text-yellow-400',
            $this->score >= 40 => 'text-orange-400',
            default            => 'text-red-400',
        };
    }

    public function getGradeBgClass(): string
    {
        return match(true) {
            $this->score >= 85 => 'bg-emerald-500',
            $this->score >= 70 => 'bg-green-500',
            $this->score >= 55 => 'bg-yellow-500',
            $this->score >= 40 => 'bg-orange-500',
            default            => 'bg-red-500',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'minutes_late',
        'scheduled_start',
        'event_type',
        'actual_login_at',
        'actual_logout_at',
        'status',
        'approved_by',
        'approved_at',
        'description',
    ];

    protected $casts = [
        'actual_login_at' => 'datetime',
        'actual_logout_at' => 'datetime',
        'approved_at' => 'datetime',
        'amount' => 'integer',
        'minutes_late' => 'integer',
    ];

    public function getActualEventAtAttribute()
    {
        return $this->actual_logout_at ?? $this->actual_login_at;
    }

    public function getEventTypeLabelAttribute(): string
    {
        return $this->event_type === 'logout' ? 'Déconnexion' : 'Connexion';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

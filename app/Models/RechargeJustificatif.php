<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RechargeJustificatif extends Model
{
    protected $fillable = ['recharge_id', 'user_id', 'type', 'path'];

    public function recharge()
    {
        return $this->belongsTo(Recharge::class);
    }
}

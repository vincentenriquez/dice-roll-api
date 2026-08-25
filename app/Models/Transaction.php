<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'stake', 'total_win', 'net_win', 'balance_after'])]

class Transaction extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gameRound():HasOne {
        return $this->hasOne(GameRound::class);
    }
}

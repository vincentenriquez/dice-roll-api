<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'stake', 'total_win', 'net_win', 'balance_after'];

    public function user() :BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameRound(): HasOne
    {
        return $this->hasOne(GameRound::class);
    }
}

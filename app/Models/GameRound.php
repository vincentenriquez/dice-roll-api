<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRound extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'transaction_id', 'guess', 'result', 'is_win'];

    protected function casts(): array
    {
        return [
            'is_win' => 'boolean',
        ];
    }

    public function user() :BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction() :BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}

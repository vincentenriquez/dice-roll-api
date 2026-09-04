<?php

namespace App\Services;

use App\Models\User;

class BalTranService
{
    public function resetBalance(User $user): void
    {
        $user->update(['balance' => 100]);
    }
}

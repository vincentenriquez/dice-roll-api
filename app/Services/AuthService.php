<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(string $username, string $email, string $password): User
    {
        $user = User::create([
            'name' => $username,
            'email' => $email,
            'password' => $password, // auto-hashed by the 'hashed' cast in User model
            'balance' => 100,
        ]);

        return $user->refresh();
    }

    public function login(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        return $user;
    }

    public function logout(User $user): bool
    {
        $user->tokens()->delete();

        return true;
    }

    // public function resetBalance(User $user): void
    // {
    //     $user->update(['balance' => 100]);
    // }
}

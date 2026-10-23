<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class RegisterUserAction
{
    public function execute(
        string $name,
        string $email,
        string $password,
    ): User {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }
}

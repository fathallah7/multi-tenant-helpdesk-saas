<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class LoginUserAction
{
    public function execute(
        string $email,
        string $password,
    ): ?User {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (
            $user === null ||
            ! Hash::check($password, $user->password)
        ) {
            return null;
        }

        return $user;
    }
}

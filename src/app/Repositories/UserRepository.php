<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    // メールアドレスで1件取得（なければ404）
    public function findByEmail(string $email)
    {
        return User::where('email', $email)->firstOrFail();
    }
}

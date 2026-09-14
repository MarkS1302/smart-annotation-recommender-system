<?php

namespace App\Policies;

use App\Models\AiResponse;
use App\Models\User;

class AiResponsePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AiResponse $aiResponse): bool
    {
        return $user->is($aiResponse->user);
    }

}

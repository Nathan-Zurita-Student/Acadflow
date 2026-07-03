<?php

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class AccountDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly bool $subscriptionCanceled = false,
    ) {}
}

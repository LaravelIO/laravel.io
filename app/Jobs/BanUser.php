<?php

namespace App\Jobs;

use App\Models\User;
use Carbon\Carbon;

final class BanUser
{
    public function __construct(
        private User $user
    ) {
    }

    public function handle(): void
    {
        $this->user->banned_at = Carbon::now();
        $this->user->save();
    }
}

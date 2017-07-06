<?php

namespace App\Jobs;

use App\User;
use App\Http\Requests\UpdateProfileRequest;

class UpdateProfile
{
    /**
     * @var \App\User
     */
    private $user;

    /**
     * @var array
     */
    private $attributes;

    public function __construct(User $user, array $attributes = [])
    {
        $this->user = $user;
        $this->attributes = array_only($attributes, ['name', 'email', 'username', 'github_username']);
    }

    public static function fromRequest(User $user, UpdateProfileRequest $request): self
    {
        return new static($user, [
            'name' => $request->name(),
            'email' => $request->email(),
            'username' => strtolower($request->username()),
        ]);
    }

    public function handle()
    {
        $this->user->update($this->attributes);
    }
}

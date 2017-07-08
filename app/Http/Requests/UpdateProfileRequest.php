<?php

namespace App\Http\Requests;

use Auth;

class UpdateProfileRequest extends Request
{
    public function rules()
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.Auth::id(),
            'username' => 'required|max:255|unique:users,username,'.Auth::id(),
            'bio' => 'sometimes|max:144',
        ];
    }

    public function bio(): ?string
    {
        return $this->get('bio');
    }

    public function name(): string
    {
        return $this->get('name');
    }

    public function email(): string
    {
        return $this->get('email');
    }

    public function username(): string
    {
        return $this->get('username');
    }
}

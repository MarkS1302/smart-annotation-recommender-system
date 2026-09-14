<?php

namespace App\Http\Requests\Users;

use App\Concerns\ProfileValidationRules;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use ProfileValidationRules;


    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'role_ids' => ['array'],
            'role_ids.*' => [                'integer',                Rule::exists(Role::class, 'id'),],
        ];
    }
}

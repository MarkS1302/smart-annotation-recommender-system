<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiLoginController extends Controller
{
    public function __invoke(ApiLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $email = Str::lower((string) Arr::get($credentials, 'email'));
        $password = (string) Arr::get($credentials, 'password');
        $user = User::query()->where('email', $email)->first();

        if (blank($user) || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $plainTextToken = $user->createToken('annotation-api')->plainTextToken;

        return response()->json([
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'user' => Arr::only($user->toArray(), ['id', 'name', 'email']),
        ]);
    }
}

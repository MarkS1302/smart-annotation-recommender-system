<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAnnotationApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('ai.api_token');
        $providedToken = (string) $request->bearerToken();

        if ($configuredToken === '' || $providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Invalid annotation API token.'], 401);
        }

        $user = User::query()->find(config('ai.api_user_id'));

        if ($user === null) {
            return response()->json(['message' => 'Annotation API user is not configured.'], 503);
        }

        Auth::setUser($user);
        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }
}

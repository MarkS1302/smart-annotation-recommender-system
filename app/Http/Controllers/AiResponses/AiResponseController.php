<?php

namespace App\Http\Controllers\AiResponses;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiResponseResource;
use App\Models\AiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AiResponseController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', AiResponse::class);

        return Inertia::render('ai-responses/Index', [
            'aiResponses' => AiResponseResource::collection(
                AiResponse::query()
                    ->whereBelongsTo($request->user())
                    ->latest()
                    ->paginate(15),
            ),
        ]);
    }

    public function show(AiResponse $aiResponse): Response
    {
        Gate::authorize('view', $aiResponse);

        return Inertia::render('ai-responses/Show', [
            'aiResponse' => AiResponseResource::make($aiResponse),
        ]);
    }

}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'aiRequests' => AiResponse::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(5)
                ->get(['id', 'request_id', 'entity', 'status', 'created_at'])
                ->map(fn (AiResponse $aiResponse): array => [
                    'id' => $aiResponse->id,
                    'requestId' => $aiResponse->request_id,
                    'entity' => $aiResponse->entity,
                    'status' => $aiResponse->status,
                    'createdAt' => $aiResponse->created_at?->toIso8601String(),
                ]),
        ]);
    }
}

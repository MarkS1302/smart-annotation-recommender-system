<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiResponseResource;
use App\Models\AiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowAnnotationRecommendationController extends Controller
{
    public function __invoke(Request $request, string $requestId): JsonResponse
    {
        $response = AiResponse::query()
            ->whereBelongsTo($request->user())
            ->where('request_id', $requestId)
            ->firstOrFail();

        return response()->json([
            'data' => AiResponseResource::make($response)->resolve($request),
        ]);
    }
}

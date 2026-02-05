<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PingController extends Controller
{
    #[OA\Get(
        path: '/api/ping',
        operationId: 'ping',
        tags: ['System'],
        summary: 'Health check',
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'app', type: 'string', example: 'Laravel'),
                        new OA\Property(property: 'time', type: 'string', example: '2026-02-05T20:00:00Z'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'app' => config('app.name'),
            'time' => now()->toISOString(),
        ]);
    }
}

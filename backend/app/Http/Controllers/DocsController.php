<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Local API',
    version: '1.0.0',
    description: 'API documentation for the Laravel backend'
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Local Docker'
)]
class DocsController extends Controller
{
    // Attributes only
}

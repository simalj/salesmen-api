<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to PROSIGHT Salesmen API',
        'version' => 'v1.0.0',
        'documentation' => [
            'health_check' => '/api/health',
            'salesmen_api' => '/api/salesmen',
            'codelists' => '/api/codelists',
            'api_v1' => '/api/v1',
            'swagger_ui' => '/api/documentation'
        ],
        'endpoints' => [
            'GET /api/health' => 'System health check',
            'GET /api/salesmen' => 'List all salesmen (paginated)',
            'POST /api/salesmen' => 'Create new salesman',
            'GET /api/salesmen/{id}' => 'Get specific salesman',
            'PUT /api/salesmen/{id}' => 'Update salesman',
            'DELETE /api/salesmen/{id}' => 'Delete salesman',
            'GET /api/codelists' => 'Get validation codelists'
        ],
        'deployment' => [
            'docker' => 'docker-compose up -d --build',
            'local' => 'php artisan serve'
        ],
        'built_for' => 'PROSIGHT Slovensko - Backend Developer Assignment'
    ], 200, [], JSON_PRETTY_PRINT);
});

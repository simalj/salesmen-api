<?php

namespace App\Http\Controllers;

use App\Http\Resources\CodelistResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Http\JsonResponse;

class CodelistController extends Controller
{
    /**
     * Get all codelists for validation.
     * GET /codelists
     */
    public function index(): JsonResponse
    {
        try {
            return response()->json(new CodelistResource(null));
            
        } catch (\Exception $e) {
            return response()->json(
                ErrorResource::validationError(['general' => [$e->getMessage()]]),
                400
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\CodelistResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CodelistsResponse",
    properties: [
        new OA\Property(property: "genders", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "code", type: "string", example: "m"),
            new OA\Property(property: "name", type: "string", example: "muž")
        ])),
        new OA\Property(property: "marital_statuses", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "code", type: "string", example: "single"),
            new OA\Property(property: "name", type: "string", example: "slobodný / slobodná")
        ])),
        new OA\Property(property: "titles_before", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "code", type: "string", example: "Ing."),
            new OA\Property(property: "name", type: "string", example: "Ing.")
        ])),
        new OA\Property(property: "titles_after", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "code", type: "string", example: "PhD."),
            new OA\Property(property: "name", type: "string", example: "PhD.")
        ]))
    ]
)]
#[OA\PathItem(
    path: "/api/v1/codelists",
    summary: "Codelists endpoints",
    description: "Endpoints for retrieving application codelists"
)]
#[OA\Tag(
    name: "Codelists",
    description: "Application codelists and reference data"
)]
class CodelistController extends Controller
{
    /**
     * Get all codelists for frontend validation.
     * Cached for 1 hour since codelists rarely change.
     */
    #[OA\Get(
        path: "/api/v1/codelists",
        operationId: "getCodelists",
        summary: "Get all codelists",
        description: "Retrieve all available codelists for validation",
        tags: ["Codelists"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Codelists retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/CodelistsResponse")
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $codelists = cache()->remember('codelists', 3600, function () {
            return new CodelistResource(null);
        });
        
        return response()->json($codelists);
    }
}

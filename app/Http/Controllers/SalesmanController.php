<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesmanRequest;
use App\Http\Requests\UpdateSalesmanRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SalesmanCollection;
use App\Http\Resources\SalesmanResource;
use App\Models\Salesman;
use App\Services\SalesmanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Salesmen API",
    description: "Professional REST API for managing salesmen data - PROSIGHT Slovensko Technical Assignment"
)]
#[OA\Server(url: "/api", description: "API Server")]
#[OA\Schema(
    schema: "SalesmanRequest",
    required: ["first_name", "last_name", "prosight_id", "email", "gender"],
    properties: [
        new OA\Property(property: "first_name", type: "string", minLength: 2, maxLength: 50, description: "First name", example: "Ján"),
        new OA\Property(property: "last_name", type: "string", minLength: 2, maxLength: 50, description: "Last name", example: "Novák"),
        new OA\Property(property: "titles_before", type: "array", items: new OA\Items(type: "string"), maxItems: 10, example: ["Ing.", "Mgr."]),
        new OA\Property(property: "titles_after", type: "array", items: new OA\Items(type: "string"), maxItems: 10, example: ["PhD."]),
        new OA\Property(property: "prosight_id", type: "string", pattern: "^[0-9]{5}$", description: "5-digit ID", example: "12345"),
        new OA\Property(property: "email", type: "string", format: "email", description: "Email", example: "jan.novak@prosight.sk"),
        new OA\Property(property: "phone", type: "string", nullable: true, description: "Phone", example: "+421901234567"),
        new OA\Property(property: "gender", type: "string", enum: ["m", "f"], description: "Gender", example: "m"),
        new OA\Property(property: "marital_status", type: "string", enum: ["single", "married", "divorced", "widowed"], nullable: true, example: "single")
    ]
)]
#[OA\Schema(
    schema: "SalesmanResponse",
    properties: [
        new OA\Property(property: "data", type: "object", properties: [
            new OA\Property(property: "id", type: "string", format: "uuid", example: "01234567-89ab-cdef-0123-456789abcdef"),
            new OA\Property(property: "first_name", type: "string", example: "Ján"),
            new OA\Property(property: "last_name", type: "string", example: "Novák"),
            new OA\Property(property: "display_name", type: "string", example: "Ing. Mgr. Ján Novák PhD."),
            new OA\Property(property: "titles_before", type: "array", items: new OA\Items(type: "string"), example: ["Ing.", "Mgr."]),
            new OA\Property(property: "titles_after", type: "array", items: new OA\Items(type: "string"), example: ["PhD."]),
            new OA\Property(property: "prosight_id", type: "string", example: "12345"),
            new OA\Property(property: "email", type: "string", example: "jan.novak@prosight.sk"),
            new OA\Property(property: "phone", type: "string", example: "+421901234567"),
            new OA\Property(property: "gender", type: "string", example: "m"),
            new OA\Property(property: "marital_status", type: "string", example: "single"),
            new OA\Property(property: "created_at", type: "string", format: "date-time"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time")
        ])
    ]
)]
#[OA\Schema(
    schema: "SalesmenCollection",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/SalesmanResponse/properties/data")),
        new OA\Property(property: "links", type: "object", properties: [
            new OA\Property(property: "first", type: "string", nullable: true),
            new OA\Property(property: "last", type: "string", nullable: true),
            new OA\Property(property: "prev", type: "string", nullable: true),
            new OA\Property(property: "next", type: "string", nullable: true)
        ]),
        new OA\Property(property: "meta", type: "object", properties: [
            new OA\Property(property: "current_page", type: "integer"),
            new OA\Property(property: "per_page", type: "integer"),
            new OA\Property(property: "total", type: "integer")
        ])
    ]
)]
#[OA\Schema(
    schema: "ErrorResponse",
    properties: [
        new OA\Property(property: "errors", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "code", type: "string", example: "RESOURCE_NOT_FOUND"),
            new OA\Property(property: "message", type: "string", example: "Resource not found")
        ]))
    ]
)]
#[OA\Schema(
    schema: "ValidationError",
    properties: [
        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
        new OA\Property(property: "errors", type: "object", example: ["first_name" => ["The first name field is required."]])
    ]
)]
#[OA\PathItem(
    path: "/api/v1/salesmen",
    summary: "Salesmen management endpoints",
    description: "CRUD operations for salesmen resources"
)]
#[OA\Tag(
    name: "Salesmen",
    description: "Salesmen management operations"  
)]
class SalesmanController extends Controller
{
    public function __construct(
        private SalesmanService $salesmanService
    ) {}

    /**
     * Display a listing of the resource.
     * GET /salesmen?page=1&per_page=10&sort=-created_at
     */
    #[OA\Get(
        path: "/api/v1/salesmen",
        operationId: "getSalesmen",
        summary: "List all salesmen",
        description: "Retrieve a paginated list of salesmen with optional sorting",
        tags: ["Salesmen"],
        parameters: [
            new OA\Parameter(
                name: "page",
                description: "Page number for pagination",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, default: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                description: "Number of items per page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, default: 15)
            ),
            new OA\Parameter(
                name: "sort",
                description: "Sort field and direction (e.g., 'first_name' or '-created_at')",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", example: "first_name")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of salesmen retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SalesmenCollection")
            ),
            new OA\Response(
                response: 400,
                description: "Bad request",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 10);
            $sort = $request->string('sort', 'created_at');
            
            $salesmen = $this->salesmanService->getPaginatedSalesmen([
                'per_page' => $perPage,
                'sort' => $sort,
            ]);
            
            return response()->json(new SalesmanCollection($salesmen));
            
        } catch (\Exception $e) {
            return response()->json(
                ErrorResource::notFound('Query execution failed', $e->getMessage()),
                400
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /salesmen
     */
    #[OA\Post(
        path: "/api/v1/salesmen",
        operationId: "createSalesman",
        summary: "Create a new salesman",
        description: "Create a new salesman record with validation",
        tags: ["Salesmen"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SalesmanRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Salesman created successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SalesmanResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Validation error",
                content: new OA\JsonContent(ref: "#/components/schemas/ValidationError")
            ),
            new OA\Response(
                response: 409,
                description: "Salesman already exists",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function store(StoreSalesmanRequest $request): JsonResponse
    {
        try {
            $salesman = $this->salesmanService->createSalesman($request);
            
            return response()->json(new SalesmanResource($salesman), 201);
            
        } catch (\Exception $e) {
            return response()->json(
                ErrorResource::validationError(['general' => [$e->getMessage()]]),
                400
            );
        }
    }

    /**
     * Display the specified resource.
     * GET /salesmen/{uuid}
     */
    #[OA\Get(
        path: "/api/v1/salesmen/{id}",
        operationId: "getSalesmanById",
        summary: "Get salesman by ID",
        description: "Retrieve a specific salesman by their UUID",
        tags: ["Salesmen"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Salesman UUID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Salesman retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SalesmanResponse")
            ),
            new OA\Response(
                response: 404,
                description: "Salesman not found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function show(string $id): JsonResponse
    {
        try {
            $salesman = Salesman::findOrFail($id);
            return response()->json(new SalesmanResource($salesman));
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                ErrorResource::notFound('Salesman', $id),
                404
            );
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /salesmen/{uuid}
     */
    #[OA\Put(
        path: "/api/v1/salesmen/{id}",
        operationId: "updateSalesman",
        summary: "Update salesman",
        description: "Update an existing salesman record",
        tags: ["Salesmen"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Salesman UUID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SalesmanRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Salesman updated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SalesmanResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Validation error",
                content: new OA\JsonContent(ref: "#/components/schemas/ValidationError")
            ),
            new OA\Response(
                response: 404,
                description: "Salesman not found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function update(UpdateSalesmanRequest $request, string $id): JsonResponse
    {
        try {
            $salesman = Salesman::findOrFail($id);
            $updatedSalesman = $this->salesmanService->updateSalesman($salesman, $request);
            
            return response()->json(new SalesmanResource($updatedSalesman));
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                ErrorResource::notFound('Salesman', $id),
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                ErrorResource::validationError(['general' => [$e->getMessage()]]),
                400
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /salesmen/{uuid}
     */
    #[OA\Delete(
        path: "/api/v1/salesmen/{id}",
        operationId: "deleteSalesman",
        summary: "Delete salesman",
        description: "Delete a salesman record",
        tags: ["Salesmen"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Salesman UUID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Salesman deleted successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Salesman not found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function destroy(string $id): Response|JsonResponse
    {
        try {
            $salesman = Salesman::findOrFail($id);
            $this->salesmanService->deleteSalesman($salesman);
            
            return response()->noContent(); // 204 No Content
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                ErrorResource::notFound('Salesman', $id),
                404
            );
        }
    }
}

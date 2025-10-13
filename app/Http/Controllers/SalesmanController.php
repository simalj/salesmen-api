<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesmanRequest;
use App\Http\Requests\UpdateSalesmanRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SalesmanCollection;
use App\Http\Resources\SalesmanResource;
use App\Models\Salesman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SalesmanController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /salesmen?page=1&per_page=10&sort=-created_at
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Salesman::query();
            
            // Sorting
            $sort = $request->string('sort', 'created_at');
            $direction = 'asc';
            
            if (str_starts_with($sort, '-')) {
                $direction = 'desc';
                $sort = substr($sort, 1);
            }
            
            // Validate sort field
            $allowedSortFields = ['created_at', 'updated_at', 'first_name', 'last_name', 'email', 'prosight_id'];
            if (in_array($sort, $allowedSortFields)) {
                $query->orderBy($sort, $direction);
            }
            
            // Pagination
            $perPage = min($request->integer('per_page', 10), 100); // Max 100 per page
            $salesmen = $query->paginate($perPage);
            
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
    public function store(StoreSalesmanRequest $request): JsonResponse
    {
        try {
            // Check for existing prosight_id or email (custom validation)
            $prosightId = $request->string('prosight_id');
            $existingBySalesmanId = Salesman::where('prosight_id', $prosightId)->first();
            if ($existingBySalesmanId) {
                return response()->json(
                    ErrorResource::alreadyExists('prosight_id', $prosightId),
                    409
                );
            }

            $email = $request->string('email');
            $existingByEmail = Salesman::where('email', $email)->first();
            if ($existingByEmail) {
                return response()->json(
                    ErrorResource::alreadyExists('email', $email),
                    409
                );
            }

            $salesman = Salesman::create($request->validatedForDatabase());
            
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
    public function update(UpdateSalesmanRequest $request, string $id): JsonResponse
    {
        try {
            $salesman = Salesman::findOrFail($id);
            
            // Check for existing prosight_id or email (excluding current record)
            if ($request->has('prosight_id')) {
                $prosightId = $request->string('prosight_id');
                $existingBySalesmanId = Salesman::where('prosight_id', $prosightId)
                    ->where('id', '!=', $id)
                    ->first();
                if ($existingBySalesmanId) {
                    return response()->json(
                        ErrorResource::alreadyExists('prosight_id', $prosightId),
                        409
                    );
                }
            }

            if ($request->has('email')) {
                $email = $request->string('email');
                $existingByEmail = Salesman::where('email', $email)
                    ->where('id', '!=', $id)
                    ->first();
                if ($existingByEmail) {
                    return response()->json(
                        ErrorResource::alreadyExists('email', $email),
                        409
                    );
                }
            }
            
            $salesman->update($request->validatedForDatabase());
            
            return response()->json(new SalesmanResource($salesman));
            
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
    public function destroy(string $id): Response|JsonResponse
    {
        try {
            $salesman = Salesman::findOrFail($id);
            $salesman->delete();
            
            return response()->noContent(); // 204 No Content
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                ErrorResource::notFound('Salesman', $id),
                404
            );
        }
    }
}

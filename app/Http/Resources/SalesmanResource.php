<?php

namespace App\Http\Resources;

use App\Models\Salesman;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Salesman
 */
class SalesmanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Salesman $salesman */
        $salesman = $this->resource;
        
        return [
            'id' => $salesman->id,
            'self' => '/salesmen/' . $salesman->id,
            'first_name' => $salesman->first_name,
            'last_name' => $salesman->last_name,
            'display_name' => $salesman->display_name, // Použije accessor z modelu
            'titles_before' => $salesman->titles_before,
            'titles_after' => $salesman->titles_after,
            'prosight_id' => $salesman->prosight_id,
            'email' => $salesman->email,
            'phone' => $salesman->phone,
            'gender' => $salesman->gender_code,
            'marital_status' => $salesman->marital_status_code,
            'created_at' => $salesman->created_at?->toISOString(),
            'updated_at' => $salesman->updated_at?->toISOString(),
        ];
    }
}

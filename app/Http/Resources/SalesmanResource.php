<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'self' => '/salesmen/' . $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name, // Použije accessor z modelu
            'titles_before' => $this->titles_before,
            'titles_after' => $this->titles_after,
            'prosight_id' => $this->prosight_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender_code,
            'marital_status' => $this->marital_status_code,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

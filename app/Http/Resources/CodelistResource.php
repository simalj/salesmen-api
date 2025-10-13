<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CodelistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Tento resource bude staticky vracať všetky codelists
        return [
            'genders' => $this->getGenders(),
            'marital_statuses' => $this->getMaritalStatuses(),
            'titles_before' => $this->getTitlesBefore(),
            'titles_after' => $this->getTitlesAfter(),
        ];
    }

    /**
     * Get genders codelist.
     */
    private function getGenders(): array
    {
        return \App\Models\Gender::all()->map(function ($gender) {
            return [
                'code' => $gender->code,
                'name' => $gender->name,
            ];
        })->toArray();
    }

    /**
     * Get marital statuses codelist.
     */
    private function getMaritalStatuses(): array
    {
        return \App\Models\MaritalStatus::all()->map(function ($status) {
            return [
                'code' => $status->code,
                'name' => [
                    'm' => $status->name_m,
                    'f' => $status->name_f,
                    'general' => $status->name_general,
                ],
            ];
        })->toArray();
    }

    /**
     * Get titles before codelist.
     */
    private function getTitlesBefore(): array
    {
        return \App\Models\TitleBefore::all()->map(function ($title) {
            return [
                'code' => $title->code,
                'name' => $title->name,
            ];
        })->toArray();
    }

    /**
     * Get titles after codelist.
     */
    private function getTitlesAfter(): array
    {
        return \App\Models\TitleAfter::all()->map(function ($title) {
            return [
                'code' => $title->code,
                'name' => $title->name,
            ];
        })->toArray();
    }
}

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
     *
     * @return array<int, array<string, string>>
     */
    private function getGenders(): array
    {
        /** @var array<int, array<string, string>> */
        return \App\Models\Gender::all()->map(function ($gender) {
            return [
                'code' => $gender->code,
                'name' => $gender->name,
            ];
        })->toArray();
    }

    /**
     * Get marital statuses codelist.
     *
     * @return array<int, array<string, string>>
     */
    private function getMaritalStatuses(): array
    {
        /** @var array<int, array<string, string>> */
        return \App\Models\MaritalStatus::all()->map(function ($status) {
            return [
                'code' => $status->code,
                'name' => $status->name_general, // Používame general name pre codelist
            ];
        })->toArray();
    }

    /**
     * Get titles before codelist.
     *
     * @return array<int, array<string, string>>
     */
    private function getTitlesBefore(): array
    {
        /** @var array<int, array<string, string>> */
        return \App\Models\TitleBefore::all()->map(function ($title) {
            return [
                'code' => $title->code,
                'name' => $title->name,
            ];
        })->toArray();
    }

    /**
     * Get titles after codelist.
     *
     * @return array<int, array<string, string>>
     */
    private function getTitlesAfter(): array
    {
        /** @var array<int, array<string, string>> */
        return \App\Models\TitleAfter::all()->map(function ($title) {
            return [
                'code' => $title->code,
                'name' => $title->name,
            ];
        })->toArray();
    }
}

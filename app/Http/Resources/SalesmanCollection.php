<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesmanCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => SalesmanResource::collection($this->collection),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        if ($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return [
                'links' => [
                    'first' => $this->getFirstPageUrl(),
                    'last' => $this->getLastPageUrl(),
                    'prev' => $this->getPrevPageUrl(),
                    'next' => $this->getNextPageUrl(),
                ],
            ];
        }

        return [];
    }

    /**
     * Get the first page URL.
     */
    private function getFirstPageUrl(): ?string
    {
        if ($this->resource->currentPage() <= 1) {
            return null;
        }
        
        return $this->buildPageUrl(1);
    }

    /**
     * Get the last page URL.
     */
    private function getLastPageUrl(): ?string
    {
        if ($this->resource->currentPage() >= $this->resource->lastPage()) {
            return null;
        }
        
        return $this->buildPageUrl($this->resource->lastPage());
    }

    /**
     * Get the previous page URL.
     */
    private function getPrevPageUrl(): ?string
    {
        if ($this->resource->currentPage() <= 1) {
            return null;
        }
        
        return $this->buildPageUrl($this->resource->currentPage() - 1);
    }

    /**
     * Get the next page URL.
     */
    private function getNextPageUrl(): ?string
    {
        if ($this->resource->currentPage() >= $this->resource->lastPage()) {
            return null;
        }
        
        return $this->buildPageUrl($this->resource->currentPage() + 1);
    }

    /**
     * Build a page URL.
     */
    private function buildPageUrl(int $page): string
    {
        $path = request()->path();
        $query = request()->query();
        $query['page'] = $page;
        
        return '/' . $path . '?' . http_build_query($query);
    }
}

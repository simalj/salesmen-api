<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesmanCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'data' => SalesmanResource::collection($this->collection),
        ];

        // Add pagination info if resource is paginated
        if ($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = array_merge($data, $this->with($request));
        }

        return $data;
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        if ($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            /** @var LengthAwarePaginator<int, mixed> $paginator */
            $paginator = $this->resource;
            
            return [
                'links' => [
                    'first' => $this->getFirstPageUrl(),
                    'last' => $this->getLastPageUrl(),
                    'prev' => $this->getPrevPageUrl(),
                    'next' => $this->getNextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
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
        /** @var LengthAwarePaginator<int, mixed> $paginator */
        $paginator = $this->resource;
        
        if ($paginator->currentPage() <= 1) {
            return null;
        }
        
        return $this->buildPageUrl(1);
    }

    /**
     * Get the last page URL.
     */
    private function getLastPageUrl(): ?string
    {
        /** @var LengthAwarePaginator<int, mixed> $paginator */
        $paginator = $this->resource;
        
        if ($paginator->currentPage() >= $paginator->lastPage()) {
            return null;
        }
        
        return $this->buildPageUrl($paginator->lastPage());
    }

    /**
     * Get the previous page URL.
     */
    private function getPrevPageUrl(): ?string
    {
        /** @var LengthAwarePaginator<int, mixed> $paginator */
        $paginator = $this->resource;
        
        if ($paginator->currentPage() <= 1) {
            return null;
        }
        
        return $this->buildPageUrl($paginator->currentPage() - 1);
    }

    /**
     * Get the next page URL.
     */
    private function getNextPageUrl(): ?string
    {
        /** @var LengthAwarePaginator<int, mixed> $paginator */
        $paginator = $this->resource;
        
        if ($paginator->currentPage() >= $paginator->lastPage()) {
            return null;
        }
        
        return $this->buildPageUrl($paginator->currentPage() + 1);
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

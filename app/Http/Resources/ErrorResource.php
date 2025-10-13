<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'errors' => $this->resource,
        ];
    }

    /**
     * Create a validation error response.
     *
     * @param array<string, array<string>> $errors
     */
    public static function validationError(array $errors): self
    {
        $formattedErrors = [];
        
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $formattedErrors[] = [
                    'code' => 'INPUT_DATA_BAD_FORMAT',
                    'message' => "Bad format of input data. Field {$field} {$message}",
                ];
            }
        }
        
        return new self($formattedErrors);
    }

    /**
     * Create a not found error response.
     */
    public static function notFound(string $resource, string $id): self
    {
        return new self([
            [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => "{$resource} with id \"{$id}\" not found.",
            ]
        ]);
    }

    /**
     * Create an already exists error response.
     */
    public static function alreadyExists(string $field, string $value): self
    {
        return new self([
            [
                'code' => 'RESOURCE_ALREADY_EXISTS',
                'message' => "Resource with {$field} \"{$value}\" is already registered.",
            ]
        ]);
    }

    /**
     * Create an input data out of range error response.
     */
    public static function outOfRange(string $field, string $value, string $range): self
    {
        return new self([
            [
                'code' => 'INPUT_DATA_OUT_OF_RANGE',
                'message' => "Input data out of range. Field {$field} of value \"{$value}\" is out of range. Acceptable range for this field is {$range}.",
            ]
        ]);
    }
}

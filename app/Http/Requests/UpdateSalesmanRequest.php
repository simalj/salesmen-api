<?php

namespace App\Http\Requests;

use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\TitleAfter;
use App\Models\TitleBefore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesmanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        // Pre update sú všetky polia optional (sometimes)
        $salesmanId = $this->route('salesman'); // UUID z route
        
        return [
            'first_name' => ['sometimes', 'required', 'string', 'min:2', 'max:50'],
            'last_name' => ['sometimes', 'required', 'string', 'min:2', 'max:50'],
            'prosight_id' => [
                'sometimes', 
                'required', 
                'string', 
                'size:5', 
                'regex:/^\d{5}$/', 
                Rule::unique('salesmen', 'prosight_id')->ignore($salesmanId)
            ],
            'email' => [
                'sometimes', 
                'required', 
                'email', 
                Rule::unique('salesmen', 'email')->ignore($salesmanId)
            ],
            'gender' => ['sometimes', 'required', 'string', Rule::in(Gender::pluck('code')->toArray())],

            // Voliteľné polia
            'phone' => ['sometimes', 'nullable', 'string'],
            'marital_status' => ['sometimes', 'nullable', 'string', Rule::in(MaritalStatus::pluck('code')->toArray())],

            // Tituly
            'titles_before' => ['sometimes', 'nullable', 'array', 'min:0', 'max:10'],
            'titles_before.*' => ['string', 'min:2', 'max:10', Rule::in(TitleBefore::pluck('code')->toArray())],
            
            'titles_after' => ['sometimes', 'nullable', 'array', 'min:0', 'max:10'],
            'titles_after.*' => ['string', 'min:2', 'max:10', Rule::in(TitleAfter::pluck('code')->toArray())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prosight_id.size' => 'Prosight ID must be exactly 5 digits.',
            'prosight_id.regex' => 'Prosight ID must contain only numbers.',
            'prosight_id.unique' => 'Salesman with this Prosight ID already exists.',
            'email.unique' => 'Salesman with this email already exists.',
            'gender.in' => 'Invalid gender code. Allowed values: m, f.',
            'marital_status.in' => 'Invalid marital status code.',
            'titles_before.max' => 'Maximum 10 titles before name allowed.',
            'titles_after.max' => 'Maximum 10 titles after name allowed.',
            'titles_before.*.in' => 'Invalid title before name.',
            'titles_after.*.in' => 'Invalid title after name.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('gender_code')) {
            $this->merge(['gender' => $this->input('gender_code')]);
        }

        if ($this->has('marital_status_code')) {
            $this->merge(['marital_status' => $this->input('marital_status_code')]);
        }
    }

    /**
     * Get the validated data from the request, mapped to database columns.
     *
     * @return array<string, mixed>
     */
    public function validatedForDatabase(): array
    {
        $validated = $this->validated();
        $data = [];

        // Mapujeme len polia, ktoré boli poskytnuté
        if (isset($validated['first_name'])) $data['first_name'] = $validated['first_name'];
        if (isset($validated['last_name'])) $data['last_name'] = $validated['last_name'];
        if (isset($validated['prosight_id'])) $data['prosight_id'] = $validated['prosight_id'];
        if (isset($validated['email'])) $data['email'] = $validated['email'];
        if (array_key_exists('phone', $validated)) $data['phone'] = $validated['phone'];
        if (isset($validated['gender'])) $data['gender_code'] = $validated['gender'];
        if (array_key_exists('marital_status', $validated)) $data['marital_status_code'] = $validated['marital_status'];
        if (array_key_exists('titles_before', $validated)) $data['titles_before'] = $validated['titles_before'];
        if (array_key_exists('titles_after', $validated)) $data['titles_after'] = $validated['titles_after'];

        return $data;
    }
}

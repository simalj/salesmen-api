<?php

namespace App\Http\Requests;

use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\TitleAfter;
use App\Models\TitleBefore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesmanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Pre API bez autentifikácie
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Povinné polia
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'prosight_id' => ['required', 'string', 'size:5', 'regex:/^\d{5}$/', 'unique:salesmen,prosight_id'],
            'email' => ['required', 'email', 'unique:salesmen,email'],
            'gender' => ['required', 'string', Rule::in(Gender::pluck('code')->toArray())],

            // Voliteľné polia  
            'phone' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string', Rule::in(MaritalStatus::pluck('code')->toArray())],

            // Tituly - arrays s validáciou proti codelistom
            'titles_before' => ['nullable', 'array', 'min:0', 'max:10'],
            'titles_before.*' => ['string', 'min:2', 'max:10', Rule::in(TitleBefore::pluck('code')->toArray())],
            
            'titles_after' => ['nullable', 'array', 'min:0', 'max:10'],
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
        // Mapujeme gender_code na gender pre validáciu
        if ($this->has('gender_code')) {
            $this->merge([
                'gender' => $this->input('gender_code')
            ]);
        }

        // Mapujeme marital_status_code na marital_status pre validáciu  
        if ($this->has('marital_status_code')) {
            $this->merge([
                'marital_status' => $this->input('marital_status_code')
            ]);
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
        
        // Mapujeme späť na databázové stĺpce
        return [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'prosight_id' => $validated['prosight_id'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'gender_code' => $validated['gender'],
            'marital_status_code' => $validated['marital_status'] ?? null,
            'titles_before' => $validated['titles_before'] ?? null,
            'titles_after' => $validated['titles_after'] ?? null,
        ];
    }
}

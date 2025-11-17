<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric|min:-90|max:90',
            'longitude' => 'required|numeric|min:-180|max:180',
            'accuracy_meters' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'speed_mps' => 'nullable|numeric|min:0',
            'heading_degrees' => 'nullable|numeric|min:0|max:360',
            'source' => 'nullable|in:foreground,background',
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'La latitude est obligatoire',
            'longitude.required' => 'La longitude est obligatoire',
            'latitude.min' => 'La latitude doit être entre -90 et 90',
            'latitude.max' => 'La latitude doit être entre -90 et 90',
            'longitude.min' => 'La longitude doit être entre -180 et 180',
            'longitude.max' => 'La longitude doit être entre -180 et 180',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422));
    }
}

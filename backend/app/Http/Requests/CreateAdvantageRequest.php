<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateAdvantageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'type' => 'required|in:percentage,fixed_amount,2for1,free_item',
            'discount_percentage' => 'required_if:type,percentage|nullable|numeric|min:1|max:100',
            'discount_amount' => 'required_if:type,fixed_amount|nullable|numeric|min:0',
            'valid_from' => 'required|date|after_or_equal:today',
            'valid_until' => 'required|date|after:valid_from',
            'days_available' => 'required|array|min:1',
            'days_available.*' => 'integer|min:0|max:6',
            'time_from' => 'nullable|date_format:H:i',
            'time_until' => 'nullable|date_format:H:i|after:time_from',
            'category_id' => 'required|exists:categories,id',
            'terms_conditions' => 'nullable|string|max:2000',
            'usage_limit' => 'nullable|integer|min:1',
            'image_url' => 'nullable|url',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire',
            'description.required' => 'La description est obligatoire',
            'type.required' => 'Le type de réduction est obligatoire',
            'type.in' => 'Le type de réduction doit être : percentage, fixed_amount, 2for1, ou free_item',
            'discount_percentage.required_if' => 'Le pourcentage de réduction est obligatoire pour ce type',
            'discount_amount.required_if' => 'Le montant de réduction est obligatoire pour ce type',
            'valid_from.required' => 'La date de début est obligatoire',
            'valid_from.after_or_equal' => 'La date de début doit être aujourd\'hui ou ultérieure',
            'valid_until.required' => 'La date de fin est obligatoire',
            'valid_until.after' => 'La date de fin doit être après la date de début',
            'days_available.required' => 'Au moins un jour doit être sélectionné',
            'category_id.required' => 'La catégorie est obligatoire',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422));
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'gte:min_price'],
            'stock_min' => ['sometimes', 'integer', 'min:0'],
            'stock_max' => ['sometimes', 'integer', 'gte:stock_min'],
            'stock_level' => ['sometimes', Rule::in(['out_of_stock', 'low', 'in_stock'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}

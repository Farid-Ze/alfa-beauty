<?php

namespace App\Http\Requests\Api\V1;

/**
 * Request validation for listing products.
 */
class ListProductsRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:100'],
            'brand_id' => ['sometimes', 'integer', 'exists:brands,id'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'featured' => ['sometimes', 'boolean'],
            'in_stock' => ['sometimes', 'boolean'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0', 'gte:min_price'],
            'sort' => ['sometimes', 'string', 'in:name,base_price,created_at,stock'],
            'direction' => ['sometimes', 'string', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize search input
        if ($this->has('search')) {
            $this->merge([
                'search' => mb_substr(trim($this->search), 0, 100),
            ]);
        }

        // Convert string booleans
        if ($this->has('featured')) {
            $this->merge([
                'featured' => filter_var($this->featured, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('in_stock')) {
            $this->merge([
                'in_stock' => filter_var($this->in_stock, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}

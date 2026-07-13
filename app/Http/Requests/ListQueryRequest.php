<?php

namespace App\Http\Requests;

use App\Filters\FilterOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filters' => ['sometimes', 'array'],
            'filters.*.field' => ['required_with:filters', 'string'],
            'filters.*.operator' => ['required_with:filters', 'string', Rule::in(FilterOperator::values())],
            'filters.*.value' => ['nullable'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return list<array{field: string, operator: string, value: mixed}>
     */
    public function filters(): array
    {
        return $this->validated('filters', []);
    }

    public function searchTerm(): ?string
    {
        return $this->validated('search');
    }

    public function sortExpression(): ?string
    {
        return $this->validated('sort');
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 15);
    }
}

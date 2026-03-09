<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'name_uz' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'description_uz' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $this->route('product'),
            'category_id' => 'required|integer|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'manufacturer' => 'nullable|string|max:255',
            'article' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'coating' => 'nullable|string|max:255',
            'index' => 'nullable|numeric',
            'family' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'option' => 'nullable|string|max:255',
            'sph' => 'nullable|numeric',
            'cyl' => 'nullable|numeric',
            'axis' => 'nullable|numeric',
            'add' => 'nullable|numeric',
            'prism' => 'nullable|numeric',
            'photos' => 'sometimes|array|max:10',
            'photos.*' => 'sometimes|image|mimes:jpg,png',
            'attributes' => 'sometimes|array',
            'attributes.*' => 'nullable|array',
            'attributes.*.*' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'index.numeric' => 'Поле «Индекс» должно быть числом.',
            'index.min'     => 'Поле «Индекс» не может быть меньше 0.',
            'index.max'     => 'Поле «Индекс» не может превышать 99.99.',
        ];
    }
}

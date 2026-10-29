<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50'],
            // Güncellemede ürünün kendi barkodu hariç tutulur
            'barcode' => ['nullable', 'string', 'max:64', \Illuminate\Validation\Rule::unique('products', 'barcode')->ignore($this->route('product'))],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:20'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ürün adı zorunludur.',
            'unit.required' => 'Birim zorunludur.',
            'purchase_price.required' => 'Alış fiyatı zorunludur.',
            'purchase_price.numeric' => 'Alış fiyatı sayı olmalıdır.',
            'sale_price.required' => 'Satış fiyatı zorunludur.',
            'sale_price.numeric' => 'Satış fiyatı sayı olmalıdır.',
            'stock_quantity.required' => 'Stok adedi zorunludur.',
            'stock_quantity.integer' => 'Stok adedi tam sayı olmalıdır.',
            'stock_quantity.min' => 'Stok adedi negatif olamaz.',
        ];
    }
}

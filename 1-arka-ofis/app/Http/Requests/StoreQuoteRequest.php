<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'tax_rate' => ['required', 'numeric', 'in:0,1,10,20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.required' => 'Cari seçilmelidir.',
            'contact_id.exists' => 'Seçilen cari geçersiz.',
            'issue_date.required' => 'Teklif tarihi zorunludur.',
            'valid_until.after_or_equal' => 'Geçerlilik tarihi teklif tarihinden önce olamaz.',
            'tax_rate.in' => 'KDV oranı 0, 1, 10 veya 20 olmalıdır.',
            'items.required' => 'Teklife en az bir kalem eklenmelidir.',
            'items.*.product_id.required' => 'Her kalemde bir ürün seçilmelidir.',
            'items.*.quantity.min' => 'Kalem miktarı sıfırdan büyük olmalıdır.',
            'items.*.unit_price.required' => 'Kalem birim fiyatı zorunludur.',
        ];
    }
}

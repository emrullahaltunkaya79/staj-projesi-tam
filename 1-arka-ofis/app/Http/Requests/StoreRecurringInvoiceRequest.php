<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:28'],
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
            'title.required' => 'Kural adı zorunludur.',
            'contact_id.required' => 'Cari seçilmelidir.',
            'contact_id.exists' => 'Seçilen cari geçersiz.',
            'day_of_month.required' => 'Fatura günü zorunludur.',
            'day_of_month.max' => 'Fatura günü 1-28 arasında olmalıdır.',
            'tax_rate.in' => 'KDV oranı 0, 1, 10 veya 20 olmalıdır.',
            'items.required' => 'Kurala en az bir kalem eklenmelidir.',
            'items.*.product_id.required' => 'Her kalemde bir ürün seçilmelidir.',
            'items.*.quantity.min' => 'Kalem miktarı sıfırdan büyük olmalıdır.',
            'items.*.unit_price.required' => 'Kalem birim fiyatı zorunludur.',
        ];
    }
}

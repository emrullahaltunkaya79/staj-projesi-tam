<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Tahsilat formu doğrulama kuralları.
     * Bakiye aşımı kontrolü, satır kilidi altında PaymentService'te yapılır.
     */
    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_type' => ['required', 'in:nakit,havale'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.required' => 'Cari seçilmelidir.',
            'contact_id.exists' => 'Seçilen cari geçersiz.',
            'amount.required' => 'Tahsilat tutarı zorunludur.',
            'amount.numeric' => 'Tahsilat tutarı sayı olmalıdır.',
            'amount.min' => 'Tahsilat tutarı sıfırdan büyük olmalıdır.',
            'payment_type.required' => 'Tahsilat türü seçilmelidir.',
            'payment_type.in' => 'Tahsilat türü Nakit veya Havale olmalıdır.',
            'description.max' => 'Açıklama en fazla 1000 karakter olabilir.',
        ];
    }
}

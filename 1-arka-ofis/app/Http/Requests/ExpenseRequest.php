<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payment_type' => ['required', 'in:nakit,havale'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Gider kategorisi zorunludur.',
            'amount.required' => 'Tutar zorunludur.',
            'amount.numeric' => 'Tutar sayı olmalıdır.',
            'amount.min' => 'Tutar sıfırdan büyük olmalıdır.',
            'expense_date.required' => 'Gider tarihi zorunludur.',
            'payment_type.in' => 'Ödeme türü Nakit veya Havale olmalıdır.',
        ];
    }
}

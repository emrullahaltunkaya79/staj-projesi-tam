<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /** Bilinen pazar yerlerinin standart komisyon yüzdeleri */
    public const PAZAR_YERI_KOMISYONLARI = [
        'trendyol' => 15.0,
        'hepsiburada' => 14.0,
        'amazon' => 12.0,
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Simülatör alanlarını normalize eder: bilinen bir pazar yeri seçilip özel
     * oran boş bırakıldıysa standart komisyon otomatik atanır; "web"/boş ise
     * komisyon sıfırlanır.
     */
    protected function prepareForValidation(): void
    {
        $pazaryeri = $this->input('pazaryeri');

        if (! $pazaryeri || $pazaryeri === 'web') {
            $this->merge([
                'pazaryeri' => $pazaryeri === 'web' ? 'web' : null,
                'pazaryeri_komisyon_orani' => $pazaryeri === 'web' ? 0 : null,
            ]);
            return;
        }

        if ($this->input('pazaryeri_komisyon_orani') === null
            && isset(self::PAZAR_YERI_KOMISYONLARI[$pazaryeri])) {
            $this->merge(['pazaryeri_komisyon_orani' => self::PAZAR_YERI_KOMISYONLARI[$pazaryeri]]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            // Güncellemede ürünün kendi barkodu hariç tutulur
            'barcode' => ['nullable', 'string', 'max:64', \Illuminate\Validation\Rule::unique('products', 'barcode')->ignore($this->route('product'))],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'alt_kategori' => ['nullable', 'string', 'max:120'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'pre_order' => ['nullable', 'boolean'],
            'katalog_gizli' => ['nullable', 'boolean'],
            'unit' => ['required', 'string', 'max:20'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'supplier_id' => ['nullable', 'integer', 'exists:contacts,id'],
            // Pazar yeri simülatörü girdileri (advisory — sipariş entegrasyonlarında varsayılan)
            'pazaryeri' => ['nullable', 'string', 'in:web,ozel,trendyol,hepsiburada,amazon'],
            'pazaryeri_komisyon_orani' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'kargo_maliyeti' => ['nullable', 'numeric', 'min:0'],
            'maliyet_kdv_dahil' => ['nullable', 'boolean'],
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

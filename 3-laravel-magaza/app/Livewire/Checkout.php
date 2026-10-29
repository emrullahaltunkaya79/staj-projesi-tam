<?php

namespace App\Livewire;

use App\Services\Backoffice\ApiClient;
use App\Services\Cart;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

class Checkout extends Component
{
    #[Validate('required|string|min:3|max:255', message: 'Ad soyad zorunludur.')]
    public string $name = '';

    #[Validate('required|email|max:255', message: 'Geçerli bir e-posta girin.')]
    public string $email = '';

    #[Validate('required|string|min:10|max:500', message: 'Teslim adresi zorunludur.')]
    public string $address = '';

    #[Validate('required|string|in:0,1,10,20')]
    public string $taxRate = '20';

    public bool $submitting = false;

    public function mount(): void
    {
        // "Siparişlerim" girişi yapan müşteri için ön doldurma
        $this->email = (string) (session('shop.email') ?? $this->email);
        $this->name = (string) (session('shop.name') ?? $this->name);
    }

    public function submit()
    {
        $this->validate();
        $this->submitting = true;

        // Müşteri adını oturuma taşı — sonraki ziyarette formlar dolu gelir
        session(['shop.email' => $this->email, 'shop.name' => $this->name]);

        if (Cart::count() === 0) {
            $this->addError('cart', 'Sepetiniz boş.');
            $this->submitting = false;

            return;
        }

        try {
            $client = ApiClient::make();

            // 1) Müşteri — idempotent: aynı e-posta her zaman aynı cariye eşlenir
            $customer = $client->findOrCreateCustomer($this->name, $this->email);
            $contactId = $customer['data']['contact_id'] ?? null;

            if (! $contactId) {
                throw new RuntimeException('Müşteri kaydı oluşturulamadı.');
            }

            // 2) Sipariş — external_ref sepet kimliği: aynı seketin tekrar gönderimi zararsız
            $externalRef = 'WEB-'.session()->getId().'-'.substr(md5(implode(',', array_keys(Cart::items()))), 0, 8);

            $items = [];
            foreach (Cart::items() as $productId => $item) {
                $items[] = ['product_id' => $productId, 'quantity' => $item['qty']];
            }

            $order = $client->createOrder([
                'contact_id' => $contactId,
                'tax_rate' => (int) $this->taxRate,
                'items' => $items,
                'shipping_address' => $this->address,
                'external_ref' => $externalRef,
                'customer_email' => $this->email,
                'customer_name' => $this->name,
            ]);

            $orderNo = $order['data']['order_no'] ?? null;
            if (! $orderNo) {
                throw new RuntimeException($order['message'] ?? 'Sipariş oluşturulamadı.');
            }

            Cart::clear();
            $this->dispatch('cart-updated');

            return redirect()->route('shop.order', ['orderNo' => $orderNo, 'yeni' => 1]);
        } catch (RuntimeException $e) {
            $this->addError('api', $e->getMessage());
            $this->submitting = false;
        }
    }

    public function render()
    {
        return view('livewire.checkout')
            ->layout('layouts.shop')
            ->title('Ödeme');
    }
}

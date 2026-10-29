<?php

namespace App\Livewire;

use App\Services\Backoffice\ApiClient;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

class MyOrders extends Component
{
    /** Oturumda tanılı müşteri (hafif giriş: yalnız e-posta) */
    public ?string $email = null;
    public ?string $name = null;

    #[Validate('required|email', message: 'Geçerli bir e-posta girin.')]
    public string $loginEmail = '';

    public array $orders = [];
    public ?string $error = null;
    public bool $loading = false;

    public function mount(): void
    {
        $this->email = session('shop.email');
        $this->name = session('shop.name');

        if ($this->email) {
            $this->loadOrders();
        }
    }

    public function login(): void
    {
        $this->validate();

        session(['shop.email' => $this->loginEmail]);
        $this->email = $this->loginEmail;
        $this->name = session('shop.name');

        $this->loadOrders();
    }

    public function logout(): void
    {
        session()->forget(['shop.email', 'shop.name']);
        $this->email = null;
        $this->name = null;
        $this->orders = [];
    }

    public function loadOrders(): void
    {
        $this->loading = true;
        $this->error = null;

        try {
            $result = ApiClient::make()->ordersByEmail($this->email);
            $this->orders = $result['data'] ?? [];
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.my-orders')
            ->layout('layouts.shop')
            ->title('Siparişlerim');
    }
}

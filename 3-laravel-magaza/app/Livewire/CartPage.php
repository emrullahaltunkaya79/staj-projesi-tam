<?php

namespace App\Livewire;

use App\Services\Cart;
use Livewire\Component;

class CartPage extends Component
{
    public array $items = [];
    public float $subtotal = 0;
    public float $tax = 0;
    public float $total = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->items = Cart::items();
        $this->subtotal = Cart::subtotal();
        $this->tax = Cart::tax();
        $this->total = Cart::total();
    }

    public function increase(int $id): void
    {
        Cart::update($id, (Cart::items()[$id]['qty'] ?? 0) + 1);
        $this->refresh();
        $this->dispatch('cart-updated');
    }

    public function decrease(int $id): void
    {
        Cart::update($id, (Cart::items()[$id]['qty'] ?? 1) - 1);
        $this->refresh();
        $this->dispatch('cart-updated');
    }

    public function remove(int $id): void
    {
        Cart::remove($id);
        $this->refresh();
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.cart-page')
            ->layout('layouts.shop')
            ->title('Sepetim — '.config('backoffice.name'));
    }
}

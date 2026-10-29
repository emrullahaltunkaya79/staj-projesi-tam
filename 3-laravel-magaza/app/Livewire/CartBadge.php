<?php

namespace App\Livewire;

use App\Services\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->count = Cart::count();
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        $this->count = Cart::count();
    }

    public function render()
    {
        return view('livewire.cart-badge');
    }
}

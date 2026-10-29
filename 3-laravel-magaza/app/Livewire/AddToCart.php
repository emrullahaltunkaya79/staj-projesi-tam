<?php

namespace App\Livewire;

use App\Services\Cart;
use Livewire\Component;

class AddToCart extends Component
{
    public int $productId;
    public string $name;
    public float $price;
    public int $available;
    public ?string $image = null;
    public string $unit = 'adet';
    public int $qty = 1;
    public bool $added = false;

    public function add(): void
    {
        if ($this->available < 1) {
            return;
        }
        $this->validate(['qty' => 'required|integer|min:1|max:'.$this->available]);

        Cart::add($this->productId, $this->name, $this->price, $this->qty, $this->image, $this->unit);
        $this->dispatch('cart-updated');
        $this->added = true;
    }

    public function render()
    {
        return view('livewire.add-to-cart');
    }
}

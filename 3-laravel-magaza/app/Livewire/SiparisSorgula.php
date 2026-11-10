<?php

namespace App\Livewire;

use App\Services\Backoffice\ApiClient;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

/** Misafir sipariş sorgulama: sipariş no + siparişteki e-posta birlikte doğrulanır → erişim anahtarlı bağlantı. */
class SiparisSorgula extends Component
{
    #[Validate('required', message: 'Sipariş numarası zorunludur.')]
    public string $no = '';

    #[Validate('required|email', message: 'Geçerli bir e-posta girin.')]
    public string $email = '';

    public ?string $hata = null;
    public bool $loading = false;

    public function mount(): void
    {
        $this->no = strtoupper((string) request()->query('no', ''));
    }

    public function sorgula(): void
    {
        $this->validate();
        $this->hata = null;
        $this->loading = true;

        try {
            $order = ApiClient::make()->order($this->no)['data'] ?? [];

            if (empty($order) || strcasecmp((string) ($order['customer_email'] ?? ''), $this->email) !== 0) {
                $this->hata = 'Bu bilgilerle eşleşen bir sipariş bulunamadı.';

                return;
            }

            $anahtar = (string) ($order['access_key'] ?? '');
            if ($anahtar === '') {
                $this->hata = 'Bu sipariş için erişim bağlantısı üretilemedi.';

                return;
            }

            $this->redirect(route('shop.order', ['orderNo' => $order['order_no'], 'k' => $anahtar]));
        } catch (RuntimeException $e) {
            $this->hata = 'Sorgulama yapılamadı: '.$e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.siparis-sorgula')
            ->layout('layouts.shop')
            ->title('Sipariş Sorgula');
    }
}
